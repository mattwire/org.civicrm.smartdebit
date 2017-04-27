<?php

/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * addition of contacts to groups.
 *
 * Path: civicrm/smartdebit/reconciliation/list
 */
class CRM_Smartdebit_Form_ReconciliationList extends CRM_Core_Form {
  /* Smart Debit parameters
    address_3 (String, 0 characters )
    first_name (String, 9 characters ) simon1008
    last_name (String, 9 characters ) simon1008
    regular_amount (String, 6 characters ) �7.60
    start_date (String, 10 characters ) 2013-04-01
    county (String, 0 characters )
    address_1 (String, 9 characters ) simon1008
    postcode (String, 7 characters ) B25 8XY
    title (String, 0 characters )
    email_address (String, 18 characters ) simon1008@veda.com
    current_state (String, 2 characters ) 10
    town (String, 9 characters ) simon1008
    payerReference (String, 5 characters ) 36978
    frequency_type (String, 1 characters ) M
    reference_number (String, 8 characters ) 00000573
    address_2 (String, 0 characters )
  */

  /**
   * Build the form
   *
   * @access public
   * @return void
   */
  public function buildQuickForm() {
    if ($this->_flagSubmitted) return;

    // See if a sync is requested
    $sync = CRM_Utils_Array::value('sync', $_GET, '');
    if ($sync) {
      // Do a sync
      $mandatesList = CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails();
      CRM_Smartdebit_Sync::updateSmartDebitMandatesTable($mandatesList);

      // Redirect back to this form
      $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', 'reset=1');
      CRM_Utils_System::redirect($url);
      return;
    }

    // Check if we have any data to use, otherwise we'll need to sync with Smartdebit
    $query = "SELECT COUNT(*) FROM veda_smartdebit_mandates";
    $count = CRM_Core_DAO::singleValueQuery($query);
    $this->assign('totalMandates', $count);
    if ($count == 0) {
      // Have not done a sync.  Display state and add button to perform sync
      $queryParams = 'sync=1&reset=1';
      $redirectUrlContinue  = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', $queryParams);
      $buttons[] = array(
        'type' => 'next',
        'js' => array('onclick' => "location.href='{$redirectUrlContinue}'; return false;"),
        'name' => ts('Sync Now'),
      );
      $this->addButtons($buttons);
      return;
    }

    $checkAmount = CRM_Utils_Array::value('checkAmount', $_GET);
    $checkFrequency = CRM_Utils_Array::value('checkFrequency', $_GET);
    $checkStatus = CRM_Utils_Array::value('checkStatus', $_GET);
    $checkPayerReference = CRM_Utils_Array::value('checkPayerReference', $_GET);
    $checkMissingFromCivi = CRM_Utils_Array::value('checkMissingFromCivi', $_GET);
    $checkMissingFromSD = CRM_Utils_Array::value('checkMissingFromSD', $_GET);
    // Only display smart debit records that have a matching contact in CiviCRM if hasContact=1
    $hasAmount = CRM_Utils_Array::value('hasAmount', $_GET);
    $hasContact = CRM_Utils_Array::value('hasContact', $_GET);

    $listArray = array();
    $fixMeContact = FALSE;
    $totalRows = NULL;
    // Get contribution Status options
    $contributionStatusOptions = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');

    // The following differences are highlighted
    // 1. Transaction Id in Smart Debit and Civi for the same contact
    // 2. Transaction Id in Smart Debit and Civi for different contacts
    // 3. Transaction Id in Smart Debit but none found in Civi
    // 4. Transaction Id in Civi but none in Smart Debit

    // Loop through Contributions and Highlight Discrepencies
    //foreach ($smartDebitArray as $key => $smartDebitRecord) {

    // Start Here
    if ($checkAmount || $checkFrequency || $checkStatus || $checkPayerReference) {
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
  csd.regular_amount,
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
AND   opva.label = 'Direct Debit' ";

      $dao = CRM_Core_DAO::executeQuery( $sql);

      // Remove first 2 characters (Ascii characters 194 & 163)
      while ($dao->fetch()) {
        // Smart Debit Record Found
        // 1. Transaction Id in Smart Debit and Civi for the same contact

        $transactionRecordFound = true;
        $difference['amount'] = $difference['frequency'] = $difference['status'] = $difference['contact'] = FALSE;

        if ($checkAmount) {
          // Check that amount in CiviCRM matches amount in Smart Debit
          if ($dao->regular_amount != $dao->amount) {
            $difference['amount'] = TRUE;
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

        /* Smart Debit statuses are as follows
          0 Draft
          1 New
          10 Live
          11 Cancelled
          12 Rejected
         *
         */
        // First case check if Smart Debit is new or live then CiviCRM is in progress
        if ($checkStatus) {
          if (($dao->current_state == 10 || $dao->current_state == 1) && ($dao->contribution_status_id != 5)) {
            $difference['status'] = TRUE;
          }
          // Recurring record active in Civi, but smart debit record is not active
          if (!($dao->current_state == 10 || $dao->current_state == 1) && ($dao->contribution_status_id == 5)) {
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
        if ($difference['amount'] || $difference['frequency'] || $difference['status'] || $difference['contact']) {
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
          $listArray[$dao->smart_debit_id]['sd_amount']             = $dao->regular_amount;
          $listArray[$dao->smart_debit_id]['contribution_status_id'] = $contributionStatusOptions[$dao->contribution_status_id];
          $listArray[$dao->smart_debit_id]['sd_contribution_status_id'] = self::formatSDStatus($dao->current_state);
          $listArray[$dao->smart_debit_id]['transaction_id']        = $dao->trxn_id;
          $listArray[$dao->smart_debit_id]['differences']           = $differences;
          $fixmeurl = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/fix/select', "cid=".$dao->contact_id."&reference_number=".$dao->reference_number,  TRUE, NULL, FALSE, TRUE, TRUE);
          if ($difference['amount'] || $difference['frequency'] || $difference['status']) {
            // Can't fix contact Id
            $listArray[$dao->smart_debit_id]['fix_me_url'] = $fixmeurl;
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
  csd1.regular_amount,
  csd1.frequency_type,
  csd1.frequency_factor,
  csd1.current_state,
  csd1.payerReference,
  csd1.start_date,
  csd1.reference_number,
  csd1.id as smart_debit_id,
  csd1.first_name,
  csd1.last_name 
FROM veda_smartdebit_mandates csd1 
LEFT JOIN civicrm_contribution_recur ctrc ON ctrc.trxn_id = csd1.reference_number 
LEFT JOIN civicrm_contact contact ON contact.id = csd1.payerReference 
WHERE ( csd1.current_state = %1 OR csd1.current_state = %2 ) 
AND ctrc.id IS NULL";
      // Filter records that have an amount recorded against them or not
      if ($hasAmount) {
        $sql .= " AND (COALESCE(csd1.regular_amount, '') != '')";
      }
      else {
        $sql .= " AND (COALESCE(csd1.regular_amount, '') = '')";
      }
      // Filter records with no valid contact ID
      if ($hasContact) {
        $sql .= " AND contact.id IS NOT NULL";
      }
      else {
        $sql .= " AND contact.id IS NULL";
      }
      $params = array( 1 => array( 10, 'Int' ), 2 => array(1, 'Int') );
      $dao = CRM_Core_DAO::executeQuery( $sql, $params);
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
        $listArray[$dao->smart_debit_id]['fix_me_url'] = $fixmeUrl;
        $listArray[$dao->smart_debit_id]['recordFound'] = $transactionRecordFound;
        $listArray[$dao->smart_debit_id]['contact_id'] = $missingContactID;
        $listArray[$dao->smart_debit_id]['contact_name'] = $missingContactName;
        $listArray[$dao->smart_debit_id]['differences'] = $differences;
        $listArray[$dao->smart_debit_id]['sd_contact_id'] = $dao->payerReference;
        $listArray[$dao->smart_debit_id]['sd_start_date'] = $dao->start_date;
        $listArray[$dao->smart_debit_id]['sd_frequency_type'] = $dao->frequency_type;
        $listArray[$dao->smart_debit_id]['sd_frequency_factor'] = $dao->frequency_factor;
        $listArray[$dao->smart_debit_id]['sd_amount'] = $dao->regular_amount;
        $listArray[$dao->smart_debit_id]['sd_contribution_status_id'] = self::formatSDStatus($dao->current_state);
        $listArray[$dao->smart_debit_id]['transaction_id'] = $dao->reference_number;
        $listArray[$dao->smart_debit_id]['sd_frequency'] = $dao->frequency_type;

        // We've found a contact id matching that in smart debit
        // Need to determine if its a correupt renewal or something
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
AND   opva.label = 'Direct Debit' 
AND   csd.id IS NULL LIMIT 100";

      $dao = CRM_Core_DAO::executeQuery( $sql );

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
    if ($checkMissingFromCivi || $checkMissingFromSD) {
      $title = 'Showing '.count($listArray).' of '.$totalRows.' Difference(s)';
    } else {
      $title = 'Found '.count($listArray).' Difference(s)';
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
   */
  static function reconcileRecordWithCiviCRM($params) {
    foreach (array(
               'contact_id',
               'payer_reference') as $required) {

      if (empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    // Get the Smart Debit details for the payer
    $smartDebitResponse = CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails($params['payer_reference']);

    foreach ($smartDebitResponse as $key => $smartDebitRecord) {
      // Setup params for the relevant record
      $recurParams['contact_id'] = $params['contact_id'];
      $recurParams['contribution_recur_id'] = (!empty($params['contribution_recur_id']) ? $params['contribution_recur_id'] : NULL);
      $recurParams['frequency_type'] = $smartDebitRecord['frequency_type'];
      $recurParams['frequency_factor'] = $smartDebitRecord['frequency_factor'];
      $recurParams['amount'] = CRM_Smartdebit_Utils::getCleanSmartdebitAmount($smartDebitRecord['regular_amount']);
      $recurParams['start_date'] = $smartDebitRecord['start_date'].' 00:00:00';
      $recurParams['next_sched_contribution'] = $smartDebitRecord['start_date'].' 00:00:00';
      $recurParams['trxn_id'] = $params['payer_reference'];
      $recurParams['processor_id'] = $params['payer_reference'];

      // Set state of recurring contribution (10=live,1=New at SmartDebit)
      if ($smartDebitRecord['current_state'] == 10 || $smartDebitRecord['current_state'] == 1) {
        $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
      }
      else {
        $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Cancelled');
      }

      $recurId = 0;
      $membershipId = 0;

      // Create / Update recurring contribution
      try {
        $result = CRM_Smartdebit_Base::createRecurContribution($recurParams);
        $recurId = $result['id'];
      }
      catch (Exception $e) {
        $recurId = -1;
        CRM_Core_Session::setStatus("Error creating recurring contribution for contact (".$params['contact_id'].") " . $e->getMessage(), 'Smart Debit');
      }

      // Link Membership to recurring contribution
      if (!empty($params['membership_id'])) {
        $params['contribution_recur_id'] = $recurId;
        try {
          $result = self::linkMembershipToRecurContribution($params);
          $membershipId = $result['id'];
        }
        catch (Exception $e) {
          $membershipId = -1;
          CRM_Core_Session::setStatus("Error linking membership (".$params['membership_id'].") to recurring contribution (".$params['contribution_recur_id'].") " . $e->getMessage(), 'Smart Debit');
        }
      }

      // Return true if we succeeded, false otherwise
      // Set return value
      if ($recurId >= 0 && $membershipId >= 0) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
  }

  /**
   * This is used when we need to create a linked mem
   * @param $params
   *
   */
  static function linkMembershipToRecurContribution($params) {
    foreach (array(
               'contact_id',
               'membership_id',
               'contribution_recur_id') as $required) {

      if (!isset($params[$required]) || empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    try {
      $membershipRecord = civicrm_api3('Membership', 'getsingle', array(
        'id' => $params['membership_id'],
      ));
    }
    catch (Exception $e) {
      // Failed to find membership.  This should never happen unless we get a malformed submission
      CRM_Core_Session::setStatus('Failed to find membership with Id: ' . $params['membership_id'], 'Smart Debit: Link Membership');
      return FALSE;
    }

    return civicrm_api3('Membership', 'create', array(
      'sequential' => 1,
      'id' => $params['membership_id'],
      'contact_id' => $params['contact_id'],
      'contribution_recur_id' => $params['contribution_recur_id'],
      'membership_type_id' => $membershipRecord['membership_type_id'],
    ));
  }

  /**
   * Format Smartdebit Status ID for display
   * @param $sdStatus
   * @return string
   */
  static function formatSDStatus($sdStatus) {
    switch ($sdStatus) {
      case 0: // Draft
        return 'Draft';
      case 1: // New
        return 'New';
      case 10: // Live
        return 'Live';
      case 11: // Cancelled
        return 'Cancelled';
      case 12: // Rejected
        return 'Rejected';
    }
  }
}
