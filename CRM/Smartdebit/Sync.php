<?php

class CRM_Smartdebit_Sync
{
  const QUEUE_NAME = 'sm-pull';
  const END_URL = 'civicrm/smartdebit/syncsd/confirm';
  const END_PARAMS = 'state=done';
  const BATCH_COUNT = 10;

  /**
   * If $auddisIDs and $aruddIDs are not set no AUDDIS/ARUDD records will be processed.
   * Currently you can only import AUDDIS/ARUDD happen via the UI import.
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

    $count = count($smartDebitPayerContacts);

    smartdebit_civicrm_saveSetting('total', $count);

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
        array('CRM_Smartdebit_Sync', 'syncSmartdebitRecords'),
        array($smartDebitPayerContactsBatch),
        "Pulling smart debit - Contacts {$counter} of {$count}"
      );

      // Add the Task to the Queue
      $queue->createItem($task);
      $i++;
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

      // Reset the counter when sync starts
      smartdebit_civicrm_saveSetting('rejected_ids', NULL);

      // Add contributions for rejected payments with the status of 'failed'
      $ids = array();

      // Retrieve AUDDIS files from Smartdebit
      if($auddisIDs) {
        // Find the relevant auddis file
        foreach ($auddisIDs as $auddisID) {
          $auddisFiles[] = CRM_Smartdebit_Auddis::getSmartdebitAuddisFile($auddisID);
        }
        // Process AUDDIS files
        foreach ($auddisFiles as $auddisFile) {
          $auddisDate = $auddisFile['auddis_date'];
          unset($auddisFile['auddis_date']);
          foreach ($auddisFile as $key => $value) {

            $sql = "
            SELECT ctrc.id contribution_recur_id ,ctrc.contact_id , cont.display_name ,ctrc.start_date , ctrc.amount, ctrc.trxn_id , ctrc.frequency_unit, ctrc.payment_instrument_id, ctrc.financial_type_id
            FROM civicrm_contribution_recur ctrc
            INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
            WHERE ctrc.trxn_id = %1";

            $params = array( 1 => array( $value['reference'], 'String' ) );
            $dao = CRM_Core_DAO::executeQuery( $sql, $params);

            // Contribution receive date is "now"
            $receiveDate = new DateTime();
            $receiveDateString = $receiveDate->format('YmdHis');

            if ($dao->fetch()) {
              $contributeParams =
                array(
                  'version'                => 3,
                  'contact_id'             => $dao->contact_id,
                  'contribution_recur_id'  => $dao->contribution_recur_id,
                  'total_amount'           => $dao->amount,
                  'invoice_id'             => md5(uniqid(rand(), TRUE )),
                  'trxn_id'                => $value['reference'].'/'.$receiveDateString,
                  'financial_type_id'      => $dao->financial_type_id,
                  'payment_instrument_id'  => $dao->payment_instrument_id,
                  'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed'),
                  'source'                 => 'Smart Debit Import',
                  'receive_date'           => $value['effective-date'],
                );

              // Allow params to be modified via hook
              CRM_Smartdebit_Utils_Hook::alterSmartdebitContributionParams( $contributeParams );
              $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);

              if(!$contributeResult['is_error']) {
                $contributionID = $contributeResult['id'];
                // get contact display name to display in result screen
                $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionID]['contact_id']);
                $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

                $ids[$contributionID] = array('cid' => $contributeResult['values'][$contributionID]['contact_id'],
                  'id' => $contributionID,
                  'display_name' => $contactResult['display_name'],
                  'total_amount' => CRM_Utils_Money::format($contributeResult['values'][$contributionID]['total_amount']),
                  'trxn_id'      => $value['reference'],
                  'status'       => $contributeResult['label'],
                );

                // Allow auddis rejected contribution to be handled by hook
                CRM_Smartdebit_Utils_Hook::handleAuddisRejectedContribution($contributionID);
              }
            }
          }
          // Create activity now we've processed auddis
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'activity_type_id' => 6,
            'subject' => 'SmartdebitAUDDIS'.$auddisDate,
            'details' => 'Sync had been processed already for this date '.$auddisDate,
          );
          $result = civicrm_api('Activity', 'create', $params);
        }
      }


      // Add contributions for rejected payments with the status of 'failed'
      /*
       * [@attributes] => Array
                                                                (
                                                                    [ref] => 12345689
                                                                    [transCode] => 01
                                                                    [returnCode] => 0
                                                                    [payerReference] => 268855
                                                                    [returnDescription] => REFER TO PAYER
                                                                    [originalProcessingDate] => 2016-03-14
                                                                    [currency] => GBP
                                                                    [valueOf] => 10.50
                                                                )
       */
      // Retrieve ARUDD files from Smartdebit
      if($aruddIDs) {
        foreach ($aruddIDs as $aruddID) {
          $aruddFiles[] = CRM_Smartdebit_Auddis::getSmartdebitAruddFile($aruddID);
        }
        // Process ARUDD files
        foreach ($aruddFiles as $aruddFile) {
          $aruddDate = $aruddFile['arudd_date'];
          unset($aruddFile['arudd_date']);
          foreach ($aruddFile as $key => $value) {
            $sql = "
            SELECT ctrc.id contribution_recur_id ,ctrc.contact_id , cont.display_name ,ctrc.start_date , ctrc.amount, ctrc.trxn_id , ctrc.frequency_unit, ctrc.payment_instrument_id, ctrc.financial_type_id
            FROM civicrm_contribution_recur ctrc
            INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
            WHERE ctrc.trxn_id = %1";

            $params = array( 1 => array( $value['ref'], 'String' ) );
            $dao = CRM_Core_DAO::executeQuery( $sql, $params);

            if ($dao->fetch()) {
              $contributeParams =
                array(
                  'version'                => 3,
                  'contact_id'             => $dao->contact_id,
                  'contribution_recur_id'  => $dao->contribution_recur_id,
                  'total_amount'           => $dao->amount,
                  'invoice_id'             => md5(uniqid(rand(), TRUE )),
                  'trxn_id'                => $value['ref'].'/'.CRM_Utils_Date::processDate($receiveDate),
                  'financial_type_id'      => $dao->financial_type_id,
                  'payment_instrument_id'  => $dao->payment_instrument_id,
                  'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed'),
                  'source'                 => 'Smart Debit Import',
                  'receive_date'           => $value['originalProcessingDate'],
                );

              // Allow params to be modified via hook
              CRM_Smartdebit_Utils_Hook::alterSmartdebitContributionParams( $contributeParams );

              $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);

              if(!$contributeResult['is_error']) {
                $contributionID   = $contributeResult['id'];
                // get contact display name to display in result screen
                $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionID]['contact_id']);
                $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

                $ids[$contributionID] = array('cid' => $contributeResult['values'][$contributionID]['contact_id'],
                  'id' => $contributionID,
                  'display_name' => $contactResult['display_name'],
                  'total_amount' => CRM_Utils_Money::format($contributeResult['values'][$contributionID]['total_amount']),
                  'trxn_id'      => $value['ref'],
                  'status'       => $contributeResult['label'],
                );

                // Allow auddis rejected contribution to be handled by hook
                CRM_Smartdebit_Utils_Hook::handleAuddisRejectedContribution( $contributionID );
              }
            }
          }
          // Create activity now we've processed auddis
          $params = array(
            'version' => 3,
            'sequential' => 1,
            'activity_type_id' => 6,
            'subject' => 'SmartdebitARUDD'.$aruddDate,
            'details' => 'Sync had been processed already for this date '.$aruddDate,
          );
          $result = civicrm_api('Activity', 'create', $params);
        }
      }

      smartdebit_civicrm_saveSetting('rejected_ids', $ids);
      return $runner;
    }
    return FALSE;
  }

  static function syncSmartdebitRecords(CRM_Queue_TaskContext $ctx, $smartDebitPayerContacts) {

    // Clear out the results table
    $emptySql = "TRUNCATE TABLE veda_smartdebit_import_success_contributions";
    CRM_Core_DAO::executeQuery($emptySql);

    // Initialise variables
    $ids = array();

    // Import each transaction from smart debit
    foreach ($smartDebitPayerContacts as $key => $sdContact) {
      // TODO: Update this
      // Get recurring contribution details from CiviCRM
      $sql = "
        SELECT ctrc.id contribution_recur_id ,ctrc.contact_id , cont.display_name ,ctrc.start_date , ctrc.amount, ctrc.trxn_id , ctrc.frequency_unit, ctrc.frequency_interval, ctrc.payment_instrument_id, ctrc.financial_type_id
        FROM civicrm_contribution_recur ctrc
        INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
        WHERE ctrc.trxn_id = %1";
      $params = array( 1 => array($sdContact['reference_number'], 'String'));
      $daoContributionRecur = CRM_Core_DAO::executeQuery( $sql, $params);

      // Get transaction details from collection report
      $selectQuery = "SELECT `receive_date` as receive_date, `amount` as amount 
                      FROM `veda_smartdebit_import` 
                      WHERE `transaction_id` = %1";
      $daoCollectionReport = CRM_Core_DAO::executeQuery($selectQuery, $params);
      $daoCollectionReport->fetch();

      // Smart debit charge file has dates in UK format
      // UK dates (eg. 27/05/1990) won't work with strtotime, even with timezone properly set.
      // However, if you just replace "/" with "-" it will work fine.
      $receiveDate = date('Y-m-d', strtotime(str_replace('/', '-', $daoCollectionReport->receive_date)));

      $fred = 1; //DEBUG temp to disable add contribution
      // If we matched the transaction ID to a recurring contribution process it
      if ($daoContributionRecur->fetch()) {
        CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: Matched=' . $sdContact['reference_number']);
      } elseif ($fred == 4) { // DEBUG temp to disable add contribution
        $contributeParams =
          array(
            'version'                => 3,
            'contact_id'             => $daoContributionRecur->contact_id,
            'contribution_recur_id'  => $daoContributionRecur->contribution_recur_id,
            'total_amount'           => $daoCollectionReport->amount,
            'invoice_id'             => md5(uniqid(rand(), TRUE )),
            'trxn_id'                => $sdContact['reference_number'].'/'.CRM_Utils_Date::processDate($receiveDate),
            'financial_type_id'      => $daoContributionRecur->financial_type_id,
            'payment_instrument_id'  => $daoContributionRecur->payment_instrument_id,
            'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
            'source'                 => 'Smart Debit Import',
            'receive_date'           => CRM_Utils_Date::processDate($receiveDate),
          );

        // Check if the contribution is first payment
        // if yes, update the contribution instead of creating one
        // as CiviCRM should have created the first contribution
        $contributeParams = self::checkIfFirstPayment($contributeParams, $daoContributionRecur->frequency_unit, $daoContributionRecur->frequency_interval);

        // Allow params to be modified via hook
        CRM_Smartdebit_Utils_Hook::alterSmartdebitContributionParams($contributeParams);

        $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);
        $membershipRenew = 0;
        CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: $contributeResult='.print_r($contributeResult)); //DEBUG

        if(!$contributeResult['is_error']) {
          CRM_Core_Error::debug_log_message('syncSmartdebitRecords: Created contribution success'); //DEBUG
          // Get recurring contribution ID
          $contributionID   = $contributeResult['id'];
          $contributionRecurID     = $contributeResult['values'][$contributionID]['contribution_recur_id'];
          // Get membership ID for recurring contribution
          $membershipRecord = civicrm_api3('Membership', 'get', array(
            'sequential' => 1,
            'return' => array("id", "end_date", "status_id"),
            'contribution_recur_id' => $contributionRecurID,
          ));
          if (isset($membershipRecord['id'])) {
            $membershipID = $membershipRecord['id'];
          }

          CRM_Core_Error::debug_log_message('membershipID = '. $membershipID); //DEBUG
          if (!empty($membershipID)) {
            // Get membership dates
            if (isset($membershipRecord['values'][0]['end_date'])) {
              $membershipEndDate = $membershipRecord['values'][0]['end_date'];
            }
            else {
              // Membership is probably pending so we can't do anything here
              // We shouldn't get here because the completed contribution should renew the membership
            }

            // Create membership payment
            self::createMembershipPayment($membershipID, $contributionID);

            // Get recurring contribution details
            $contributionRecur = civicrm_api("ContributionRecur","get", array ('version' => '3', 'id' => $contributionRecurID));
            if (isset($contributionRecur['values'][$contributionRecurID]['frequency_unit'])) {
              $frequencyUnit = $contributionRecur['values'][$contributionRecurID]['frequency_unit'];
              $frequencyInterval = $contributionRecur['values'][$contributionRecurID]['frequency_interval'];
            }
            else {
              CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: FrequencyUnit/Interval not defined for recurring contribution='.$contributionRecurID);
              // Membership won't be renewed as we don't know the renewal frequency
            }

            // FIXME: What do we do if we don't have an end date? Will it get created for us when membership payment is made?
            $membershipRenewStartDate = $membershipEndDate;
            $membershipRenewEndDate = date("Y-m-d", strtotime($membershipEndDate));

            // Renew the membership if we have a renewal frequency
            if (isset($frequencyUnit)) {
              // Increase new membership end date by one period
              $membershipRenewEndDate = date("Y-m-d",strtotime(date("Y-m-d", strtotime($membershipEndDate)) . " +$frequencyInterval $frequencyUnit"));

              $membershipParams = array ( 'version'       => '3',
                'membership_id' => $membershipID,
                'id'            => $membershipID,
                'end_date'      => $membershipRenewEndDate,
              );

              // Set a flag to be sent to hook, so that membership renewal can be skipped
              $membershipParams['renew'] = 1;

              // Allow membership update params to be modified via hook
              CRM_Smartdebit_Utils_Hook::handleSmartdebitMembershipRenewal($membershipParams);

              // Membership renewal may be skipped in hook by setting 'renew' = 0
              if ($membershipParams['renew'] == 1) {
                // remove the renew key from params array, which need to be passed to API
                $membershipRenew = $membershipParams['renew'];
                unset($membershipParams['renew']);
                // Update/Renew the membership
                //FIXME: Do we also need to change the membership status?
                $updatedMember = civicrm_api("Membership", "create", $membershipParams);
              }
            }
          }
          // get contact display name to display in result screen
          $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionID]['contact_id']);
          $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

          $ids[$contributionID]= array('cid' => $contributeResult['values'][$contributionID]['contact_id'],
            'id'  => $contributionID,
            'display_name'  => $contactResult['display_name'],
          );

          // Store the results in veda_smartdebit_import_success_contributions table
          $keepSuccessResultsSQL = "
            INSERT Into veda_smartdebit_import_success_contributions
            ( `transaction_id`, `contribution_id`, `contact_id`, `contact`, `amount`, `frequency`, `is_membership_renew`, `membershipRenewStartDate`, `membership_renew_to` )
            VALUES ( %1, %2, %3, %4, %5, %6, %7, %8, %9 )
          ";
          $keepSuccessResultsParams = array(
            1 => array( $sdContact, 'String'),
            2 => array( $contributionID, 'Integer'),
            3 => array( $contactResult['id'], 'Integer'),
            4 => array( $contactResult['display_name'], 'String'),
            5 => array( $contributeResult['values'][$contributionID]['total_amount'], 'String'),
            6 => array( $frequencyInterval . ' ' . $frequencyUnit, 'String'),
            7 => array( $membershipRenew, 'Integer'),
            8 => array( $membershipRenewStartDate, 'String'),
            9 => array ($membershipRenewEndDate, 'String'),
          );
          CRM_Core_DAO::executeQuery($keepSuccessResultsSQL, $keepSuccessResultsParams);
        }
        else {
          // No membership ID so we don't do anything with membership
          CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: No Membership ID! contributeResult = '.print_r($contributeResult, TRUE)); //DEBUG
        }
      }
      else {
        CRM_Core_Error::debug_log_message('Smartdebit syncSmartdebitRecords: Not Matched='.$sdContact['reference_number']);
      }
    }
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Function to check if the contribution is first contribution
   * for the recurring contribution record
   *
   * @param $params
   * @param string $frequencyUnit
   * @param int $frequencyInterval
   */
  static function checkIfFirstPayment($params, $frequencyUnit = 'year', $frequencyInterval = 1) {
    if (empty($params['contribution_recur_id'])) {
      return;
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
        $dateDiff = CRM_Smartdebit_Sync::getDateDifference($params['receive_date'], $contributionDetails['receive_date']);

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
          $dateDiff = CRM_Smartdebit_Sync::getDateDifference($params['receive_date'], $dao->receive_date);

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
   * Link Membership ID with Contribution ID
   * @param $membershipId
   * @param $contributionId
   */
  function createMembershipPayment($membershipId, $contributionId) {
    if (empty($membershipId) || empty($contributionId)) {
      return;
    }

    // Check if membership payment already exist for the contribution
    $params = array(
      'version' => 3,
      'membership_id' => $membershipId,
      'contribution_id' => $contributionId,
    );
    $membershipPayment = civicrm_api('MembershipPayment', 'get', $params);

    // Create if the membership payment not exists
    if ($membershipPayment['count'] == 0) {
      $membershipPayment = civicrm_api('MembershipPayment', 'create', $params);
    }
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
}
