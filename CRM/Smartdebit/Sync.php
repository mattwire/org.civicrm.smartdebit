<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

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
    // Reset stats
    CRM_Smartdebit_Settings::save(array('rejected_auddis' => NULL));
    CRM_Smartdebit_Settings::save(array('rejected_arudd' => NULL));

    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name'  => self::QUEUE_NAME,
      'type'  => 'Sql',
      'reset' => TRUE,
    ));

    // Clear out the results table
    $emptySql = "TRUNCATE TABLE veda_smartdebit_success_contributions";
    CRM_Core_DAO::executeQuery($emptySql);

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
      $start   = $i * self::BATCH_COUNT;
      $counter = ($rounds > 1) ? ($start + self::BATCH_COUNT) : $count;
      if ($counter > $count) $counter = $count;
      $task    = new CRM_Queue_Task(
        array('CRM_Smartdebit_Sync', 'syncSmartdebitCollectionReports'),
        array($start, self::BATCH_COUNT),
        "Syncing Smartdebit collections to CiviCRM contributions - {$counter} of {$count}"
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
        array('CRM_Smartdebit_Sync', 'syncSmartdebitAuddis'),
        array($auddisIDs),
        "Syncing AUDDIS reports from Smartdebit"
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
        array('CRM_Smartdebit_Sync', 'syncSmartdebitArudd'),
        array($aruddIDs),
        "Syncing ARUDD reports from Smartdebit"
      );
      $queue->createItem($task);
    }

    // Update recurring contributions
    $task = new CRM_Queue_Task(
      array('CRM_Smartdebit_Sync', 'updateRecurringContributionsTask'),
      array(),
      'Updating Recurring Contributions in CiviCRM'
    );
    $queue->createItem($task);

    $task = new CRM_Queue_Task(
      array('CRM_Smartdebit_CollectionReports', 'removeOld'),
      array(),
      'Cleaning up'
    );
    $queue->createItem($task);

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
  public static function syncSmartdebitAuddis(CRM_Queue_TaskContext $ctx, $smartDebitAuddisIds)
  {
    // Add contributions for rejected payments with the status of 'failed'
    // Reset the counter when sync starts
    CRM_Smartdebit_Settings::save(array('rejected_auddis' => NULL));
    // Rejected Ids is used to display on confirm form, would be nice to tidy and have it's own table or something
    $rejectedIds = array();

    // Retrieve AUDDIS files from Smartdebit
    if ($smartDebitAuddisIds) {
      // Find the relevant auddis file
      foreach ($smartDebitAuddisIds as $auddisId) {
        // Process AUDDIS files
        $auddisFile = CRM_Smartdebit_Api::getAuddisFile($auddisId);
        unset($auddisFile['auddis_date']);
        $refKey = 'reference';
        $dateKey = 'effective-date';
        $rejectedIds = array_merge($rejectedIds, CRM_Smartdebit_Sync::processAuddisFile($auddisId, $auddisFile, $refKey, $dateKey, 'SDAUDDIS'));
      }
    }
    CRM_Smartdebit_Settings::save(array('rejected_auddis' => $rejectedIds));
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
    $rejectedIds = array();
    // Reset the counter when sync starts
    CRM_Smartdebit_Settings::save(array('rejected_arudd' => NULL));

    // Retrieve ARUDD files from Smartdebit
    if($smartDebitAruddIds) {
      foreach ($smartDebitAruddIds as $aruddId) {
        // Process ARUDD files
        $aruddFile = CRM_Smartdebit_Api::getAruddFile($aruddId);
        unset($aruddFile['arudd_date']);
        $refKey = 'ref';
        $dateKey = 'originalProcessingDate';
        $rejectedIds = array_merge($rejectedIds, CRM_Smartdebit_Sync::processAuddisFile($aruddId, $aruddFile, $refKey, $dateKey, 'SDARUDD'));
      }
    }
    CRM_Smartdebit_Settings::save(array('rejected_arudd' => $rejectedIds));

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
      // Check we have a mandate for the payment
      if (!CRM_Smartdebit_Mandates::getbyReference($sdPayment['transaction_id'], TRUE)) {
        if (CRM_Smartdebit_Settings::getValue('debug')) {
          Civi::log()->debug('Smartdebit syncSmartdebitRecords: No mandate available for ' . $sdPayment['transaction_id']);
        }
        continue;
      }

      self::processCollection($sdPayment['transaction_id'], $sdPayment['receive_date'], TRUE, $sdPayment['amount'], 'SDCR');
    }
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Process the collection/auddis/arudd record and add/update contributions as required
   *
   * @param string $trxnId
   * @param string $receiveDate
   * @param bool $contributionSuccess
   * @param float $amount
   * @param string $collectionDescription
   *
   * @return bool|int
   * @throws \CiviCRM_API3_Exception
   */
  private static function processCollection($trxnId, $receiveDate, $contributionSuccess, $amount, $collectionDescription) {
    if (empty($trxnId) || empty($receiveDate)) {
      // amount can be empty
      return FALSE;
    }

    // Get existing recurring contribution
    try {
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', array(
        'trxn_id' => $trxnId,
      ));
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
    $receiveDate = date('Y-m-d', strtotime(str_replace('/', '-', $receiveDate)));

    // Use financial type from Smart debit settings
    // if recurring record does not have financial type
    if (empty($contributionRecur['financial_type_id'])) {
      $contributionRecur['financial_type_id'] = CRM_Smartdebit_Settings::getValue('smartdebit_financial_type');
    }

    $contributeParams =
      array(
        'contact_id' => $contributionRecur['contact_id'],
        'contribution_recur_id' => $contributionRecur['id'],
        'total_amount' => $amount,
        'invoice_id' => md5(uniqid(rand(), TRUE)),
        'trxn_id' => $trxnId . '/' . CRM_Utils_Date::processDate($receiveDate),
        'financial_type_id' => $contributionRecur['financial_type_id'],
        'payment_instrument_id' => $contributionRecur['payment_instrument_id'],
        'receive_date' => CRM_Utils_Date::processDate($receiveDate),
      );

    // Check if the contribution is first payment
    // if yes, update the contribution instead of creating one
    // as CiviCRM should have created the first contribution
    list($firstPayment, $contributeParams) = self::checkIfFirstPayment($contributeParams, $contributionRecur);

    $contributeParams['source'] = '[' . $collectionDescription . ']';
    try {
      // Try to get description for contribution from membership
      $membership = civicrm_api3('Membership', 'getsingle', array(
        'contribution_recur_id' => $contributionRecur['id'],
      ));
      if (!empty($membership['source'])) {
        $contributeParams['source'] = '[' . $collectionDescription . '] ' . $membership['source'];
      }
    }
    catch (Exception $e) {
      // Do nothing, we just use passed in description
    }

    // Allow params to be modified via hook
    CRM_Smartdebit_Hook::alterContributionParams($contributeParams, $firstPayment);

    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: $contribution=' . print_r($contributeParams, true)); }

    if ($contributionSuccess) {
      $completedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      if ($firstPayment) {
        if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: success firstpayment (recur:' . $contributionRecur['id'] . ')'); }
        // Update the matching contribution that was created when we setup the recurring/contribution.
        $contributeResult = CRM_Smartdebit_Base::createContribution($contributeParams);
        if (isset($contributeResult['values'][$contributeResult['id']])) {
          $newContributionParams = $contributeResult['values'][$contributeResult['id']];
        }
        else {
          Civi::log()->error('Smartdebit processCollection: Failed to create contribution: $contributionParams: ' . print_r($contributeParams, TRUE));
          return FALSE;
        }
        // If we are in "Pending" status call completetransaction to update related objects (ie. memberships Pending->New).
        $pendingStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
        if ($newContributionParams['contribution_status_id'] === $pendingStatusId) {
          try {
            $contributeResult = civicrm_api3('Contribution', 'completetransaction', $newContributionParams);
          }
          catch (Exception $e) {
            Civi::log()->error('Smartdebit processCollection: Failed to run completetransaction on C' . $newContributionParams['id']);
            return FALSE;
          }
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

    if (!empty($contributeResult['id'])) {
      // Get recurring contribution ID
      // get contact display name to display in result screen
      $contactResult = civicrm_api3('Contact', 'getsingle', array('id' => $contributionRecur['contact_id']));

      // Update Recurring contribution to "In Progress"
      $contributionRecur['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
      if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit processCollection: Updating contributionrecur=' . $contributionRecur['id']); }
      CRM_Smartdebit_Base::createRecurContribution($contributionRecur);

      // Only record completed collections in DB
      if ($contributionSuccess) {
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
          1 => array($trxnId, 'String'),
          2 => array($contributeResult['id'], 'Integer'),
          3 => array($contactResult['id'], 'Integer'),
          4 => array($contactResult['display_name'], 'String'),
          5 => array($amount, 'String'),
          6 => array($contributionRecur['frequency_interval'] . ' ' . $contributionRecur['frequency_unit'], 'String'),
        );
        CRM_Core_DAO::executeQuery($keepSuccessResultsSQL, $keepSuccessResultsParams);
      }
      return $contributeResult['id'];
    }
    return FALSE;
  }

  /**
   * Wrapper around Contribution.repeattransaction API
   * This function will only be called when there is an existing (current or previous) contribution for the recurring contribution
   *
   * @param $contributeParams
   *
   * @return array|bool
   */
  private static function repeatTransaction($contributeParams) {
    if (empty($contributeParams['id'])) {
      Civi::log()->error('Smartdebit repeatTransaction: Missing mandatory parameter $contributeParams[\'id\']');
      return FALSE;
    }
    try {
      // Check for duplicate transaction IDs.
      if (!empty($contributeParams['trxn_id'])) {
        $existingContribution = civicrm_api3('Contribution', 'get', array(
          'trxn_id' => $contributeParams['trxn_id'],
        ));
        if ($existingContribution['count'] > 0) {
          // We already have a contribution with matching transaction ID
          // ... so update it instead of creating a new one.
          if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit repeatTransaction: Updating existing contribution ' . $existingContribution['id']); }
          $contributeParams['id'] = $existingContribution['id'];
          // TODO: do we need to call completeTransaction here, maybe only when going from Pending->Completed?
          return CRM_Smartdebit_Base::createContribution($contributeParams);
        }
        else {
          // We already have one (or more) contribution but none with a matching transaction ID
          // ... so use the ID of the one passed in via $contributeParams as a template for repeattransaction
          // Set original contribution ID for repeattransaction, make sure id is not set as we don't want to update an existing one!
          $contributeParams['original_contribution_id'] = $contributeParams['id'];
          unset($contributeParams['id']);
          return civicrm_api3('contribution', 'repeattransaction', $contributeParams);
        }
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->error('Smartdebit repeatTransaction error: ' . $e->getMessage() . ' ' . print_r($contributeParams, TRUE));
    }
  }

  /**
   * This function is used to process Auddis and Arudd records from an Auddis/Arudd file
   *
   * @param string $auddisId
   * @param string $auddisFile
   * @param string $refKey
   * @param string $dateKey
   * @param string $collectionDescription
   *
   * @return array|bool
   * @throws \CiviCRM_API3_Exception
   */
  private static function processAuddisFile($auddisId, $auddisFile, $refKey, $dateKey, $collectionDescription) {
    $errors = FALSE;
    $rejectedIds = array();

    // Process each record in the auddis file
    foreach ($auddisFile as $key => $value) {
      if (!isset($value[$refKey]) || !isset($value[$dateKey])) {
        Civi::log()->debug('Smartdebit processAuddis. Id=' . $auddisId . '. Malformed Auddis/Arudd record from Smartdebit.');
        continue;
      }

      $contributionId = self::processCollection($value[$refKey], $value[$dateKey], FALSE, 0, $collectionDescription);

      if ($contributionId) {
        // Look for an existing contribution
        try {
          $existingContribution = civicrm_api3('Contribution', 'getsingle', array(
            'return' => array("id"),
            'id' => $contributionId,
          ));
        } catch (Exception $e) {
          return FALSE;
        }

        // get contact display name to display in result screen
        $contactParams = array('version' => 3, 'id' => $existingContribution['contact_id']);
        $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

        $rejectedIds[$contributionId] = array('cid' => $existingContribution['contact_id'],
          'id' => $contributionId,
          'display_name' => $contactResult['display_name'],
          'total_amount' => CRM_Utils_Money::format($existingContribution['total_amount']),
          'trxn_id' => $value[$refKey],
          'status' => $existingContribution['label'],
        );

        // Allow auddis rejected contribution to be handled by hook
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
      return array(FALSE, NULL);
    }
    if (empty($contributionRecur['frequency_unit'])) {
      $contributionRecur['frequency_unit'] = 'year';
    }
    if (empty($contributionRecur['frequency_interval'])) {
      $contributionRecur['frequency_interval'] = 1;
    }

    $contributionResult = civicrm_api3('Contribution', 'get', array(
      'sequential' => 1,
      'options' => array('sort' => "receive_date DESC"),
      'contribution_recur_id' => $newContribution['contribution_recur_id'],
    ));

    // We have only one contribution for the recurring record
    if ($contributionResult['count'] > 0) {
      if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit checkIfFirstPayment: '.$contributionResult['count'].' contribution(s). id='.$contributionResult['id']); }

      foreach ($contributionResult['values'] as $contributionDetails) {
        // Check if trxn_ids are identical, if so, update this trxn
        if (strcmp($contributionDetails['trxn_id'], $newContribution['trxn_id']) == 0) {
          $newContribution['id'] = $contributionDetails['id'];
          if (CRM_Smartdebit_Settings::getValue('debug')) {
            Civi::log()->debug('Smartdebit checkIfFirstPayment: Identical-Using existing contribution');
          }
          return array(TRUE, $newContribution);
        }
      }

      $contributionDetails = $contributionResult['values'][0];
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
            return array(FALSE, $newContribution);
          }
        }
      }

      if (!empty($contributionDetails['receive_date']) && !empty($newContribution['receive_date'])) {
        // Find the date difference between the contribution date and new collection date
        $dateDiff = CRM_Smartdebit_Sync::dateDifference($newContribution['receive_date'], $contributionDetails['receive_date']);
        // Get days difference to determine if this is first payment
        $days = CRM_Smartdebit_Sync::daysDifferenceForFrequency($contributionRecur['frequency_unit'], $contributionRecur['frequency_interval']);

        // if diff is less than set number of days, return Contribution ID to update the contribution
        // If $days == 0 it's a lifetime membership
        if (($dateDiff < $days) && ($days != 0)) {
          if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit checkIfFirstPayment: Within dates,Using existing contribution'); }
          // Assign the id of the most recent contribution, we need this as a template to repeat the transaction
          $newContribution['id'] = $contributionDetails['id'];
          return array(TRUE, $newContribution);
        }
      }
    }
    // If no contributions linked to recur, it must be the first contribution!
    return array(TRUE, $newContribution);
  }

  /**
   * Return difference between two dates in format
   *
   * @param string $date_1
   * @param string $date_2
   * @param string $differenceFormat
   *
   * @return string
   */
  private static function dateDifference($date_1, $date_2, $differenceFormat = '%a')
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
   * @param string $frequencyUnit
   * @param int $frequencyInterval
   *
   * @return int
   */
  private static function daysDifferenceForFrequency($frequencyUnit, $frequencyInterval) {
    switch ($frequencyUnit) {
      case 'day':
        $days = $frequencyInterval * 1;
        break;
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
   * Helper function to trigger updateRecurringContributions via taskrunner
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateRecurringContributionsTask(CRM_Queue_TaskContext $ctx) {
    self::updateRecurringContributions();
    return CRM_Queue_Task::TASK_SUCCESS;
  }

  /**
   * Update parameters of CiviCRM recurring contributions that represent Smartdebit Direct Debit Mandates
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function updateRecurringContributions() {
    $smartDebitPayerContactDetails = CRM_Smartdebit_Mandates::getAll(FALSE, TRUE);
    if (empty($smartDebitPayerContactDetails)) {
      return;
    }

    foreach ($smartDebitPayerContactDetails as $key => $smartDebitRecord) {
      // Get recur
      try {
        $recurContribution = civicrm_api3('ContributionRecur', 'getsingle', array(
          'trxn_id' => $smartDebitRecord['reference_number'],
        ));
      }
      catch (CiviCRM_API3_Exception $e) {
        // Recurring contribution with transaction ID does not exist
        continue;
      }

      $recurContributionOriginal = $recurContribution;
      // Update the recurring contribution
      $recurContribution['amount'] = CRM_Smartdebit_Utils::getCleanSmartdebitAmount($smartDebitRecord['default_amount']);
      list($recurContribution['frequency_unit'], $recurContribution['frequency_interval']) =
        CRM_Smartdebit_Base::translateSmartdebitFrequencytoCiviCRM($smartDebitRecord['frequency_type'], $smartDebitRecord['frequency_factor']);

      switch ($smartDebitRecord['current_state']) {
        case CRM_Smartdebit_Api::SD_STATE_LIVE:
        case CRM_Smartdebit_Api::SD_STATE_NEW:
          // Clear cancel date and set status if live
          $recurContribution['cancel_date'] = '';
          if (($recurContribution['contribution_status_id'] != CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'))
            && ($recurContribution['contribution_status_id'] != CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress'))) {
            $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
          }
          break;
        case CRM_Smartdebit_Api::SD_STATE_CANCELLED:
          $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled');
          break;
        case CRM_Smartdebit_Api::SD_STATE_REJECTED:
          $recurContribution['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Failed');
          break;
      }

      // Set start date to date of first contribution
      try {
        $firstContribution = civicrm_api3('Contribution', 'getsingle', array(
          'contribution_recur_id' => $recurContribution['id'],
          'options' => array('limit' => 1, 'sort' => "receive_date ASC"),
        ));
        if (!empty($firstContribution['receive_date'])) {
          $recurContribution['start_date'] = $firstContribution['receive_date'];
        }
      }
      catch (CiviCRM_API3_Exception $e) {
        // No contribution, so don't update start date.
      }

      // Hook to allow modifying recurring contribution during sync task
      CRM_Smartdebit_Hook::updateRecurringContribution($recurContribution);
      if ($recurContribution != $recurContributionOriginal) {
        $recurContribution['modified_date'] = (new DateTime())->format('Y-m-d H:i:s');
        civicrm_api3('ContributionRecur', 'create', $recurContribution);
      }
    }
  }

}
