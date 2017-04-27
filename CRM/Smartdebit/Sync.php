<?php

/**
 * Class CRM_Smartdebit_Sync
 *
 * This is the main class responsible for the "Sync" scheduled job
 * It can also be accessed at civicrm/smartdebit/sync
 */
class CRM_Smartdebit_Sync
{
  const QUEUE_NAME = 'sm-pull';
  const END_URL = 'civicrm/smartdebit/syncsd/confirm';
  const END_PARAMS = 'state=done';
  const BATCH_COUNT = 10;

  const COLLECTION_REPORT_AGE = '-3 month';

  const DEBUG = false;

  /**
   * If $auddisIDs and $aruddIDs are not set all available AUDDIS/ARUDD records will be processed.
   *
   * @param bool $interactive
   * @param null $auddisIDs
   * @param null $aruddIDs
   * @return bool|CRM_Queue_Runner
   */
  static function getRunner($interactive=TRUE, $auddisIDs = NULL, $aruddIDs = NULL) {
    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name'  => self::QUEUE_NAME,
      'type'  => 'Sql',
      'reset' => TRUE,
    ));

    // Get collection report for today
    CRM_Core_Error::debug_log_message('Smartdebit cron: Retrieving Daily Collection Report.');
    $date = new DateTime();
    $collections = CRM_Smartdebit_Auddis::getSmartdebitCollectionReport($date->format('Y-m-d'));
    if (!isset($collections['error'])) {
      CRM_Smartdebit_Auddis::saveSmartdebitCollectionReport($collections);
    }
    CRM_Smartdebit_Auddis::removeOldSmartdebitCollectionReports();

    CRM_Core_Error::debug_log_message('Smartdebit Sync: Retrieving Smart Debit Payer Contact Details.');
    // Get list of payers from Smartdebit
    $smartDebitPayerContacts = CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails();
    if (empty($smartDebitPayerContacts))
      return FALSE;

    // Update mandates table for reconciliation functions
    CRM_Smartdebit_Sync::updateSmartDebitMandatesTable($smartDebitPayerContacts);

    // Save total count
    $count = count($smartDebitPayerContacts);
    smartdebit_civicrm_saveSetting('total', $count);

    // Clear out the results table
    $emptySql = "TRUNCATE TABLE veda_smartdebit_success_contributions";
    CRM_Core_DAO::executeQuery($emptySql);

    // Set the Number of Rounds
    $rounds = ceil($count/self::BATCH_COUNT);
    // Setup a Task in the Queue
    $i = 0;
    while ($i < $rounds) {
      $start   = $i * self::BATCH_COUNT;
      $smartDebitPayerContactsBatch  = array_slice($smartDebitPayerContacts, $start, self::BATCH_COUNT, TRUE);
      $counter = ($rounds > 1) ? ($start + self::BATCH_COUNT) : $count;
      if ($counter > $count) $counter = $count;
      $task    = new CRM_Queue_Task(
        array('CRM_Smartdebit_Sync', 'syncSmartdebitCollectionReports'),
        array($smartDebitPayerContactsBatch),
        "Syncing smart debit collection reports - contacts {$counter} of {$count}"
      );

      // Add the Task to the Queue
      $queue->createItem($task);
      $i++;
    }

    CRM_Core_Error::debug_log_message('Smartdebit Sync: Retrieving AUDDIS reports.');
    // Get auddis/arudd IDs for last month if none specified.
    $auddisProcessor = new CRM_Smartdebit_Auddis();

    if (!isset($auddisIDs)) {
      // Get list of auddis records from smart debit
      if ($auddisProcessor->getSmartdebitAuddisList()) {
        // Get list of auddis dates, convert them to IDs
        if ($auddisProcessor->getAuddisDates()) {
          $auddisIDs = $auddisProcessor->getAuddisIdsForProcessing($auddisProcessor->getAuddisDatesList());
          $task = new CRM_Queue_Task(
            array('CRM_Smartdebit_Sync', 'syncSmartdebitAuddis'),
            array($auddisIDs),
            "Syncing smart debit AUDDIS reports"
          );
          $queue->createItem($task);
        }
      }
    }

    CRM_Core_Error::debug_log_message('Smartdebit Sync: Retrieving ARUDD reports.');
    if (!isset($aruddIDs)) {
      // Get list of auddis records from smart debit
      if ($auddisProcessor->getSmartdebitAruddList()) {
        // Get list of auddis dates, convert them to IDs
        if ($auddisProcessor->getAruddDates()) {
          $aruddIDs = $auddisProcessor->getAruddIDsForProcessing($auddisProcessor->getAruddDatesList());
          $task = new CRM_Queue_Task(
            array('CRM_Smartdebit_Sync', 'syncSmartdebitArudd'),
            array($aruddIDs),
            "Syncing smart debit ARUDD reports"
          );
          $queue->createItem($task);
        }
      }
    }

    if (!empty($smartDebitPayerContacts)) {
      // Setup the Runner
      $runnerParams = array(
        'title' => ts('Import From Smart Debit'),
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
      );
      if ($interactive) {
        $runnerParams['onEndUrl'] = CRM_Utils_System::url(self::END_URL, self::END_PARAMS, TRUE, NULL, FALSE);
      }
      $runner = new CRM_Queue_Runner($runnerParams);

      return $runner;
    }
    return FALSE;
  }

  /**
   * Sync the AUDDIS records with contacts
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param $smartDebitAuddisIds
   */
  static function syncSmartdebitAuddis(CRM_Queue_TaskContext $ctx, $smartDebitAuddisIds)
  {
    // Add contributions for rejected payments with the status of 'failed'
    // Reset the counter when sync starts
    smartdebit_civicrm_saveSetting('rejected_auddis', NULL);
    // Rejected Ids is used to display on confirm form, would be nice to tidy and have it's own table or something
    $rejectedIds = array();

    // Retrieve AUDDIS files from Smartdebit
    if ($smartDebitAuddisIds) {
      // Find the relevant auddis file
      foreach ($smartDebitAuddisIds as $auddisId) {
        // Process AUDDIS files
        $auddisFile = CRM_Smartdebit_Auddis::getSmartdebitAuddisFile($auddisId);
        $auddisDate = $auddisFile['auddis_date'];
        unset($auddisFile['auddis_date']);
        $refKey = 'reference';
        $dateKey = 'effective-date';
        $rejectedIds = array_merge($rejectedIds, CRM_Smartdebit_Sync::processAuddisFile($auddisId, $auddisFile, $refKey, $dateKey));
      }
    }
    smartdebit_civicrm_saveSetting('rejected_auddis', $rejectedIds);
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Sync the ARUDD records with contacts
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param $smartDebitAruddIds
   *
   * @return int
   */
  static function syncSmartdebitArudd (CRM_Queue_TaskContext $ctx, $smartDebitAruddIds) {
    // Add contributions for rejected payments with the status of 'failed'
    $rejectedIds = array();
    // Reset the counter when sync starts
    smartdebit_civicrm_saveSetting('rejected_arudd', NULL);

    // Retrieve ARUDD files from Smartdebit
    if($smartDebitAruddIds) {
      foreach ($smartDebitAruddIds as $aruddId) {
        // Process ARUDD files
        $aruddFile = CRM_Smartdebit_Auddis::getSmartdebitAruddFile($aruddId);
        $aruddDate = $aruddFile['arudd_date'];
        unset($aruddFile['arudd_date']);
        $refKey = 'ref';
        $dateKey = 'originalProcessingDate';
        $rejectedIds = array_merge($rejectedIds, CRM_Smartdebit_Sync::processAuddisFile($aruddId, $aruddFile, $refKey, $dateKey));
      }
    }
    smartdebit_civicrm_saveSetting('rejected_arudd', $rejectedIds);

    CRM_Core_Error::debug_log_message('Smartdebit: Sync Job End.');
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Synchronise smart debit payments with CiviCRM
   * We only create new contributions here, anything else has to be done manually using reconciliation
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param $smartDebitPayerContacts
   * @return int
   */
  static function syncSmartdebitCollectionReports(CRM_Queue_TaskContext $ctx, $smartDebitPayerContacts)
  {
    // Import each transaction from smart debit
    foreach ($smartDebitPayerContacts as $key => $sdContact) {
      try {
        $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', array(
          'trxn_id' => $sdContact['reference_number'],
        ));
      } catch (Exception $e) {
        CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: Not Matched=' . $sdContact['reference_number']);
        continue;
      }

      if (self::DEBUG) { CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: Matched=' . $sdContact['reference_number']); }

      // Get transaction details from collection report
      $selectQuery = "SELECT `receive_date` as receive_date, `amount` as amount 
                      FROM `veda_smartdebit_collectionreports` 
                      WHERE `transaction_id` = %1";
      $params = array(1 => array($sdContact['reference_number'], 'String'));
      $daoCollectionReport = CRM_Core_DAO::executeQuery($selectQuery, $params);
      if (!$daoCollectionReport->fetch()) {
        CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: No collection report for ' . $sdContact['reference_number']);
        continue;
      }

      // Smart debit charge file has dates in UK format
      // UK dates (eg. 27/05/1990) won't work with strtotime, even with timezone properly set.
      // However, if you just replace "/" with "-" it will work fine.
      $receiveDate = date('Y-m-d', strtotime(str_replace('/', '-', $daoCollectionReport->receive_date)));

      $contributeParams =
        array(
          'version' => 3,
          'contact_id' => $contributionRecur['contact_id'],
          'contribution_recur_id' => $contributionRecur['id'],
          'total_amount' => $daoCollectionReport->amount,
          'invoice_id' => md5(uniqid(rand(), TRUE)),
          'trxn_id' => $sdContact['reference_number'] . '/' . CRM_Utils_Date::processDate($receiveDate),
          'financial_type_id' => $contributionRecur['financial_type_id'],
          'payment_instrument_id' => $contributionRecur['payment_instrument_id'],
          'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
          'source' => 'Smart Debit Import',
          'receive_date' => CRM_Utils_Date::processDate($receiveDate),
        );

      // Check if the contribution is first payment
      // if yes, update the contribution instead of creating one
      // as CiviCRM should have created the first contribution
      $contributeParams = self::checkIfFirstPayment($contributeParams, $contributionRecur['frequency_unit'], $contributionRecur['frequency_interval']);

      // Allow params to be modified via hook
      CRM_Smartdebit_Utils_Hook::alterSmartdebitContributionParams($contributeParams);

      $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);

      if (self::DEBUG) { CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: $contributeResult=' . print_r($contributeResult, true)); }

      if (empty($contributeResult['is_error'])) {
        // Get recurring contribution ID
        // get contact display name to display in result screen
        $contactParams = array('version' => 3, 'id' => $contributionRecur['contact_id']);
        $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

        // Update Recurring contribution to "In Progress"
        $contributionRecur['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
        CRM_Smartdebit_Base::createRecurContribution($contributionRecur);

        // Store the results in veda_smartdebit_success_contributions table
        $keepSuccessResultsSQL = "
          INSERT INTO `veda_smartdebit_success_contributions`(
            `transaction_id`,
            `contribution_id`,
            `contact_id`,
            `contact`,
            `amount`,
            `frequency`
            )
          VALUES (%1,%2,%3,%4,%5,%6)
        ";
        $keepSuccessResultsParams = array(
          1 => array($sdContact['reference_number'], 'String'),
          2 => array($contributeResult['id'], 'Integer'),
          3 => array($contactResult['id'], 'Integer'),
          4 => array($contactResult['display_name'], 'String'),
          5 => array($daoCollectionReport->amount, 'String'),
          6 => array($contributionRecur['frequency_interval'] . ' ' . $contributionRecur['frequency_unit'], 'String'),
        );
        CRM_Core_DAO::executeQuery($keepSuccessResultsSQL, $keepSuccessResultsParams);
      }
    }
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * This function is used to process Auddis and Arudd records from an Auddis/Arudd file
   *
   * @param $auddisId
   * @param $auddisFile
   * @param $refKey
   * @param $dateKey
   * @return array
   */
  static function processAuddisFile($auddisId, $auddisFile, $refKey, $dateKey) {
    $errors = FALSE;
    $rejectedIds = array();

    // Process each record in the auddis file
    foreach ($auddisFile as $key => $value) {
      if (!isset($value[$refKey]) || !isset($value[$dateKey])) {
        CRM_Core_Error::debug_log_message('Smartdebit processAuddis. Id=' . $auddisId . '. Malformed Auddis/Arudd record from Smartdebit.');
        continue;
      }

      $sql = "
            SELECT ctrc.id contribution_recur_id ,ctrc.contact_id , cont.display_name ,ctrc.start_date , ctrc.amount, ctrc.trxn_id , ctrc.frequency_unit, ctrc.payment_instrument_id, ctrc.financial_type_id
            FROM civicrm_contribution_recur ctrc
            INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
            WHERE ctrc.trxn_id = %1";

      $params = array(1 => array($value[$refKey], 'String'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);

      // Contribution receive date is "now"
      /*$receiveDate = new DateTime();
      $receiveDateString = $receiveDate->format('YmdHis');*/
      $receiveDateString = date('YmdHis', strtotime($value[$dateKey]. ' 00:00:00'));
      $trxnId = $value[$refKey] . '/' . $receiveDateString;

      if ($dao->fetch()) {
        $contributeParams = array(
          'version' => 3,
          'contact_id' => $dao->contact_id,
          'contribution_recur_id' => $dao->contribution_recur_id,
          'total_amount' => $dao->amount,
          'trxn_id' => $trxnId,
          'financial_type_id' => $dao->financial_type_id,
          'payment_instrument_id' => $dao->payment_instrument_id,
          'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed'),
          'receive_date' => $value[$dateKey],
        );

        // Look for an existing contribution and update existing if available
        try {
          $existingContribution = civicrm_api3('Contribution', 'getsingle', array(
            'return' => array("id"),
            'trxn_id' => $trxnId,
          ));
          if (!empty($existingContribution['id'])) {
            $contributeParams['id'] = $existingContribution['id'];
          }
        }
        catch (Exception $e) {
          // No existing contribution as expected, continue.
          $contributeParams['source'] = 'Smart Debit Import';
          $contributeParams['invoice_id'] = md5(uniqid(rand(), TRUE));
        }

        // Allow params to be modified via hook
        CRM_Smartdebit_Utils_Hook::alterSmartdebitContributionParams($contributeParams);
        $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);

        if (!$contributeResult['is_error']) {
          $contributionId = $contributeResult['id'];
          // get contact display name to display in result screen
          $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionId]['contact_id']);
          $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

          $rejectedIds[$contributionId] = array('cid' => $contributeResult['values'][$contributionId]['contact_id'],
            'id' => $contributionId,
            'display_name' => $contactResult['display_name'],
            'total_amount' => CRM_Utils_Money::format($contributeResult['values'][$contributionId]['total_amount']),
            'trxn_id' => $value[$refKey],
            'status' => $contributeResult['label'],
          );

          // Allow auddis rejected contribution to be handled by hook
          CRM_Smartdebit_Utils_Hook::handleAuddisRejectedContribution($contributionId);
        }
      }
      else {
        CRM_Core_Error::debug_log_message('Smartdebit processAuddis: ' . $value[$refKey] . ' NOT matched to contribution in CiviCRM - try reconciliation.');
        $errors = TRUE;
      }
      if (!$errors) {
        // Mark auddis as processed if we actually found a matching contribution
        CRM_Smartdebit_Auddis::setAuddisRecordProcessed($auddisId);
      }
    }
    return $rejectedIds;
  }

  /**
   * Function to check if the contribution is first contribution
   * for the recurring contribution record
   *
   * @param $params
   * @param string $frequencyUnit
   * @param int $frequencyInterval
   * @return bool|array
   */
  static function checkIfFirstPayment($params, $frequencyUnit = 'year', $frequencyInterval = 1) {
    if (empty($params['contribution_recur_id'])) {
      return false;
    }

    // Get days difference to determine if this is first payment
    $days = CRM_Smartdebit_Sync::daysDifferenceForFrequency($frequencyUnit, $frequencyInterval);

    $contributionResult = civicrm_api3('Contribution', 'get', array(
      'contribution_recur_id' => $params['contribution_recur_id'],
    ));

    // We have only one contribution for the recurring record
    if ($contributionResult['count'] == 1) {
      $contributionDetails = $contributionResult['values'][$contributionResult['id']];

      if (!empty($contributionDetails['receive_date']) && !empty($params['receive_date'])) {
        // Find the date difference between the contribution date and new collection date
        $dateDiff = CRM_Smartdebit_Sync::dateDifference($params['receive_date'], $contributionDetails['receive_date']);

        // if diff is less than set number of days, return Contribution ID to update the contribution
        // If $days == 0 it's a lifetime membership
        if (($dateDiff < $days) && ($days != 0)) {
          $params['id'] = $contributionResult['id'];
          unset($params['source']);
        }
      }
    }
    // Get the recent pending contribution if there is more than 1 payment for the recurring record
    else if ($contributionResult['count'] > 1) {
      $sqlParams = array(
        1 => array($params['contribution_recur_id'], 'Integer'),
      );
      $sql = "SELECT cc.id, cc.receive_date FROM civicrm_contribution cc WHERE cc.contribution_recur_id = %1 ORDER BY cc.receive_date DESC";
      $dao = CRM_Core_DAO::executeQuery($sql , $sqlParams);
      while($dao->fetch()) {
        if (!empty($dao->receive_date) && !empty($params['receive_date'])) {
          $dateDiff = CRM_Smartdebit_Sync::dateDifference($params['receive_date'], $dao->receive_date);

          // if diff is less than set number of days, return Contribution ID to update the contribution
          if ($dateDiff < $days) {
            $params['id'] = $dao->id;
            unset($params['source']);
          }
        }
      }
    }
    return $params;
  }

  /**
   * Return difference between two dates in format
   * @param $date_1
   * @param $date_2
   * @param string $differenceFormat
   * @return string
   */
  static function dateDifference($date_1, $date_2, $differenceFormat = '%a')
  {
    $datetime1 = date_create($date_1);
    $datetime2 = date_create($date_2);

    $interval = date_diff($datetime1, $datetime2);

    return $interval->format($differenceFormat);

  }

  /**
   * Function to return number of days difference to check between current date
   * and payment date to determine if this is first payment or not
   *
   * @param $frequencyUnit
   * @param $frequencyInterval
   * @return int
   */
  static function daysDifferenceForFrequency($frequencyUnit, $frequencyInterval) {
    switch ($frequencyUnit) {
      case 'day':
        $days = $frequencyInterval * 1;
      case 'month':
        $days = $frequencyInterval * 7;
        break;
      case 'year':
        $days = $frequencyInterval * 30;
        break;
      case 'lifetime':
        $days = 0;
        break;
      default:
        $days = 30;
        break;
    }
    return $days;
  }

  /**
   * Retrieve Payer Contact Details from Smartdebit
   * Called during daily sync job
   * @param null $referenceNumber
   * @return array|bool
   */
  static function getSmartdebitPayerContactDetails($referenceNumber = NULL)
  {
    $userDetails = CRM_Smartdebit_Auddis::getSmartdebitUserDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    // Send payment POST to the target URL
    $url = CRM_Smartdebit_Base::getApiUrl('/api/data/dump', "query[service_user][pslid]="
      .urlencode($pslid)."&query[report_format]=XML");

    // Restrict to a single payer if we have a reference
    if ($referenceNumber) {
      $url .= "&query[reference_number]=".urlencode($referenceNumber);
    }
    $response = CRM_Smartdebit_Base::requestPost($url, '', $username, $password, '');

    // Take action based upon the response status
    switch (strtoupper($response["Status"])) {
      case 'OK':
        $smartDebitArray = array();

        // Cater for a single response
        if (isset($response['Data']['PayerDetails']['@attributes'])) {
          $smartDebitArray[] = $response['Data']['PayerDetails']['@attributes'];
        } else {
          foreach ($response['Data']['PayerDetails'] as $key => $value) {
            $smartDebitArray[] = $value['@attributes'];
          }
        }
        return $smartDebitArray;
      default:
        if (isset($response['error'])) {
          $msg = $response['error'];
        }
        $msg .= 'Invalid reference number: ' . $referenceNumber;
        CRM_Core_Session::setStatus(ts($msg), 'Smart Debit', 'error');
        CRM_Core_Error::debug_log_message('Smart Debit: getSmartdebitPayments Error: ' . $msg);
        return false;
    }
  }

  /**
   * Update Smartdebit Mandates in table veda_smartdebit_mandates for further analysis
   * This table is only used by Reconciliation functions
   *
   * @param $smartDebitPayerContactDetails (array of smart debit contact details : call CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails())
   * @return bool|int
   */
  static function updateSmartDebitMandatesTable($smartDebitPayerContactDetails) {
    // if the civicrm_sd table exists, then empty it
    $emptySql = "TRUNCATE TABLE `veda_smartdebit_mandates`";
    CRM_Core_DAO::executeQuery($emptySql);

    // Get payer contact details
    if (empty($smartDebitPayerContactDetails)) {
      return FALSE;
    }
    // Insert mandates into table
    foreach ($smartDebitPayerContactDetails as $key => $smartDebitRecord) {
      $sql = "INSERT INTO `veda_smartdebit_mandates`(
            `title`,
            `first_name`,
            `last_name`, 
            `email_address`,
            `address_1`, 
            `address_2`, 
            `address_3`, 
            `town`, 
            `county`,
            `postcode`,
            `first_amount`,
            `regular_amount`,
            `frequency_type`,
            `frequency_factor`,
            `start_date`,
            `current_state`,
            `reference_number`,
            `payerReference`
            ) 
            VALUES (%1,%2,%3,%4,%5,%6,%7,%8,%9,%10,%11,%12,%13,%14,%15,%16,%17,%18)";
      $params = array(
        1 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'title', 'NULL'), 'String' ),
        2 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'first_name', 'NULL'), 'String' ),
        3 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'last_name', 'NULL'), 'String' ),
        4 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'email_address', 'NULL'),  'String'),
        5 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'address_1', 'NULL'), 'String' ),
        6 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'address_2', 'NULL'), 'String' ),
        7 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'address_3', 'NULL'), 'String' ),
        8 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'town', 'NULL'), 'String' ),
        9 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'county', 'NULL'), 'String' ),
        10 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'postcode', 'NULL'), 'String' ),
        11 => array( CRM_Smartdebit_Utils::getCleanSmartdebitAmount(CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'first_amount', 'NULL')), 'String' ),
        12 => array( CRM_Smartdebit_Utils::getCleanSmartdebitAmount(CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'regular_amount', 'NULL')), 'String' ),
        13 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'frequency_type', 'NULL'), 'String' ),
        14 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'frequency_factor', 'NULL'), 'Int' ),
        15 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'start_date', 'NULL'), 'String' ),
        16 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'current_state', 'NULL'), 'Int' ),
        17 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'reference_number', 'NULL'), 'String' ),
        18 => array( CRM_Smartdebit_Utils::getArrayFieldValue($smartDebitRecord, 'payerReference', 'NULL'), 'String' ),
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    $mandateFetchedCount = count($smartDebitPayerContactDetails);
    return $mandateFetchedCount;
  }
}
