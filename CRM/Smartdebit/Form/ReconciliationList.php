<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * addition of contacts to groups.
 *
 * Path: civicrm/smartdebit/reconciliation/list
 */
class CRM_Smartdebit_Form_ReconciliationList extends CRM_Core_Form {

  /**
   * Build the form
   *
   * @access public
   *
   * @throws \Exception
   */
  public function buildQuickForm() {
    if ($this->_flagSubmitted) return;

    // See if a sync is requested
    $sync = CRM_Utils_Array::value('sync', $_GET, '');
    if ($sync) {
      // Do a sync
      CRM_Smartdebit_Mandates::retrieveAll();

      // Redirect back to this form
      $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', 'reset=1');
      CRM_Utils_System::redirect($url);
      return;
    }

    // Check if we have any data to use, otherwise we'll need to sync with Smartdebit
    $count = CRM_Smartdebit_Mandates::count();
    $this->assign('totalMandates', $count);
    if ($count == 0) {
      // Have not done a sync.  Display state and add button to perform sync
      $queryParams['sync'] = 1;
      $queryParams['reset'] = 1;
      $redirectUrlContinue  = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', $queryParams);
      $buttons[] = [
        'type' => 'next',
        'js' => ['onclick' => "location.href='{$redirectUrlContinue}'; return false;"],
        'name' => ts('Sync Now'),
      ];
      $this->addButtons($buttons);
      return;
    }

    $checkAmount = CRM_Utils_Array::value('checkAmount', $_GET);
    $checkFrequency = CRM_Utils_Array::value('checkFrequency', $_GET);
    $checkStatus = CRM_Utils_Array::value('checkStatus', $_GET);
    $checkDate = CRM_Utils_Array::value('checkDate', $_GET);
    $checkPayerReference = CRM_Utils_Array::value('checkPayerReference', $_GET);
    $checkMissingFromCivi = CRM_Utils_Array::value('checkMissingFromCivi', $_GET);
    $checkMissingFromSD = CRM_Utils_Array::value('checkMissingFromSD', $_GET);
    // Only display smart debit records that have a matching contact in CiviCRM if hasContact=1
    $hasAmount = CRM_Utils_Array::value('hasAmount', $_GET);
    $hasContact = CRM_Utils_Array::value('hasContact', $_GET);

    $listArray = [];
    $fixMeContact = FALSE;
    $totalRows = 0;

    // Get contribution Status options
    $contributionStatusOptions = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');

    // The following differences are highlighted
    // 1. Transaction Id in Smart Debit and Civi for the same contact
    // 2. Transaction Id in Smart Debit and Civi for different contacts
    // 3. Transaction Id in Smart Debit but none found in Civi
    // 4. Transaction Id in Civi but none in Smart Debit

    // Start Here
    if ($checkAmount || $checkFrequency || $checkStatus || $checkPayerReference || $checkDate) {
      $sql  = "
SELECT ctrc.id contribution_recur_id, 
  ctrc.contact_id,
  cont.display_name,
  ctrc.payment_instrument_id,
  opva.label payment_instrument,
  ctrc.start_date,
  ctrc.amount,
  ctrc.trxn_id,
  ctrc.contribution_status_id,
  ctrc.frequency_unit,
  ctrc.frequency_interval,
  ctrc.financial_type_id,
  csd.default_amount,
  csd.frequency_type,
  csd.frequency_factor,
  csd.current_state,
  csd.payerReference,
  csd.start_date as smart_debit_start_date,
  csd.reference_number,
  csd.id as smart_debit_id 
FROM civicrm_contribution_recur ctrc 
LEFT JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id) 
LEFT JOIN civicrm_option_value opva ON (ctrc.payment_instrument_id = opva.value) 
LEFT JOIN civicrm_option_group opgr ON (opgr.id = opva.option_group_id) 
INNER JOIN veda_smartdebit_mandates csd ON csd.reference_number = ctrc.trxn_id 
WHERE opgr.name = 'payment_instrument' 
AND   opva.name = 'Direct Debit' ";

      $sql .= ' ORDER BY csd.reference_number DESC';
      $dao = CRM_Core_DAO::executeQuery($sql);

      while ($dao->fetch()) {
        // Smart Debit Record Found
        // 1. Transaction Id in Smart Debit and Civi for the same contact

        $transactionRecordFound = true;
        $difference['amount'] = $difference['frequency'] = $difference['status'] = $difference['contact'] = $difference['date'] = FALSE;

        if ($checkAmount) {
          // Check that amount in CiviCRM matches amount in Smart Debit
          if ($dao->default_amount != $dao->amount) {
            $difference['amount'] = TRUE;
          }
        }

        if ($checkDate) {
          // Check that date in CiviCRM matches amount in Smart Debit
          if ($dao->smart_debit_start_date != $dao->start_date) {
            $difference['date'] = TRUE;
          }
        }

        if ($checkFrequency) {
          // Check that frequency in CiviCRM matches frequency in Smart Debit
          if ($dao->frequency_type == 'W' && ($dao->frequency_unit != 'day' || $dao->frequency_interval % 7 != 0) ) {
            $difference['frequency'] = TRUE;
          }
          elseif ($dao->frequency_type == 'M' && $dao->frequency_unit != 'month' ) {
            $difference['frequency'] = TRUE;
          }
          elseif ($dao->frequency_type == 'Q' && ($dao->frequency_unit != 'month' && $dao->frequency_interval % 3 != 0)) {
            $difference['frequency'] = TRUE;
          }
          elseif ($dao->frequency_type == 'Y' && $dao->frequency_unit != 'year' ) {
            $difference['frequency'] = TRUE;
          }
        }

        $contributionInProgressId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
        // First case check if Smart Debit is new or live then CiviCRM is in progress
        if ($checkStatus) {
          if (($dao->current_state == CRM_Smartdebit_Api::SD_STATE_LIVE || $dao->current_state == CRM_Smartdebit_Api::SD_STATE_NEW)
            && ($dao->contribution_status_id != $contributionInProgressId)) {
            $difference['status'] = TRUE;
          }
          // Recurring record active in Civi, but smart debit record is not active
          if (!($dao->current_state == CRM_Smartdebit_Api::SD_STATE_LIVE || $dao->current_state == CRM_Smartdebit_Api::SD_STATE_NEW)
            && ($dao->contribution_status_id == $contributionInProgressId)) {
            $difference['status'] = TRUE;
          }
        }

        // 2. Transaction Id in Smart Debit and Civi for different contacts
        if ($checkPayerReference) {
          if ($dao->payerReference != $dao->contact_id) {
            $difference['contact'] = TRUE;
          }
        }

        // If different then
        if ($difference['amount'] || $difference['frequency'] || $difference['status'] || $difference['contact'] || $difference['date']) {
          $financialType = '';
          if ($dao->financial_type_id) {
            $financialType = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $dao->financial_type_id, 'name', 'id');
          }

          $differences = '';
          // Generate differences text string for display
          foreach ($difference as $key => $value) {
            if ($value) {
              if (!empty($differences)) {
                $differences .= ' | ';
              }
              switch ($key) {
                case 'amount':
                  $differences .= 'Amount';
                  break;
                case 'frequency':
                  $differences .= 'Frequency';
                  break;
                case 'status':
                  $differences .= 'Status';
                  break;
                case 'contact':
                  $differences .= 'Payer Reference(Contact Id)';
                  break;
                case 'date':
                  $differences .= 'Date';
                  break;
              }
            }
          }

          $listArray[$dao->smart_debit_id]['recordFound']           = $transactionRecordFound;
          $listArray[$dao->smart_debit_id]['contribution_recur_id'] = $dao->contribution_recur_id;
          $listArray[$dao->smart_debit_id]['contribution_type']     = $financialType;
          $listArray[$dao->smart_debit_id]['contact_id']            = $dao->contact_id;
          $listArray[$dao->smart_debit_id]['sd_contact_id']         = $dao->payerReference;
          $listArray[$dao->smart_debit_id]['contact_name']          = $dao->display_name;
          $listArray[$dao->smart_debit_id]['payment_instrument']    = $dao->payment_instrument;
          $listArray[$dao->smart_debit_id]['start_date']            = $dao->start_date;
          $listArray[$dao->smart_debit_id]['sd_start_date']         = $dao->smart_debit_start_date;
          $listArray[$dao->smart_debit_id]['frequency_unit']        = $dao->frequency_unit;
          $listArray[$dao->smart_debit_id]['sd_frequency_type']     = $dao->frequency_type;
          $listArray[$dao->smart_debit_id]['frequency_interval']    = $dao->frequency_interval;
          $listArray[$dao->smart_debit_id]['sd_frequency_factor']   = $dao->frequency_factor;
          $listArray[$dao->smart_debit_id]['amount']                = $dao->amount;
          $listArray[$dao->smart_debit_id]['sd_amount']             = $dao->default_amount;
          $listArray[$dao->smart_debit_id]['contribution_status_id'] = $contributionStatusOptions[$dao->contribution_status_id];
          $listArray[$dao->smart_debit_id]['sd_contribution_status_id'] = self::formatSDStatus($dao->current_state);
          $listArray[$dao->smart_debit_id]['transaction_id']        = $dao->trxn_id;
          $listArray[$dao->smart_debit_id]['differences']           = $differences;
          $fixmeurl = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/fix/select', "cid=".$dao->contact_id."&reference_number=".$dao->reference_number,  TRUE, NULL, FALSE, TRUE, TRUE);
          if ($difference['amount'] || $difference['frequency'] || $difference['status'] || $difference['date']) {
            // Show fix me link if difference is amount, frequency, status, or date
            if (!empty($dao->default_amount)) { // We don't support no amount at smartdebit
              $listArray[$dao->smart_debit_id]['fix_me_url'] = $fixmeurl;
            }
          }
          if ($difference['contact']) {
            // Assign to form so we can use it
            $fixMeContact = TRUE;
          }
        }
      }
    }
    if ($checkMissingFromCivi) {
      // 3. Transaction Id in Smart Debit but none found in Civi
      $sql  = "
SELECT contact.id as contact_id,
  contact.display_name,
  csd.default_amount,
  csd.frequency_type,
  csd.frequency_factor,
  csd.current_state,
  csd.payerReference,
  csd.start_date,
  csd.reference_number,
  csd.id as smart_debit_id,
  csd.first_name,
  csd.last_name 
FROM veda_smartdebit_mandates csd 
LEFT JOIN civicrm_contribution_recur ctrc ON ctrc.trxn_id = csd.reference_number 
LEFT JOIN civicrm_contact contact ON contact.id = csd.payerReference 
WHERE ctrc.id IS NULL";
      // Filter records that have an amount recorded against them or not
      if ($hasAmount) {
        $sql .= " AND (COALESCE(csd.default_amount, '') != '')";
      }
      else {
        $sql .= " AND (COALESCE(csd.default_amount, '') = '')";
      }
      // Filter records with no valid contact ID
      if ($hasContact) {
        $sql .= " AND contact.id IS NOT NULL";
      }
      else {
        $sql .= " AND contact.id IS NULL";
      }
      $sql .= ' ORDER BY csd.reference_number DESC';
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $differences = 'Transaction ID not Found in CiviCRM';
        $transactionRecordFound = false;

        // Add records with no valid contact ID
        if (!empty($dao->contact_id)) {
          // Set values for records with a valid contact ID
          $differences .= ' But Contact Found Using Smart Debit payerReference ' . $dao->payerReference;
          $missingContactID = $dao->contact_id;
          $missingContactName = $dao->display_name;
          $fixmeUrl = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/fix/select', "cid=" . $dao->contact_id . "&reference_number=" . $dao->reference_number, TRUE, NULL, FALSE, TRUE, TRUE);
        }
        elseif (empty($dao->contact_id)) {
          // Set values for records with no valid contact ID in CiviCRM
          $missingContactID = 0;
          $missingContactName = $dao->first_name.' '.$dao->last_name;
          $fixmeUrl = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/fix/select', "reference_number=".$dao->reference_number,  TRUE, NULL, FALSE, TRUE, TRUE);
        }
        // Add the record
        if (!empty($dao->default_amount)) { // We don't support no amount at smartdebit
          $listArray[$dao->smart_debit_id]['fix_me_url'] = $fixmeUrl;
        }
        $listArray[$dao->smart_debit_id]['recordFound'] = $transactionRecordFound;
        $listArray[$dao->smart_debit_id]['contact_id'] = $missingContactID;
        $listArray[$dao->smart_debit_id]['contact_name'] = $missingContactName;
        $listArray[$dao->smart_debit_id]['differences'] = $differences;
        $listArray[$dao->smart_debit_id]['sd_contact_id'] = $dao->payerReference;
        $listArray[$dao->smart_debit_id]['sd_start_date'] = $dao->start_date;
        $listArray[$dao->smart_debit_id]['sd_frequency_type'] = $dao->frequency_type;
        $listArray[$dao->smart_debit_id]['sd_frequency_factor'] = $dao->frequency_factor;
        $listArray[$dao->smart_debit_id]['sd_amount'] = $dao->default_amount;
        $listArray[$dao->smart_debit_id]['sd_contribution_status_id'] = self::formatSDStatus($dao->current_state);
        $listArray[$dao->smart_debit_id]['transaction_id'] = $dao->reference_number;
        $listArray[$dao->smart_debit_id]['sd_frequency'] = $dao->frequency_type;

        // We've found a contact id matching that in smart debit
        // Need to determine if it is a corrupt renewal or something
        // i.e. there is a pending payment for the recurring record and the recurring record itself
      }
      $query = "SELECT FOUND_ROWS()";
      $totalRows = CRM_Core_DAO::singleValueQuery($query);
    }

    if ($checkMissingFromSD) {
      // 4. Transaction Id in Civi but none in Smart Debit
      $arrayIndex = 1;
      $sql  = "
SELECT SQL_CALC_FOUND_ROWS ctrc.id contribution_recur_id,
  ctrc.contact_id,
  cont.display_name,
  ctrc.payment_instrument_id,
  opva.label payment_instrument,
  ctrc.start_date,
  ctrc.amount,
  ctrc.trxn_id,
  ctrc.contribution_status_id 
FROM civicrm_contribution_recur ctrc 
LEFT JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id) 
LEFT JOIN civicrm_option_value opva ON (ctrc.payment_instrument_id = opva.value) 
LEFT JOIN civicrm_option_group opgr ON (opgr.id = opva.option_group_id) 
LEFT JOIN veda_smartdebit_mandates csd ON csd.reference_number = ctrc.trxn_id 
WHERE opgr.name = 'payment_instrument' 
AND   opva.name = 'Direct Debit' 
AND   csd.id IS NULL";
      $sql .= ' ORDER BY csd.reference_number DESC';
      $sql .= ' LIMIT 100';
      $dao = CRM_Core_DAO::executeQuery($sql);

      while ($dao->fetch()) {
        $transactionRecordFound = false;
        $differences = 'Transaction: ' .$dao->trxn_id. ' not Found in Smart Debit';
        $listArray[$arrayIndex]['recordFound']  = $transactionRecordFound;
        $listArray[$arrayIndex]['contact_id']   = $dao->contact_id;
        $listArray[$arrayIndex]['contact_name'] = $dao->display_name;
        $listArray[$arrayIndex]['differences']  = $differences;
        $arrayIndex = $arrayIndex + 1;
      }
      $query = "SELECT FOUND_ROWS()";
      $totalRows = CRM_Core_DAO::singleValueQuery($query);
    }

    $this->assign('totalRows', $totalRows);
    $this->assign('listArray', $listArray);
    $this->assign('fixMeContact', $fixMeContact);
    CRM_Utils_System::setTitle('Smart Debit Reconciliation');
  }

  /**
   * This is the main controlling function for fixing reconciliation records
   * Three possibilities
   *  1. Membership Not Selected, Recurring Not Selected
   *     - Create a Recurring Record only i.e. Must be regular Donor
   *  2. Membership Selected, Recurring Not Selected
   *     - Create a Recurring Record and link to the selected membership
   *  3. Membership Selected, Recurring Selected
   *     - Fix the recurring Record and link the membership and recurring
   *
   * In All cases the recurring details are taken from Smart Debit so its crucial this is correct first
   *
   * @param $params
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function reconcileRecordWithCiviCRM($params) {
    foreach (['contact_id', 'payer_reference'] as $required) {
      if (empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    // Get the Smart Debit details for the payer
    $smartDebitRecord = CRM_Smartdebit_Mandates::getbyReference(['trxn_id' => $params['payer_reference']]);

    // Setup params for the relevant record
    $recurParams['contact_id'] = $params['contact_id'];
    $recurParams['contribution_recur_id'] = (!empty($params['contribution_recur_id']) ? $params['contribution_recur_id'] : NULL);
    $recurParams['frequency_type'] = $smartDebitRecord['frequency_type'];
    $recurParams['frequency_factor'] = $smartDebitRecord['frequency_factor'];
    $recurParams['amount'] = CRM_Smartdebit_Utils::getCleanSmartdebitAmount($smartDebitRecord['default_amount']);
    $recurParams['start_date'] = $smartDebitRecord['start_date'];
    $recurParams['next_sched_contribution_date'] = $smartDebitRecord['start_date'];
    $recurParams['trxn_id'] = $params['payer_reference'];

    $auditlog = CRM_Smartdebit_Api::getAuditLog($params['payer_reference']);
    if (isset($auditlog[0]['description'])) {
      if (strpos($auditlog[0]['description'], 'Created') !== FALSE) {
        if (isset($auditlog[0]['timestamp'])) {
          $recurParams['create_date'] = $auditlog[0]['timestamp'];
        }
      }
    }

    // Set state of recurring contribution (10=live,1=New at SmartDebit)
    if ($smartDebitRecord['current_state'] == CRM_Smartdebit_Api::SD_STATE_LIVE || $smartDebitRecord['current_state'] == CRM_Smartdebit_Api::SD_STATE_NEW) {
      $recurParams['contribution_status_id'] = CRM_Core_Payment_Smartdebit::getInitialContributionStatus(TRUE);
    }
    else {
      $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled');
    }

    // Create / Update recurring contribution
    try {
      $contributionRecurResult = CRM_Smartdebit_Base::createRecurContribution($recurParams);
      $recurId = $contributionRecurResult['id'];
    }
    catch (Exception $e) {
      $message = 'Error creating recurring contribution for contact ('.$params['contact_id'].') ' . $e->getMessage();
      CRM_Core_Session::setStatus($message, CRM_Smartdebit_Settings::TITLE);
      Civi::log()->error('Smartdebit reconcileRecordWithCiviCRM: ' . $message);
      return FALSE;
    }

    // A contribution with just the SD payer reference may exist if an older version of SmartDebit extension was used.
    // If we find one, we need to update it
    // If no contribution exists, we don't need to create one, as the sync job will do that for us.
    $contribution = self::contributionExists($params['payer_reference']);
    if ($contribution) {
      if (!empty($recurId)) {
        $contribution['contribution_recur_id'] = $recurId;
      }
      $contribution['trxn_id'] = $params['payer_reference'].'/'.date('YmdHis', strtotime($recurParams['start_date']));
      $contribution['total_amount'] = $recurParams['amount'];
      $contribution['receive_date'] = $recurParams['start_date'];
      CRM_Smartdebit_Base::createContribution($contribution);
    }

    // Link Membership to recurring contribution
    if (!empty($params['membership_id'])) {
      $params['contribution_recur_id'] = $recurId;
      try {
        self::linkMembershipToRecurContribution($params);
      }
      catch (CiviCRM_API3_Exception $e) {
        $message = 'Error linking membership (' . $params['membership_id'] . ') to recurring contribution (' . $params['contribution_recur_id'] . ') ' . $e->getMessage();
        CRM_Core_Session::setStatus($message, CRM_Smartdebit_Settings::TITLE);
        Civi::log()->error('Smartdebit reconcileRecordWithCiviCRM: ' . $message);
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * This is used when we need to create a linked mem
   * @param $params
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function linkMembershipToRecurContribution($params) {
    foreach ([
               'contact_id',
               'membership_id',
               'contribution_recur_id'
             ] as $required) {

      if (!isset($params[$required]) || empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    $membershipRecord = civicrm_api3('Membership', 'getsingle', [
      'id' => $params['membership_id'],
    ]);

    return civicrm_api3('Membership', 'create', [
      'id' => $params['membership_id'],
      'contact_id' => $params['contact_id'],
      'contribution_recur_id' => $params['contribution_recur_id'],
      'membership_type_id' => $membershipRecord['membership_type_id'],
    ]);
  }

  /**
   * Format Smartdebit Status ID for display
   *
   * @param $sdStatus
   * @return string
   */
  public static function formatSDStatus($sdStatus) {
    switch ($sdStatus) {
      case CRM_Smartdebit_Api::SD_STATE_DRAFT:
        return CRM_Smartdebit_Api::SD_STATES[CRM_Smartdebit_Api::SD_STATE_DRAFT];
      case CRM_Smartdebit_Api::SD_STATE_NEW:
        return CRM_Smartdebit_Api::SD_STATES[CRM_Smartdebit_Api::SD_STATE_NEW];
      case CRM_Smartdebit_Api::SD_STATE_LIVE:
        return CRM_Smartdebit_Api::SD_STATES[CRM_Smartdebit_Api::SD_STATE_LIVE];
      case CRM_Smartdebit_Api::SD_STATE_CANCELLED:
        return CRM_Smartdebit_Api::SD_STATES[CRM_Smartdebit_Api::SD_STATE_CANCELLED];
      case CRM_Smartdebit_Api::SD_STATE_REJECTED:
        return CRM_Smartdebit_Api::SD_STATES[CRM_Smartdebit_Api::SD_STATE_REJECTED];
      default:
        return 'Unknown';
    }
  }

  /**
   * Check if contribution exists for given transaction Id. Return contribution, false otherwise.
   *
   * @param $transactionId
   *
   * @return array|bool
   */
  private static function contributionExists($transactionId) {
    try {
      $contribution = civicrm_api3('Contribution', 'getsingle', [
        'trxn_id' => $transactionId,
      ]);
      return $contribution;
    }
    catch (Exception $e) {
      // Contribution does not exist
      return FALSE;
    }
  }
}
