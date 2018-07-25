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
 * Class CRM_Smartdebit_Base
 *
 * Base class for classes that interact with Xero using push and pull methods.
 */
class CRM_Smartdebit_Base
{

  const TABLENAME = 'veda_smartdebit';

  /**
   * Generate a Direct Debit Reference (BACS reference)
   *
   * @return string
   */
  public static function getDDIReference() {
    $tempDDIReference = CRM_Utils_String::createRandom(16, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

    $insertSql = "
        INSERT INTO " . CRM_Smartdebit_Base::TABLENAME . " 
        (ddi_reference, created)
        VALUES
        (%1, NOW())
        ";
    $insertParams = array(1 => array((string)$tempDDIReference , 'String'));
    CRM_Core_DAO::executeQuery($insertSql, $insertParams);

    // Now get the ID for the record we've just created and create a sequenced DDI Reference Number
    $selectSql  = " SELECT id ";
    $selectSql .= " FROM " . CRM_Smartdebit_Base::TABLENAME . " cdd ";
    $selectSql .= " WHERE cdd.ddi_reference = %1 ";
    $selectParams  = array( 1 => array($tempDDIReference , 'String'));
    $dao = CRM_Core_DAO::executeQuery($selectSql, $selectParams);
    $dao->fetch();

    $directDebitId = $dao->id;

    // Replace the DDI Reference Number with our new unique sequenced version
    $transactionPrefix = CRM_Smartdebit_Settings::getValue('transaction_prefix');
    $DDIReference      = $transactionPrefix . sprintf("%08s", $directDebitId);

    $updateSql  = " UPDATE " . CRM_Smartdebit_Base::TABLENAME . " cdd ";
    $updateSql .= " SET cdd.ddi_reference = %0 ";
    $updateSql .= " WHERE cdd.id = %1 ";

    $updateParams = array(
      array((string) $DDIReference, 'String'),
      array((int) $directDebitId, 'Int'),
    );
    CRM_Core_DAO::executeQuery($updateSql, $updateParams);

    return $DDIReference;
  }

  /**
   * @param $params
   *
   * @return array
   */
  public static function getDDFormDetails($params) {
    $ddDetails = array();

    if (!empty($params['ddi_reference'])) {
      $sql = "
SELECT * FROM " . CRM_Smartdebit_Base::TABLENAME . " 
WHERE ddi_reference = '{$params['ddi_reference']}'
";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $ddDetails['created'] = $dao->created;
        $ddDetails['bank_name'] = $dao->bank_name;
        $ddDetails['branch'] = $dao->branch;
        $ddDetails['address1'] = $dao->address1;
        $ddDetails['address2'] = $dao->address2;
        $ddDetails['address3'] = $dao->address3;
        $ddDetails['address4'] = $dao->address4;
        $ddDetails['town'] = $dao->town;
        $ddDetails['county'] = $dao->county;
        $ddDetails['postcode'] = $dao->postcode;
        $ddDetails['first_collection_date'] = $dao->first_collection_date;
        $ddDetails['preferred_collection_day'] = $dao->preferred_collection_day;
        $ddDetails['confirmation_method'] = $dao->confirmation_method;
        $ddDetails['ddi_reference'] = $dao->ddi_reference;
      }
    }

    $ddDetails['account_holder'] = CRM_Utils_Array::value('account_holder', $params);
    $ddDetails['bank_account_number'] = CRM_Utils_Array::value('bank_account_number', $params);
    $ddDetails['bank_identification_number'] = CRM_Utils_Array::value('bank_identification_number', $params);
    $ddDetails['bank_name'] = CRM_Utils_Array::value('bank_name', $params, $ddDetails['bank_name']);

    $ddDetails['sun'] = (int) CRM_Smartdebit_Settings::getValue('service_user_number');

    // Format as array of characters for display
    $ddDetails['sunParts'] = str_split($ddDetails['sun']);
    $ddDetails['binParts'] = str_split($ddDetails['bank_identification_number']);

    $ddDetails['company_address'] = CRM_Smartdebit_Base::getCompanyAddress();
    $date = new DateTime();

    $ddDetails['today'] = $date->format('Ymd');
    $ddDetails['notice_period'] = CRM_Smartdebit_Settings::getValue('notice_period');

    return $ddDetails;
  }

  /**
   * Called after contribution page has been completed
   * Main purpose is to tidy the contribution
   * And to setup the relevant Direct Debit Mandate Information
   *
   * @param $objects
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function completeDirectDebitSetup( $params )  {
    // Create an activity to indicate Direct Debit Sign up
    CRM_Smartdebit_Base::createDDSignUpActivity($params);

    // Set the DD Record to be complete
    $sql = "
UPDATE " . CRM_Smartdebit_Base::TABLENAME . " 
SET    complete_flag = 1
WHERE  ddi_reference = %0";

    CRM_Core_DAO::executeQuery($sql, array(array((string)$params['trxn_id'], 'String'))
    );
  }

  /**
   * Calculate the earliest possible collection date based on todays date plus the collection interval setting.
   * @param $collectionDay
   *
   * @return DateTime
   */
  public static function firstCollectionDate($collectionDay) {
    // Initialise date objects with today's date
    $today                    = new DateTime();
    $earliestCollectionDate   = new DateTime();
    $collectionDateThisMonth  = new DateTime();
    $collectionDateNextMonth  = new DateTime();
    $collectionDateMonthAfter = new DateTime();
    $collectionInterval = (int) CRM_Smartdebit_Settings::getValue('collection_interval');

    // Calculate earliest possible collection date
    $earliestCollectionDate->add(new DateInterval( 'P'.$collectionInterval.'D' ));

    // Get the current year, month and next month to create the 2 potential collection dates
    $todaysMonth = (int) $today->format('m');
    $nextMonth   = (int) $today->format('m') + 1;
    $monthAfter  = (int) $today->format('m') + 2;
    $todaysYear  = (int) $today->format('Y');

    $collectionDateThisMonth->setDate($todaysYear, $todaysMonth, $collectionDay);
    $collectionDateNextMonth->setDate($todaysYear, $nextMonth, $collectionDay);
    $collectionDateMonthAfter->setDate($todaysYear, $monthAfter, $collectionDay);

    // Calculate first collection date
    if ($earliestCollectionDate > $collectionDateNextMonth) {
      // Month after next
      return $collectionDateMonthAfter;
    }
    elseif ($earliestCollectionDate > $collectionDateThisMonth) {
      // Next Month
      return $collectionDateNextMonth;
    }
    else {
      // This month
      return $collectionDateThisMonth;
    }
  }

  /**
   * Format collection day like 1st, 2nd, 3rd, 4th etc.
   *
   * @param $collectionDay
   *
   * @return string
   */
  private static function formatPreferredCollectionDay($collectionDay) {
    $ends = array( 'th'
    , 'st'
    , 'nd'
    , 'rd'
    , 'th'
    , 'th'
    , 'th'
    , 'th'
    , 'th'
    , 'th'
    );
    if ((($collectionDay%100) >= 11) && (($collectionDay%100) <= 13))
      $abbreviation = $collectionDay . 'th';
    else
      $abbreviation = $collectionDay . $ends[$collectionDay % 10];

    return $abbreviation;
  }

  /**
   * Function will return the possible array of collection days with formatted label
   *
   * @return mixed
   */
  public static function getCollectionDaysOptions() {
    $intervalDate = new DateTime();
    $interval = (int) CRM_Smartdebit_Settings::getValue('collection_interval');

    $intervalDate->modify("+$interval day");
    $intervalDay = $intervalDate->format('d');

    $collectionDays = CRM_Smartdebit_Settings::getValue('collection_days');

    // Split the array
    $tempCollectionDaysArray  = explode(',', $collectionDays);
    $earlyCollectionDaysArray = array();
    $lateCollectionDaysArray  = array();

    // Build 2 arrays around next collection date
    foreach($tempCollectionDaysArray as $key => $value){
      if ($value >= $intervalDay) {
        $earlyCollectionDaysArray[] = $value;
      }
      else {
        $lateCollectionDaysArray[]  = $value;
      }
    }
    // Merge arrays for select list
    $allCollectionDays = array_merge($earlyCollectionDaysArray, $lateCollectionDaysArray);

    // Loop through and format each label
    $collectionDaysArray = array();
    foreach($allCollectionDays as $key => $value){
      $collectionDaysArray[$value] = self::formatPreferredCollectionDay($value);
    }
    return $collectionDaysArray;
  }

  /**
   * Function will return the possible confirm by options
   *
   * @return mixed
   */
  static function getConfirmByOptions() {
    $confirmBy['EMAIL'] = (boolean) CRM_Smartdebit_Settings::getValue('confirmby_email');
    $confirmBy['POST'] = (boolean) CRM_Smartdebit_Settings::getValue('confirmby_post');
    if (!empty($confirmBy['EMAIL'])) {
      $confirmBy['EMAIL'] = 'Email';
    }
    else {
      unset($confirmBy['EMAIL']);
    }
    if (!empty($confirmBy['POST'])) {
      $confirmBy['POST'] = 'Post';
    }
    else {
      unset($confirmBy['POST']);
    }
    return $confirmBy;
  }

  /**
   * Create a Direct Debit Sign Up Activity for contact
   *
   * @param $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function createDDSignUpActivity($params) {
    if ($params['confirmation_method'] == 'POST') {
      $activityTypeLetterID = (int) CRM_Smartdebit_Settings::getValue('activity_type_letter');
      $activityLetterParams = array(
        'source_contact_id'  => $params['contactID'],
        'target_contact_id'  => $params['contactID'],
        'activity_type_id'   => $activityTypeLetterID,
        'subject'            => sprintf("Direct Debit Confirmation Letter, Mandate ID : %s", $params['trxn_id']),
        'activity_date_time' => date('YmdHis'),
        'status_id'          => CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_status_id', 'Scheduled'),
      );

      civicrm_api3( 'activity', 'create', $activityLetterParams);
    }

    $activityTypeID = (int) CRM_Smartdebit_Settings::getValue('activity_type');
    $activityParams = array(
      'source_contact_id'  => $params['contactID'],
      'target_contact_id'  => $params['contactID'],
      'activity_type_id'   => $activityTypeID,
      'subject'            => sprintf("Direct Debit Sign Up, Mandate ID : %s", $params['trxn_id']),
      'activity_date_time' => date('YmdHis'),
      'status_id'          => CRM_Core_PseudoConstant::getKey('CRM_Activity_DAO_Activity', 'activity_status_id', 'Completed'),
    );

    $activityResult = civicrm_api3('activity', 'create', $activityParams);
    return $activityResult['id'];
  }

  /**
   * Get domain address details
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getCompanyAddress() {
    $companyAddress = array();

    $domain = CRM_Core_BAO_Domain::getDomain();
    $domainLoc = $domain->getLocationValues();

    $companyAddress['company_name'] = $domain->name;
    if (!empty($domainLoc['address'])) {
      $companyAddress['address1']     = $domainLoc['address'][1]['street_address'];
      if (array_key_exists('supplemental_address_1', $domainLoc['address'][1])) {
        $companyAddress['address2']     = $domainLoc['address'][1]['supplemental_address_1'];
      }
      if (array_key_exists('supplemental_address_2', $domainLoc['address'][1])) {
        $companyAddress['address3']     = $domainLoc['address'][1]['supplemental_address_2'];
      }
      $companyAddress['town']         = $domainLoc['address'][1]['city'];
      $companyAddress['postcode']     = $domainLoc['address'][1]['postal_code'];
      if (array_key_exists('county_id', $domainLoc['address'][1])) {
        $companyAddress['county']       = CRM_Core_PseudoConstant::county($domainLoc['address'][1]['county_id']);
      }
      $companyAddress['country_id']   = CRM_Core_PseudoConstant::country($domainLoc['address'][1]['country_id']);
    }

    return $companyAddress;
  }

  /**
   * Translate Smart Debit Frequency Unit/Factor to CiviCRM frequency unit/interval (eg. W,1 = day,7)
   * @param $sdFrequencyUnit
   * @param $sdFrequencyFactor
   *
   * @return array ($civicrm_frequency_unit, $civicrm_frequency_interval)
   */
  public static function translateSmartdebitFrequencytoCiviCRM($sdFrequencyUnit, $sdFrequencyFactor) {
    if (empty($sdFrequencyFactor)) {
      $sdFrequencyFactor = 1;
    }
    switch ($sdFrequencyUnit) {
      case 'W':
        $unit = 'day';
        $interval = $sdFrequencyFactor * 7;
        break;
      case 'M':
        $unit = 'month';
        $interval = $sdFrequencyFactor;
        break;
      case 'Q':
        $unit = 'month';
        $interval = $sdFrequencyFactor*3;
        break;
      case 'Y':
      default:
        $unit = 'year';
        $interval = $sdFrequencyFactor;
    }
    return array ($unit, $interval);
  }

  /**
   * Get the next scheduled date for the recurring contribution
   *
   * @param string $paymentDateString
   * @param array $recurParams
   *
   * @return string
   */
  public static function getNextScheduledDate($paymentDateString, $recurParams) {
    $paymentDate = new DateTime($paymentDateString);
    $paymentDate->modify('+' . $recurParams['frequency_interval'] . ' ' . $recurParams['frequency_unit']);
    return $paymentDate->format('Ymd');
  }

  /**
   * Create a new recurring contribution for the direct debit instruction we set up.
   *
   * @param $recurParams
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function createRecurContribution($recurParams) {
    // Mandatory Parameters
    // Amount
    if (empty($recurParams['amount'])) {
      throw new InvalidArgumentException('Smartdebit createRecurContribution: Missing parameter: amount', 1);
    }
    else {
      // Make sure it's properly formatted (ie remove symbols etc)
      $recurParams['amount'] = preg_replace("/([^0-9\\.])/i", "", $recurParams['amount']);
    }
    if (empty($recurParams['contact_id'])) {
      throw new InvalidArgumentException('Smartdebit createRecurContribution: Missing parameter: contact_id', 1);
    }

    // Optional parameters
    // Set default payment_processor_id
    $recurParams['payment_processor_id'] = CRM_Core_Payment_Smartdebit::getSmartdebitPaymentProcessorID($recurParams);
    // Set status - Default to "Pending". This will change to "In Progress" on a successful sync once the first payment has been received
    $recurParams['contribution_status_id'] = CRM_Utils_Array::value('contribution_status_id', $recurParams, CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'));
    // Set unit/interval
    if (isset($recurParams['frequency_type'])) {
      if (empty($recurParams['frequency_factor'])) {
        $recurParams['frequency_factor'] = 1;
      }
      // Convert Smartdebit frequency params if we have them
      list($recurParams['frequency_unit'], $recurParams['frequency_interval']) = CRM_Smartdebit_Base::translateSmartdebitFrequencytoCiviCRM($recurParams['frequency_type'], $recurParams['frequency_factor']);
    }
    if (empty($recurParams['frequency_unit']) && empty($recurParams['frequency_interval'])) {
      // Default to 1 year if undefined
      $recurParams['frequency_unit'] = 'year';
      $recurParams['frequency_interval'] = 1;
    }
    // Default to today for creation date
    if (empty($recurParams['create_date'])) {
      $recurParams['create_date'] = date('YmdHis');
    }
    else {
      $recurParams['create_date'] = CRM_Utils_Date::processDate($recurParams['create_date']);
    }
    // Default to today for modified date
    if (empty($recurParams['modified_date'])) {
      $recurParams['modified_date'] = date('YmdHis');
    }
    else {
      $recurParams['modified_date'] = CRM_Utils_Date::processDate($recurParams['modified_date']);
    }
    // Default to today for start date
    if (empty($recurParams['start_date'])) {
      $recurParams['start_date'] = date('YmdHis');
    }
    else {
      $recurParams['start_date'] = CRM_Utils_Date::processDate($recurParams['start_date']);
    }
    // Default to today for next_sched_contribution_date
    if (empty($recurParams['next_sched_contribution_date'])) {
      $recurParams['next_sched_contribution_date'] = date('YmdHis');
    }
    else {
      $recurParams['next_sched_contribution_date'] = CRM_Utils_Date::processDate($recurParams['next_sched_contribution_date']);
    }
    // Cycle day defaults to day of start date
    if (empty($recurParams['cycle_day'])) {
      $recurParams['cycle_day'] = date('j', strtotime($recurParams['start_date'])); //Day of the month without leading zeros
    }
    // Default value for payment_instrument id (payment method, eg. "Direct Debit")
    if (empty($recurParams['payment_instrument_id'])){
      $recurParams['payment_instrument_id'] = (int) CRM_Smartdebit_Settings::getValue('payment_instrument_id');
    }
    // Default value for financial_type_id (eg. "Member dues")
    if (empty($recurParams['financial_type_id'])){
      $recurParams['financial_type_id'] = (int) CRM_Smartdebit_Settings::getValue('financial_type');
    }
    // Default currency
    if (empty($recurParams['currency'])) {
      $config = CRM_Core_Config::singleton();
      $recurParams['currency'] = $config->defaultCurrency;
    }
    // Invoice ID
    if (empty($recurParams['invoice_id'])) {
      $recurParams['invoice_id'] = md5(uniqid(rand(), TRUE ));
    }
    // Auto renew (default to 1, but for single payments it should be 0
    if (!isset($recurParams['auto_renew'])) {
      $recurParams['auto_renew'] = 1;
    }
    // Defaults to 0 if not set, but we set to 1 if not auto-renew, so make sure array is set here.
    $recurParams['installments'] = CRM_Utils_Array::value('installments', $recurParams, '');
    // Test mode?
    $recurParams['is_test'] = CRM_Utils_Array::value('is_test', $recurParams, FALSE);

    // Build recur params
    $params = [
      'contact_id' => $recurParams['contact_id'],
      'create_date' => $recurParams['create_date'],
      'modified_date' => $recurParams['modified_date'],
      'start_date' => $recurParams['start_date'],
      'next_sched_contribution_date' => $recurParams['next_sched_contribution_date'],
      'amount' => $recurParams['amount'],
      'frequency_unit' => $recurParams['frequency_unit'],
      'frequency_interval' => $recurParams['frequency_interval'],
      'payment_processor_id' => $recurParams['payment_processor_id'],
      'contribution_status_id'=> $recurParams['contribution_status_id'],
      'trxn_id' => $recurParams['trxn_id'],
      'financial_type_id' => $recurParams['financial_type_id'],
      'auto_renew' => $recurParams['auto_renew'],
      'cycle_day' => $recurParams['cycle_day'],
      'currency' => $recurParams['currency'],
      'payment_instrument_id' => $recurParams['payment_instrument_id'],
      'invoice_id' => $recurParams['invoice_id'],
      'installments' => $recurParams['installments'],
      'is_test' => $recurParams['is_test'],
    ];

    // We're updating an existing recurring contribution
    // Either id or contribution_recur_id can be set, but contribution_recur_id will take precedence
    if (!empty($recurParams['contribution_recur_id'])) {
      // We're updating an existing recurring contribution
      $params['id'] = $recurParams['contribution_recur_id'];
      $params['contribution_recur_id'] = $recurParams['contribution_recur_id'];
    }
    elseif (!empty($recurParams['id'])) {
      $params['id'] = $recurParams['id'];
      $params['contribution_recur_id'] = $recurParams['id'];
    }

    // Hook to allow modifying recurring contribution params
    CRM_Smartdebit_Hook::updateRecurringContribution($recurParams);
    // Create the recurring contribution
    return civicrm_api3('ContributionRecur', 'create', $params);
  }

  /**
   * Create/update a contribution for the direct debit.
   * @param $params
   *
   * @return array|bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function createContribution($params) {
    // Mandatory Parameters
    // Amount
    if (empty($params['total_amount'])) {
      Civi::log()->debug('Smartdebit createContribution: ERROR must specify amount!');
      return FALSE;
    }
    else {
      // Make sure it's properly formatted (ie remove symbols etc)
      $params['total_amount'] = preg_replace("/([^0-9\\.])/i", "", $params['total_amount']);
    }
    if (empty($params['contact_id'])) {
      Civi::log()->debug('Smartdebit createContribution: ERROR must specify contact_id!');
      return FALSE;
    }

    // Optional parameters
    // Set default payment_processor_id
    $params['payment_processor_id'] = CRM_Core_Payment_Smartdebit::getSmartdebitPaymentProcessorID($params);

    // Set status
    if (empty($params['contribution_status_id'])) {
      // Default to "Completed" as we assume contribution was successful if status not passed in
      $params['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    }
    // Default to today for modified date
    if (empty($params['receive_date'])) {
      $params['receive_date'] = date('YmdHis');
    }
    else {
      $params['receive_date'] = CRM_Utils_Date::processDate($params['receive_date']);
    }
    // Default value for payment_instrument id (payment method, eg. "Direct Debit")
    if (empty($params['payment_instrument_id'])){
      $params['payment_instrument_id'] = (int) CRM_Smartdebit_Settings::getValue('payment_instrument_id');
    }
    // Default value for financial_type_id (eg. "Member dues")
    if (empty($params['financial_type_id'])){
      $params['financial_type_id'] = (int) CRM_Smartdebit_Settings::getValue('financial_type');
    }
    // Default currency
    if (empty($params['currency'])) {
      $config = CRM_Core_Config::singleton();
      $params['currency'] = $config->defaultCurrency;
    }
    // Invoice ID
    if (empty($params['invoice_id'])) {
      $params['invoice_id'] = md5(uniqid(rand(), TRUE ));
    }

    // Build contribution params
    $contributionParams = array(
      'contact_id' =>  $params['contact_id'],
      'receive_date' => $params['receive_date'],
      'total_amount' => $params['total_amount'],
      'payment_processor_id' => $params['payment_processor_id'],
      'contribution_status_id'=> $params['contribution_status_id'],
      'trxn_id' => $params['trxn_id'],
      'financial_type_id' => $params['financial_type_id'],
      'currency' => $params['currency'],
      'payment_instrument_id' => $params['payment_instrument_id'],
      'invoice_id' => $params['invoice_id'],
      'source' => $params['source'],
    );
    if (!empty($params['contribution_id'])) {
      $contributionParams['contribution_id'] = $params['contribution_id'];
      $contributionParams['id'] = $params['contribution_id'];
    }
    elseif (!empty($params['id'])) {
      $contributionParams['id'] = $params['id'];
      $contributionParams['contribution_id'] = $params['id'];
    }

    if (!empty($params['contribution_recur_id'])) {
      $contributionParams['contribution_recur_id'] = $params['contribution_recur_id'];
    }

    // Create/Update the contribution
    try {
      $result = civicrm_api3('Contribution', 'create', $contributionParams);
    }
    catch (CiviCRM_API3_Exception $e) {
      Civi::log()->error('Smartdebit createContribution: ' . $e->getMessage() . ' ' . print_r($contributionParams, TRUE));
      $result['is_error'] = 1;
    }
    return $result;
  }

  /**
   * Add optional limits
   *
   * @param $params
   *
   * @return string
   */
  public static function limitClause($params) {
    $limitClause = '';

    if (!empty($params['limit'])) {
      $limitClause .= ' LIMIT ' . $params['limit'];
    }
    if (!empty($params['offset'])) {
      $limitClause .= ' OFFSET ' . $params['offset'];
    }
    return $limitClause;
  }


}