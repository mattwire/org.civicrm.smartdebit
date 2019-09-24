<?php
/**
 * https://civicrm.org/licensing
 */

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

  /**
   * If $auddisIDs and $aruddIDs are not set all available AUDDIS/ARUDD records will be processed.
   *
   * @param bool $interactive
   *    If TRUE, don't sync daily collectionreport (you should do this before calling, eg via manual sync), redirect after completion to show results
   * @param array $auddisIDs
   * @param array $aruddIDs
   *
   * @return \CRM_Queue_Runner
   */
  public static function getRunner($interactive=TRUE, $auddisIDs = NULL, $aruddIDs = NULL) {
    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create([
      'name'  => self::QUEUE_NAME,
      'type'  => 'Sql',
      'reset' => TRUE,
    ]);

    // Clear out the results table
    CRM_Smartdebit_SyncResults::delete();

    if (!$interactive) {
      // We only retrieve collection reports when running in unattended (ie. scheduled sync) mode.
      // Get collection reports
      // Do not call via queue, as we need the collection reports for the sync process queue
      CRM_Smartdebit_CollectionReports::retrieveDaily();
    }

    // Set the Number of Rounds
    // We need to set the rounds based on collection report and not mandates
    $count = CRM_Smartdebit_CollectionReports::count();
    $rounds = ceil($count/self::BATCH_COUNT);
    // Setup a Task in the Queue
    $i = 0;
    while ($i < $rounds) {
      $start = $i * self::BATCH_COUNT;
      $end = ($start + self::BATCH_COUNT);
      if ($end > $count) {
        $end = $count;
      }
      $task = new CRM_Queue_Task(
        ['CRM_Smartdebit_Sync', 'syncSmartdebitCollectionReports'],
        [$start, self::BATCH_COUNT],
        "Processed Smartdebit collections: {$start} to {$end} of {$count}"
      );

      // Add the Task to the Queue
      $queue->createItem($task);
      $i++;
    }

    // Get auddis/arudd IDs for last month if none specified.
    $auddisProcessor = new CRM_Smartdebit_Auddis();

    if (!isset($auddisIDs)) {
      Civi::log()->info('Smartdebit Sync: Retrieving AUDDIS reports.');
      // Get list of auddis records from smart debit
      if ($auddisProcessor->getSmartdebitAuddisList()) {
        // Get list of auddis dates, convert them to IDs
        if ($auddisProcessor->getAuddisDates()) {
          $auddisIDs = $auddisProcessor->getAuddisIdsForProcessing($auddisProcessor->getAuddisDatesList());
        }
      }
    }
    if (!empty($auddisIDs)) {
      $task = new CRM_Queue_Task(
        ['CRM_Smartdebit_Sync', 'syncSmartdebitAuddis'],
        [$auddisIDs],
        "Retrieved AUDDIS reports from Smartdebit"
      );
      $queue->createItem($task);
    }

    if (!isset($aruddIDs)) {
      Civi::log()->info('Smartdebit Sync: Retrieving ARUDD reports.');
      // Get list of auddis records from smart debit
      if ($auddisProcessor->getSmartdebitAruddList()) {
        // Get list of auddis dates, convert them to IDs
        if ($auddisProcessor->getAruddDates()) {
          $aruddIDs = $auddisProcessor->getAruddIDsForProcessing($auddisProcessor->getAruddDatesList());
        }
      }
    }
    if (!empty($aruddIDs)) {
      $task = new CRM_Queue_Task(
        ['CRM_Smartdebit_Sync', 'syncSmartdebitArudd'],
        [$aruddIDs],
        "Retrieved ARUDD reports from Smartdebit"
      );
      $queue->createItem($task);
    }

    $task = new CRM_Queue_Task(
      ['CRM_Smartdebit_CollectionReports', 'removeOld'],
      [],
      'Cleaned up'
    );
    $queue->createItem($task);

    // Setup the Runner
    $runnerParams = [
      'title' => ts('Import From Smart Debit'),
      'queue' => $queue,
      'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
    ];
    if ($interactive) {
      $runnerParams['onEndUrl'] = CRM_Utils_System::url(self::END_URL, self::END_PARAMS, TRUE, NULL, FALSE);
    }
    $runner = new CRM_Queue_Runner($runnerParams);

    return $runner;
  }

  /**
   * @param $runner
   */
  public static function runViaWeb($runner) {
    if ($runner) {
      // Run Everything in the Queue via the Web.
      $runner->runAllViaWeb();
    }
    else {
      CRM_Core_Session::setStatus(ts('No records were synchronised.'));
      $url = CRM_Utils_System::url(CRM_Smartdebit_Sync::END_URL, CRM_Smartdebit_Sync::END_PARAMS, TRUE, NULL, FALSE);
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Helper function for get mandates task
   * @param \CRM_Queue_TaskContext $ctx
   * @param $refresh
   * @param $onlyWithRecurId
   *
   * @return int
   * @throws \Exception
   */
  public static function getMandates(CRM_Queue_TaskContext $ctx, $refresh, $onlyWithRecurId) {
    $mandates = CRM_Smartdebit_Mandates::getAll($refresh, $onlyWithRecurId);
    if (empty($mandates)) {
      return CRM_Queue_Task::TASK_FAIL;
    }
    else {
      return CRM_Queue_Task::TASK_SUCCESS;
    }
  }

  /**
   * Sync the AUDDIS records with contacts
   * @param \CRM_Queue_TaskContext $ctx
   * @param $smartDebitAuddisIds
   *
   * @return int
   * @throws \Exception
   */
  public static function syncSmartdebitAuddis(CRM_Queue_TaskContext $ctx, $smartDebitAuddisIds) {
    // Add contributions for rejected payments with the status of 'failed'

    // Retrieve AUDDIS files from Smartdebit
    if ($smartDebitAuddisIds) {
      // Find the relevant AUDDIS file
      foreach ($smartDebitAuddisIds as $auddisId) {
        // Process AUDDIS files
        $auddisFile = CRM_Smartdebit_Api::getAuddisFile($auddisId);
        unset($auddisFile['auddis_date']);
        CRM_Smartdebit_Sync::processAuddisFile($auddisId, $auddisFile, CRM_Smartdebit_CollectionReports::TYPE_AUDDIS);
      }
    }

    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Sync the ARUDD records with contacts
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param $smartDebitAruddIds
   *
   * @return int
   * @throws \Exception
   */
  public static function syncSmartdebitArudd(CRM_Queue_TaskContext $ctx, $smartDebitAruddIds) {
    // Add contributions for rejected payments with the status of 'failed'

    // Retrieve ARUDD files from Smartdebit
    if($smartDebitAruddIds) {
      foreach ($smartDebitAruddIds as $aruddId) {
        // Process ARUDD files
        $aruddFile = CRM_Smartdebit_Api::getAruddFile($aruddId);
        unset($aruddFile['arudd_date']);
        CRM_Smartdebit_Sync::processAuddisFile($aruddId, $aruddFile, CRM_Smartdebit_CollectionReports::TYPE_ARUDD);
      }
    }

    Civi::log()->debug('Smartdebit: Sync Job End.');
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Synchronise smart debit payments with CiviCRM
   * We only create new contributions here, anything else has to be done manually using reconciliation
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param $start
   * @param $length
   *
   * @return int
   * @throws \Exception
   */
  public static function syncSmartdebitCollectionReports(CRM_Queue_TaskContext $ctx, $start, $length) {
    // Get batch of payments in the collection report to process
    $collectionReportParams = [
      'limit' => $length,
      'offset' => $start
    ];
    $smartDebitPayments = CRM_Smartdebit_CollectionReports::get($collectionReportParams);

    // Import each transaction from smart debit
    foreach ($smartDebitPayments as $key => $sdPayment) {
      self::processCollection($sdPayment['transaction_id'], $sdPayment['receive_date'], $sdPayment['amount'], CRM_Smartdebit_CollectionReports::TYPE_COLLECTION);
    }
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Process the collection/auddis/arudd record and add/update contributions as required
   *
   * @param string $trxnId
   * @param string $receiveDate
   * @param float $amount
   * @param int $collectionType
   * @param string $description
   *
   * @return bool|int
   * @throws \CiviCRM_API3_Exception
   */
  public static function processCollection($trxnId, $receiveDate, $amount, $collectionType, $description = '') {
    if (empty($trxnId) || empty($receiveDate)) {
      // amount can be empty
      return FALSE;
    }

    // Check we have a mandate for the payment
    $smartDebitMandate = CRM_Smartdebit_Mandates::getbyReference(['trxn_id' => $trxnId]);
    if (!$smartDebitMandate) {
      if (CRM_Smartdebit_Settings::getValue('debug')) {
        Civi::log()->debug('Smartdebit syncSmartdebitRecords: No mandate available for ' . $trxnId);
      }
      return FALSE;
    }

    switch ($collectionType) {
      case CRM_Smartdebit_CollectionReports::TYPE_COLLECTION:
        $collectionDescription = '[SDCR]';
        break;

      case CRM_Smartdebit_CollectionReports::TYPE_AUDDIS:
        $collectionDescription = "[SDAUDDIS] {$description}";
        break;

      case CRM_Smartdebit_CollectionReports::TYPE_ARUDD:
        $collectionDescription = "[SDARUDD] {$description}";
        break;
    }

    // Get existing recurring contribution
    try {
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', [
        'trxn_id' => $trxnId,
      ]);
    } catch (Exception $e) {
      Civi::log()->debug('Smartdebit processCollection: Not Matched=' . $trxnId);
      return FALSE;
    }
    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: $contributionRecur=' . print_r($contributionRecur, true)); }
    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: Matched=' . $trxnId); }

    if (empty($amount)) {
      $amount = $contributionRecur['amount'];
    }
    // Smart debit charge file has dates in UK format
    // UK dates (eg. 27/05/1990) won't work with strtotime, even with timezone properly set.
    // However, if you just replace "/" with "-" it will work fine.
    $receiveDate = CRM_Utils_Date::processDate(date('Y-m-d', strtotime(str_replace('/', '-', $receiveDate))));

    // Use financial type from Smart debit settings
    // if recurring record does not have financial type
    if (empty($contributionRecur['financial_type_id'])) {
      $contributionRecur['financial_type_id'] = CRM_Smartdebit_Settings::getValue('smartdebit_financial_type');
    }

    $contributeParams = [
      'contact_id' => $contributionRecur['contact_id'],
      'contribution_recur_id' => $contributionRecur['id'],
      'total_amount' => $amount,
      'invoice_id' => md5(uniqid(rand(), TRUE)),
      'trxn_id' => CRM_Smartdebit_DateUtils::getContributionTransactionId($trxnId, $receiveDate),
      'financial_type_id' => $contributionRecur['financial_type_id'],
      'payment_instrument_id' => $contributionRecur['payment_instrument_id'],
      'receive_date' => $receiveDate,
      // We don't want to send out email receipts for repeat contributions. That's handled by Smartdebit or by CiviCRM scheduled reminders/rules if required.
      'is_email_receipt' => FALSE,
    ];

    // Check if the contribution is first payment
    // if yes, update the contribution instead of creating one as CiviCRM should have already created the first contribution
    list($firstPayment, $contributeParams) = self::checkIfFirstPayment($contributeParams, $contributionRecur);

    $contributeParams['source'] = $collectionDescription;
    try {
      // Try to get description for contribution from membership
      $membership = civicrm_api3('Membership', 'getsingle', [
        'contribution_recur_id' => $contributionRecur['id'],
      ]);
      if (!empty($membership['source'])) {
        $contributeParams['source'] = $collectionDescription . ' ' . $membership['source'];
      }
    }
    catch (Exception $e) {
      // Do nothing, we just use passed in description
    }

    // Allow params to be modified via hook
    CRM_Smartdebit_Hook::alterContributionParams($contributeParams, $firstPayment);

    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: $contribution=' . print_r($contributeParams, true)); }

    if ($collectionType === CRM_Smartdebit_CollectionReports::TYPE_COLLECTION) {
      $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      if ($firstPayment) {
        if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: success firstpayment (recur:' . $contributionRecur['id'] . ')'); }
        // We need to keep the contribution in "Pending" status in order to run Contribution.completetransaction later.
        // But we can only do that if it hasn't already been set to "Completed".
        if (empty($contributeParams['contribution_status_id'])) {
          $contributeParams['contribution_status_id'] = 'Pending';
        }
        // Update the matching contribution that was created when we setup the recurring/contribution.
        $contributeResult = CRM_Smartdebit_Base::createContribution($contributeParams);
        if ($contributeResult) {
          $newContributionParams = $contributeResult;
        }
        else {
          Civi::log()->error('Smartdebit processCollection: Failed to create contribution: $contributionParams: ' . print_r($contributeParams, TRUE));
          return FALSE;
        }
        $contributeResult = self::completeTransaction($newContributionParams);
        if (!$contributeResult) {
          return FALSE;
        }
      }
      else {
        if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: success recurpayment (recur:' . $contributionRecur['id'] . ')'); }
        // If payment is successful, we call repeattransaction to create a new contribution and update/renew related memberships/events.
        $contributeParams['contribution_status_id'] = $completedStatusId;
        $contributeResult = self::repeatTransaction($contributeParams);
      }
    }
    else {
      // If payment failed, we create the contribution as failed, and don't call completetransaction (as we don't want to update/renew related memberships/events).
      $failedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed');
      if ($firstPayment) {
        if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: failed firstpayment (recur:' . $contributionRecur['id'] . ')'); }
        $contributeParams['contribution_status_id'] = $failedStatusId;
        $contributeResult = CRM_Smartdebit_Base::createContribution($contributeParams);
      }
      else {
        if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: failed recurpayment (recur:' . $contributionRecur['id'] . ')'); }
        $contributeParams['contribution_status_id'] = $failedStatusId;
        $contributeResult = self::repeatTransaction($contributeParams);
      }
    }

    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: $contributeParams=' . print_r($contributeParams, true)); }
    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: $contributeResult=' . print_r($contributeResult, true)); }

    if ($contributeResult) {
      // Get recurring contribution ID
      // get contact display name to display in result screen
      $contactResult = civicrm_api3('Contact', 'getsingle', ['id' => $contributionRecur['contact_id']]);

      // Update Recurring contribution to "In Progress"
      $contributionRecur['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
      if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: Updating contributionrecur=' . $contributionRecur['id']); }
      self::updateRecur($smartDebitMandate, CRM_Smartdebit_DateUtils::getNextScheduledDate($contributeParams['receive_date'], $contributionRecur));

      $resultValues = [
        'type' => $collectionType,
        'transaction_id' => $contributeParams['trxn_id'],
        'contribution_id' => $contributeResult['id'],
        'contact_id' => $contactResult['id'],
        'contact_name' => $contactResult['display_name'],
        'amount' => $amount,
        'frequency' => ucwords($contributionRecur['frequency_interval'] . ' ' . $contributionRecur['frequency_unit']),
        'receive_date' => $contributeParams['receive_date'],
      ];
      CRM_Smartdebit_SyncResults::save($resultValues, $collectionType);

      return $contributeResult['id'];
    }
    return FALSE;
  }

  /**
   * Wrapper around Contribution.completetransaction API
   *
   * @param $contributionParams
   *
   * @return array|bool Contribution parameters or FALSE on failure
   */
  private static function completeTransaction($contributionParams) {
    // If we are in "Pending" status call completetransaction to update related objects (ie. memberships Pending->New).
    $pendingStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    if ($contributionParams['contribution_status_id'] == $pendingStatusId) {
      try {
        return civicrm_api3('Contribution', 'completetransaction', $contributionParams);
      }
      catch (Exception $e) {
        Civi::log()->error('Smartdebit completeTransaction: Failed on C' . $contributionParams['id']);
        return FALSE;
      }
    }
  }

  /**
   * Wrapper around Contribution.repeattransaction API
   * This function will only be called when there is an existing (current or previous) contribution for the recurring contribution
   *
   * @param $contributeParams
   *
   * @return array|bool Contribution parameters or FALSE on failure
   */
  private static function repeatTransaction($contributeParams) {
    $mandatoryParams = ['id', 'trxn_id'];
    foreach ($mandatoryParams as $value) {
      if (empty($contributeParams[$value])) {
        Civi::log()->error('Smartdebit repeatTransaction: Missing mandatory parameter: ' . $value);
        return FALSE;
      }
    }

    try {
      // Check for duplicate transaction IDs.
      $existingContribution = civicrm_api3('Contribution', 'get', ['trxn_id' => $contributeParams['trxn_id']]);
      if ($existingContribution['count'] > 0) {
        // We already have a contribution with matching transaction ID
        // ... so update it instead of creating a new one.
        if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit repeatTransaction: Updating existing contribution ' . $existingContribution['id']); }
        $contributeParams['id'] = $existingContribution['id'];

        // As we are using an existing contribution we need to preserve it's status for completeTransaction
        // But we can only do that if it hasn't already been set to "Completed".
        if (!empty($existingContribution['contribution_status_id'])) {
          $contributionParams['contribution_status_id'] = $existingContribution['contribution_status_id'];
        }
        elseif (empty($contributeParams['contribution_status_id'])) {
          $contributeParams['contribution_status_id'] = 'Pending';
        }
        $updatedContributionParams = CRM_Smartdebit_Base::createContribution($contributeParams);
        return self::completeTransaction($updatedContributionParams);
      }
      else {
        // We already have one (or more) contribution but none with a matching transaction ID
        // ... so use the ID of the one passed in via $contributeParams as a template for repeattransaction
        // Set original contribution ID for repeattransaction, make sure id is not set as we don't want to update an existing one!
        $contributeParams['original_contribution_id'] = $contributeParams['id'];
        unset($contributeParams['id']);
        $newContribution = civicrm_api3('contribution', 'repeattransaction', $contributeParams);
        return CRM_Utils_Array::first($newContribution['values']);
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->error('Smartdebit repeatTransaction error: ' . $e->getMessage() . ' ' . print_r($contributeParams, TRUE));
    }
    return FALSE;
  }

  /**
   * This function is used to process Auddis and Arudd records from an Auddis/Arudd file
   *
   * @param string $auddisId
   * @param string $auddisFile
   * @param string $refKey
   * @param string $dateKey
   * @param int $collectionType
   *
   * @return array|bool
   * @throws \CiviCRM_API3_Exception
   */
  private static function processAuddisFile($auddisId, $auddisFile, $collectionType) {
    $errors = FALSE;
    $rejectedIds = [];

    switch ($collectionType) {
      case CRM_Smartdebit_CollectionReports::TYPE_AUDDIS:
        $collectionDescription = '[SDAUDDIS]';
        $refKey = 'reference';
        $dateKey = 'effective-date';
        $amountKey = NULL;
        $descriptionKey = 'reason-code';
        break;

      case CRM_Smartdebit_CollectionReports::TYPE_ARUDD:
        $collectionDescription = '[SDARUDD]';
        $refKey = 'ref';
        $dateKey = 'originalProcessingDate';
        $amountKey = 'valueOf';
        $descriptionKey = 'returnDescription';
        break;

      default:
        return FALSE;
    }

    // Process each record in the AUDDIS/ARUDD file
    foreach ($auddisFile as $key => $value) {
      if (!isset($value[$refKey]) || !isset($value[$dateKey])) {
        Civi::log()->debug('Smartdebit processAuddis. Id=' . $auddisId . '. Malformed AUDDIS/ARUDD record from Smartdebit.');
        continue;
      }

      $amount = 0;
      if ($amountKey) {
        // Only ARUDD has an amount
        $amount = $value[$amountKey];
      }

      $description = '';
      if ($descriptionKey) {
        $description = $value[$descriptionKey];
      }

      $contributionId = self::processCollection($value[$refKey], $value[$dateKey], $amount, $collectionType, $description);

      if ($contributionId) {
        // Look for an existing contribution
        try {
          $existingContribution = civicrm_api3('Contribution', 'getsingle', [
            'return' => ["id"],
            'id' => $contributionId,
          ]);
        } catch (Exception $e) {
          return FALSE;
        }

        // get contact display name to display in result screen
        $contactParams = ['id' => $existingContribution['contact_id']];
        $contactResult = civicrm_api3('Contact', 'getsingle', $contactParams);

        $rejectedIds[$contributionId] = [
          'cid' => $existingContribution['contact_id'],
          'id' => $contributionId,
          'display_name' => $contactResult['display_name'],
          'total_amount' => CRM_Utils_Money::format($existingContribution['total_amount']),
          'trxn_id' => $value[$refKey],
        ];

        // Allow AUDDIS rejected contribution to be handled by hook
        CRM_Smartdebit_Hook::handleAuddisRejectedContribution($contributionId);
      } else {
        Civi::log()->debug('Smartdebit processAuddis: ' . $value[$refKey] . ' NOT matched to contribution in CiviCRM - try reconciliation.');
        $errors = TRUE;
      }
    }
    if (!$errors) {
      // Mark auddis as processed if we actually found a matching contribution
      CRM_Smartdebit_Auddis::setAuddisRecordProcessed($auddisId);
    }

    return $rejectedIds;
  }

  /**
   * Function to check if the contribution is first contribution
   * for the recurring contribution record
   *
   * @param array $newContribution
   * @param array $contributionRecur
   *
   * @return array (bool: First Contribution, array: contributionrecord)
   * @throws \CiviCRM_API3_Exception
   */
  private static function checkIfFirstPayment($newContribution, $contributionRecur) {
    if (empty($newContribution['contribution_recur_id'])) {
      if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit checkIfFirstPayment: No recur_id'); }
      return [FALSE, NULL];
    }
    if (empty($contributionRecur['frequency_unit'])) {
      $contributionRecur['frequency_unit'] = 'year';
    }
    if (empty($contributionRecur['frequency_interval'])) {
      $contributionRecur['frequency_interval'] = 1;
    }

    $contributionResult = civicrm_api3('Contribution', 'get', [
      'options' => ['limit' => 0, 'sort' => "receive_date DESC"],
      'contribution_recur_id' => $newContribution['contribution_recur_id'],
      'return' => ["id", "contribution_status_id", "trxn_id", "receive_date"],
    ]);

    // We have only one contribution for the recurring record
    if ($contributionResult['count'] > 0) {
      if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit checkIfFirstPayment: '.$contributionResult['count'].' contribution(s). id='.$contributionResult['id']); }

      foreach ($contributionResult['values'] as $contributionDetails) {
        // Check if trxn_ids are identical, if so, update this trxn
        if (strcmp($contributionDetails['trxn_id'], $newContribution['trxn_id']) == 0) {
          $newContribution['id'] = $contributionDetails['id'];
          $newContribution['contribution_status_id'] = $contributionDetails['contribution_status_id'];
          if (CRM_Smartdebit_Settings::getValue('debug')) {
            Civi::log()->debug('Smartdebit checkIfFirstPayment: Identical-Using existing contribution');
          }
          return [TRUE, $newContribution];
        }
      }

      // No identical contribution found, select the most recent one
      $contributionDetails = CRM_Utils_Array::first($contributionResult['values']);
      // Check if the transaction Id is one of ours, and not identical
      if (!empty($contributionDetails['trxn_id'])) {
        // Does our trxn_id start with the recurring one?
        if (strcmp(substr($contributionDetails['trxn_id'], 0, strlen($contributionRecur['trxn_id'])), $contributionRecur['trxn_id']) == 0) {
          // Does our trxn_id contain a '/' after the ref?
          if (strcmp(substr($contributionDetails['trxn_id'], strlen($contributionRecur['trxn_id']), 1), '/') == 0) {
            // Not identical but one of ours, so we'll create a new one
            if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit checkIfFirstPayment: Not identical,ours. Creating new contribution'); }
            // Assign the id of the most recent contribution, we need this as a template to repeat the transaction
            $newContribution['id'] = $contributionDetails['id'];
            $newContribution['contribution_status_id'] = $contributionDetails['contribution_status_id'];
            return [FALSE, $newContribution];
          }
        }
      }

      if (!empty($contributionDetails['receive_date']) && !empty($newContribution['receive_date'])) {
        // Find the date difference between the contribution date and new collection date
        $dateDiff = CRM_Smartdebit_DateUtils::dateDifference($newContribution['receive_date'], $contributionDetails['receive_date']);
        // Get days difference to determine if this is first payment
        $days = CRM_Smartdebit_DateUtils::daysDifferenceForFrequency($contributionRecur['frequency_unit'], $contributionRecur['frequency_interval']);

        // if diff is less than set number of days, return Contribution ID to update the contribution
        // If $days == 0 it's a lifetime membership
        if (($dateDiff < $days) && ($days != 0)) {
          if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit checkIfFirstPayment: Within dates,Using existing contribution'); }
          // Assign the id of the most recent contribution, we need this as a template to repeat the transaction
          $newContribution['id'] = $contributionDetails['id'];
          $newContribution['contribution_status_id'] = $contributionDetails['contribution_status_id'];
          return [TRUE, $newContribution];
        }
      }
    }
    // If no contributions linked to recur, it must be the first contribution!
    return [TRUE, $newContribution];
  }

  /**
   * Update parameters of CiviCRM recurring contributions that represent Smartdebit Direct Debit Mandates
   *
   * @param array $transactionIds Optional array of transaction IDs to update recurring contributions for
   *
   * @return array $stats['modified', 'count']
   * @throws \Exception
   */
  public static function updateRecurringContributions($transactionIds = []) {
    if (!is_array($transactionIds) && !empty($transactionIds)) {
      $transactionIds = [$transactionIds];
    }
    $stats = [
      'count' => 0,
      'modified' => 0,
    ];

    if (count($transactionIds) > 0) {
      foreach ($transactionIds as $transactionId) {
        $smartDebitRecord = CRM_Smartdebit_Mandates::getbyReference(['trxn_id' => $transactionId, 'refresh' => FALSE]);
        if ($smartDebitRecord) {
          if (self::updateRecur($smartDebitRecord)) {
            $stats['modified']++;
          }
          $stats['count']++;
        }
      }
    }
    else {
      $count = CRM_Smartdebit_Mandates::count(TRUE);
      $batchSize = 100;
      $params['limit'] = $batchSize;
      for ($start = 0; $start < $count; $start+=$batchSize) {
        $params['offset'] = $start;
        $smartDebitMandates = CRM_Smartdebit_Mandates::getAll(FALSE, TRUE, $params);
        foreach ($smartDebitMandates as $key => $smartDebitMandate) {
          if (self::updateRecur($smartDebitMandate)) {
            $stats['modified']++;
          }
          $stats['count']++;
          CRM_Smartdebit_Utils::log('Smartdebit updateRecur. Modified: ' . $stats['modified'] . ' Count: ' . $stats['count'], TRUE);
        }
      }
    }

    Civi::log()->info('Smartdebit: Updated ' . $stats['modified'] . ' of ' . $stats['count'] . ' recurring contributions');

    return $stats;
  }

  /**
   * Update the recurring contribution linked to the smartdebit mandate
   *
   * @param $smartDebitMandate
   *
   * @return bool|array new $recur params if recur was modified, FALSE otherwise.
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateRecur($smartDebitMandate, $nextPaymentDate = NULL) {
    // Get the recurring contribution that is linked to the Smartdebit mandate
    try {
      $recurContribution = civicrm_api3('ContributionRecur', 'getsingle', [
        'trxn_id' => $smartDebitMandate['reference_number'],
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Recurring contribution with transaction ID does not exist
      return FALSE;
    }

    $recurContributionOriginal = $recurContribution;
    // Update the recurring contribution
    $recurContribution['amount'] = CRM_Smartdebit_Utils::getCleanSmartdebitAmount($smartDebitMandate['default_amount']);
    list($recurContribution['frequency_unit'], $recurContribution['frequency_interval']) =
      CRM_Smartdebit_DateUtils::translateSmartdebitFrequencytoCiviCRM($smartDebitMandate['frequency_type'], $smartDebitMandate['frequency_factor']);
    // We have no way of knowing the end_date (API doesn't report it) but we'll assume that there is no end date if we changed frequency.
    if (CRM_Utils_Array::value('installments', $recurContribution) == 1) {
      if (($recurContribution['frequency_interval'] != $recurContributionOriginal['frequency_interval'])
        || ($recurContribution['frequency_unit'] != $recurContributionOriginal['frequency_unit'])) {
        $recurContribution['installments'] = '';
      }
    }

    switch ($smartDebitMandate['current_state']) {
      case CRM_Smartdebit_Api::SD_STATE_NEW:
        // Clear cancel date and set status if live/new
        isset($recurContribution['cancel_date']) ? $recurContribution['cancel_date'] = '' : NULL;
        $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
        break;

      case CRM_Smartdebit_Api::SD_STATE_LIVE:
        // Clear cancel date and set status if live/new
        isset($recurContribution['cancel_date']) ? $recurContribution['cancel_date'] = '' : NULL;
        $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
        break;

      case CRM_Smartdebit_Api::SD_STATE_CANCELLED:
        $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled');
        break;

      case CRM_Smartdebit_Api::SD_STATE_REJECTED:
        $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed');
        break;
    }

    // If a date is passed in we use that as the next scheduled date, otherwise we set it to start date.
    if ($nextPaymentDate) {
      $recurContribution['next_sched_contribution_date'] = $nextPaymentDate;
    }
    else {
      if ($recurContribution['start_date'] !== $smartDebitMandate['start_date']) {
        // Update the date of the linked Contribution to match the next scheduled contribution date
        $recurContribution['next_sched_contribution_date'] = $smartDebitMandate['start_date'];
        // FIXME: We may need to call this when nextPaymentDate is set as well.
        CRM_Smartdebit_Base::updateContributionDateForLinkedRecur($recurContribution['id'], $recurContribution['start_date'], $smartDebitMandate['start_date']);
        $recurContribution['start_date'] = $smartDebitMandate['start_date'];
      }
    }

    // Hook to allow modifying recurring contribution during sync task
    CRM_Smartdebit_Hook::updateRecurringContribution($recurContribution);
    if ($recurContribution != $recurContributionOriginal) {
      CRM_Smartdebit_Utils::log('Smartdebit recurs don\'t match: Original: ' . print_r($recurContributionOriginal, TRUE) . ' New: ' . print_r($recurContribution, TRUE), TRUE);
      $recurContribution['modified_date'] = (new DateTime())->format('Y-m-d H:i:s');
      civicrm_api3('ContributionRecur', 'create', $recurContribution);
      return $recurContribution;
    }
    return FALSE;
  }

}
