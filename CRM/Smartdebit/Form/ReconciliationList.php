<?php

/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * addition of contacts to groups.
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
    // See if a sync is requested
    $sync = CRM_Utils_Array::value('sync', $_GET, '');
    if ($sync) {
      // Do a sync
      try {
        $result = civicrm_api3('Smartdebit', 'refreshsdmandatesincivi', array(
          'sequential' => 1,
        ));
      }
      catch (Exception $e) {
        CRM_Core_Session::setStatus($e->getMessage(), 'Smart Debit');
      }
      $url = CRM_Utils_System::url('civicrm/smartdebit/reconciliation/list', 'reset=1');
      CRM_Utils_System::redirect($url);
      return;
    }

    // Check if we have any data to use, otherwise we'll need to sync with Smartdebit
    $query = "SELECT COUNT(*) FROM veda_smartdebit_refresh";
    $count = CRM_Core_DAO::singleValueQuery($query);
    $this->assign('totalMandates', $count);
    if ($count == 0) {
      // Have not done a sync.  Display state and add button to perform sync
      $queryParams = 'sync=1&reset=1';
      $redirectUrlContinue  = CRM_Utils_System::url('civicrm/smartdebit/reconciliation/list', $queryParams);
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
    $totalRows = NULL;

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
INNER JOIN veda_smartdebit_refresh csd ON csd.reference_number = ctrc.trxn_id 
WHERE opgr.name = 'payment_instrument' 
AND   opva.label = 'Direct Debit' ";

      $dao = CRM_Core_DAO::executeQuery( $sql);

      // Remove first 2 characters (Ascii characters 194 & 163)
      while ($dao->fetch()) {
        // Smart Debit Record Found
        // 1. Transaction Id in Smart Debit and Civi for the same contact

        $transactionRecordFound = true;
        $different = false;
        $differences = '';
        $separator = '';
        $separatorCharacter = ' | ';

        if ($checkAmount) {
          // Check that amount in CiviCRM matches amount in Smart Debit
          if ($dao->regular_amount != $dao->amount) {
            $different = true;
            $differences .= 'Amount';
            $separator = $separatorCharacter;
          }
        }

        if ($checkFrequency) {
          // Check that frequency in CiviCRM matches frequency in Smart Debit
          if ($dao->frequency_type == 'W' && ($dao->frequency_unit != 'day' || $dao->frequency_interval % 7 != 0) ) {
            $different = true;
            $differences .= 'Frequency';
            $separator = $separatorCharacter;
          }
          elseif ($dao->frequency_type == 'M' && $dao->frequency_unit != 'month' ) {
            $different = true;
            $differences .= 'Frequency';
            $separator = $separatorCharacter;
          }
          elseif ($dao->frequency_type == 'Q' && ($dao->frequency_unit != 'month' && $dao->frequency_interval % 3 != 0)) {
            $different = true;
            $differences .= 'Frequency';
            $separator = $separatorCharacter;
          }
          elseif ($dao->frequency_type == 'Y' && $dao->frequency_unit != 'year' ) {
            $different = true;
            $differences .= 'Frequency';
            $separator = $separatorCharacter;
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
            $different = true;
            $differences .= $separator. 'Status';
            $separator = $separatorCharacter;
          }
          // Recurring record active in Civi, but smart debit record is not active
          if (!($dao->current_state == 10 || $dao->current_state == 1) && ($dao->contribution_status_id == 5)) {
            $different = true;
            $differences .= $separator. 'Status';
            $separator = $separatorCharacter;
          }
        }

        // 2. Transaction Id in Smart Debit and Civi for different contacts
        if ($checkPayerReference) {
          if ($dao->payerReference != $dao->contact_id) {
            $different = true;
            $differences .= $separator. 'Payer Reference';
            $separator = $separatorCharacter;
          }
        }

        // If different then
        if ($different) {
          $financialType = '';
          if ($dao->financial_type_id) {
            $financialType = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $dao->financial_type_id, 'name', 'id');
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
          $listArray[$dao->smart_debit_id]['frequency']             = $dao->frequency_unit;
          $listArray[$dao->smart_debit_id]['sd_frequency']          = $dao->frequency_type;
          $listArray[$dao->smart_debit_id]['amount']                = $dao->amount;
          $listArray[$dao->smart_debit_id]['sd_amount']             = $dao->regular_amount;
          $listArray[$dao->smart_debit_id]['contribution_status_id']    = $dao->contribution_status_id;
          $listArray[$dao->smart_debit_id]['sd_contribution_status_id'] = self::formatSDStatus($dao->current_state);
          $listArray[$dao->smart_debit_id]['transaction_id']        = $dao->trxn_id;
          $listArray[$dao->smart_debit_id]['differences']           = $differences;
          $fixmeurl = CRM_Utils_System::url('civicrm/smartdebit/reconciliation/fixmissingcivi', "cid=".$dao->contact_id."&reference_number=".$dao->reference_number,  TRUE, NULL, FALSE, TRUE, TRUE);
          $listArray[$dao->smart_debit_id]['fix_me_url']						= $fixmeurl;
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
FROM veda_smartdebit_refresh csd1 
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
          $fixmeUrl = CRM_Utils_System::url('civicrm/smartdebit/reconciliation/fixmissingcivi', "cid=" . $dao->contact_id . "&reference_number=" . $dao->reference_number, TRUE, NULL, FALSE, TRUE, TRUE);
        }
        elseif (empty($dao->contact_id)) {
          // Set values for records with no valid contact ID in CiviCRM
          $missingContactID = 0;
          $missingContactName = $dao->first_name.' '.$dao->last_name;
          $fixmeUrl = CRM_Utils_System::url('civicrm/smartdebit/reconciliation/fixmissingcivi', "reference_number=".$dao->reference_number,  TRUE, NULL, FALSE, TRUE, TRUE);
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
LEFT JOIN veda_smartdebit_refresh csd ON csd.reference_number = ctrc.trxn_id 
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
    CRM_Utils_System::setTitle('Smart Debit Reconciliation');
  }

  /**
   * @param $amount
   * @return mixed
   */
  static function getCleanSmartdebitAmount($amount) {
    $numeric_filtered = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    return($numeric_filtered);
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
   * FIXME: This function is not used anywhere
   * @param $params
   */
  static function repair_missing_from_civicrm_record($params) {
    foreach (array(
               'contact_id',
               'payer_reference') as $required) {

      if (!isset($params[$required]) || empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    // Get the Smart Debit details for the payer
    $smartDebitResponse = CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails($params['payer_reference']);

    foreach ($smartDebitResponse as $key => $smartDebitRecord) {
      // Setup params for the relevant rec
      list($params['recur_frequency_unit'], $params['recur_frequency_interval']) =
        CRM_Smartdebit_Base::translateSmartdebitFrequencytoCiviCRM($smartDebitRecord['frequency_type'], $smartDebitRecord['frequency_factor']);
      $params['amount'] = self::getCleanSmartdebitAmount($smartDebitRecord['regular_amount']);
      $params['recur_start_date'] = $smartDebitRecord['start_date'].' 00:00:00';
      $params['recur_next_payment_date'] = $smartDebitRecord['start_date'].' 00:00:00';
      $params['payment_processor_id'] = CRM_Core_Payment_Smartdebit::getSmartdebitPaymentProcessorID();
      $params['payment_instrument_id'] = CRM_Smartdebit_Base::getDefaultPaymentInstrumentID();
      $params['trxn_id'] = $params['payer_reference'];
      $params['current_state'] = $smartDebitRecord['current_state'];
      list($y, $m, $d) = explode('-', $smartDebitRecord['start_date']);
      $params['cycle_day'] = $d;

      // First Check if a recurring record has beeen selected
      if ((!isset($params['contribution_recur_id']) || empty($params['contribution_recur_id']))) {
        // Create the Recurring
        self::create_recur($params);
      } else {
        // Repair the Recurring
        self::repair_recur($params);
      }

      /* First Check if the membership has beeen selected */
      if ((isset($params['membership_id']) && !empty($params['membership_id']))) {
        // Link it to the Recurring Record
        self::link_membership_to_recur($params);
      }
    }
  }

  /**
   * This is used when the fix process is used on the reconciliation
   * It should ensure the recur details match those of the smart debit record
   *
   * FIXME: Not tested/used
   * @param $params
   */
  static function repair_recur(&$params) {
    foreach (array(
               'contribution_recur_id',
               'contact_id',
               'recur_frequency_interval',
               'amount',
               'recur_start_date',
               'recur_next_payment_date',
               'recur_frequency_unit',
               'payment_processor_id',
               'payment_instrument_id',
               'trxn_id',
               'cycle_day') as $required) {

      if (!isset($params[$required]) || empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    $contribution_status_id = 5; // In Progress
    if (!($params['current_state'] == 10 || $params['current_state'] == 1)) {
      $contribution_status_id = 3;
    }
    // Create contribution recur record
    $recurParams = array(
      'version' => 3,
      'contribution_recur_id' => $params['contribution_recur_id'],
      'id' => $params['contribution_recur_id'],
      'contact_id' => $params['contact_id'],
      'frequency_interval' => $params['recur_frequency_interval'],
      'amount' => $params['amount'], /* TODO Need to find the amount to charge */
      'contribution_status_id' => $contribution_status_id,
      'start_date' => $params['recur_start_date'],
      'next_sched_contribution' => $params['recur_next_payment_date'],
      'auto_renew' => '1',
      'currency' => 'GBP',
      'frequency_unit' => $params['recur_frequency_unit'],
      'payment_processor_id' => $params['payment_processor_id'],
      'payment_instrument_id' => $params['payment_instrument_id'],
      'contribution_type_id' => '2', /* TODO Get the contribution type ID for recurring memberships */
      'trxn_id' => $params['trxn_id'],
      'create_date' => $params['recur_start_date'],
      'cycle_day' => $params['cycle_day'],
    );
    $recurResult = civicrm_api("ContributionRecur","create", $recurParams);

    // Populate the membership id on repair recur
    $params['contribution_recur_id'] = $recurResult['id'];

    if( $params['contribution_recur_id'] && $params['membership_id']) {
      $columnExists = CRM_Core_DAO::checkFieldExists('civicrm_contribution_recur', 'membership_id');
      if($columnExists) {
        $query = "
                UPDATE civicrm_contribution_recur
                SET membership_id = %1
                WHERE id = %2 ";

        $query_params = array( 1 => array( $params['membership_id'], 'Int' ), 2 => array($params['contribution_recur_id'], 'Int') );
        $dao = CRM_Core_DAO::executeQuery($query, $query_params);
      }
    }
  }

  /**
   * This is used when we need to create a new recurring record
   * @param $params
   *
   * FIXME: not tested/used
   */
  static function create_recur(&$params) {
    foreach (array(
               'contact_id',
               'recur_frequency_interval',
               'amount',
               'recur_start_date',
               'recur_next_payment_date',
               'recur_frequency_unit',
               'payment_processor_id',
               'payment_instrument_id',
               'trxn_id',
               'cycle_day') as $required) {

      if (!isset($params[$required]) || empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }
    $contribution_status_id = 5; // In Progress
    if (!($params['current_state'] == 10 || $params['current_state'] == 1)) {
      $contribution_status_id = 3;
    }
    // Create contribution recur record
    $recurParams = array(
      'version' => 3,
      'contact_id' => $params['contact_id'],
      'frequency_interval' => $params['recur_frequency_interval'],
      'amount' => $params['amount'], /* TODO Need to find the amount to charge */
      'contribution_status_id' => $contribution_status_id,
      'start_date' => $params['recur_start_date'],
      'next_sched_contribution' => $params['recur_next_payment_date'],
      'auto_renew' => '1',
      'currency' => 'GBP',
      'frequency_unit' => $params['recur_frequency_unit'],
      'payment_processor_id' => $params['payment_processor_id'],
      'payment_instrument_id' => $params['payment_instrument_id'],
      'contribution_type_id' => '2', /* TODO Get the contribution type ID for recurring memberships */
      'trxn_id' => $params['trxn_id'],
      'create_date' => $params['recur_start_date'],
      'cycle_day' => $params['cycle_day'],
    );
    $recurResult = civicrm_api("ContributionRecur","create", $recurParams);

    $params['contribution_recur_id'] = $recurResult['id'];
    // // Populate the membership id on create recur
    if( $params['contribution_recur_id'] && $params['membership_id'] ) {
      $columnExists = CRM_Core_DAO::checkFieldExists('civicrm_contribution_recur', 'membership_id');
      if($columnExists) {
        $query = "
                UPDATE civicrm_contribution_recur
                SET membership_id = %1
                WHERE id = %2 ";

        $query_params = array( 1 => array( $params['membership_id'], 'Int' ), 2 => array($params['contribution_recur_id'], 'Int') );
        $dao = CRM_Core_DAO::executeQuery($query, $query_params);
      }
    }
  }

  /**
   * This is used when we need to create a linked mem
   * @param $params
   *
   * FIXME: Not tested/used
   */
  static function link_membership_to_recur(&$params) {
    foreach (array(
               'contact_id',
               'membership_id',
               'contribution_recur_id') as $required) {

      if (!isset($params[$required]) || empty($params[$required])) {
        throw new InvalidArgumentException("Missing params[$required]");
      }
    }

    // Update the source table to say we're done
    $selectDDSql     =  " UPDATE civicrm_membership ";
    $selectDDSql     .= " SET contribution_recur_id = %3 ";
    $selectDDSql     .= " WHERE id = %1 ";
    $selectDDSql     .= " AND contact_id = %2 ";
    $selectDDParams  = array( 1 => array( $params['membership_id'] , 'Integer' )
    , 2 => array( $params['contact_id'] , 'Integer' )
    , 3 => array( $params['contribution_recur_id'] , 'Integer' )
    );
    $daoMembershipType = CRM_Core_DAO::executeQuery( $selectDDSql, $selectDDParams );
  }

  static function insertSmartdebitToTable() {
    // if the civicrm_sd table exists, then empty it
    $emptySql = "TRUNCATE TABLE `veda_smartdebit_refresh`";
    CRM_Core_DAO::executeQuery($emptySql);

    // Get payer contact details
    $smartDebitArray = CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails();
    if (empty($smartDebitArray)) {
      return FALSE;
    }
    // Insert mandates into table
    foreach ($smartDebitArray as $key => $smartDebitRecord) {
      $sql = "INSERT INTO `veda_smartdebit_refresh`(
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
            VALUES (%1,%2,%3,%4,%5,%6,%7,%8,%9,%10,%11,%12,%13,%14,%15,%16,%17, %18)";
      $params = array(
        1 => array( self::getArrayFieldValue($smartDebitRecord, 'title', 'NULL'), 'String' ),
        2 => array( self::getArrayFieldValue($smartDebitRecord, 'first_name', 'NULL'), 'String' ),
        3 => array( self::getArrayFieldValue($smartDebitRecord, 'last_name', 'NULL'), 'String' ),
        4 => array( self::getArrayFieldValue($smartDebitRecord, 'email_address', 'NULL'),  'String'),
        5 => array( self::getArrayFieldValue($smartDebitRecord, 'address_1', 'NULL'), 'String' ),
        6 => array( self::getArrayFieldValue($smartDebitRecord, 'address_2', 'NULL'), 'String' ),
        7 => array( self::getArrayFieldValue($smartDebitRecord, 'address_3', 'NULL'), 'String' ),
        8 => array( self::getArrayFieldValue($smartDebitRecord, 'town', 'NULL'), 'String' ),
        9 => array( self::getArrayFieldValue($smartDebitRecord, 'county', 'NULL'), 'String' ),
        10 => array( self::getArrayFieldValue($smartDebitRecord, 'postcode', 'NULL'), 'String' ),
        11 => array( self::getCleanSmartdebitAmount(self::getArrayFieldValue($smartDebitRecord, 'first_amount', 'NULL')), 'String' ),
        12 => array( self::getCleanSmartdebitAmount(self::getArrayFieldValue($smartDebitRecord, 'regular_amount', 'NULL')), 'String' ),
        13 => array( self::getArrayFieldValue($smartDebitRecord, 'frequency_type', 'NULL'), 'String' ),
        14 => array( self::getArrayFieldValue($smartDebitRecord, 'frequency_factor', 'NULL'), 'Int' ),
        15 => array( self::getArrayFieldValue($smartDebitRecord, 'start_date', 'NULL'), 'String' ),
        16 => array( self::getArrayFieldValue($smartDebitRecord, 'current_state', 'NULL'), 'Int' ),
        17 => array( self::getArrayFieldValue($smartDebitRecord, 'reference_number', 'NULL'), 'String' ),
        18 => array( self::getArrayFieldValue($smartDebitRecord, 'payerReference', 'NULL'), 'String' ),
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    $mandateFetchedCount = count($smartDebitArray);
    return $mandateFetchedCount;
  }

  /**
   * @param $array
   * @param $field
   * @param $value
   * @return mixed
   */
  static function getArrayFieldValue($array, $field, $value) {
    if (!isset($array[$field])) {
      return $value;
    }
    else {
      return $array[$field];
    }
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
