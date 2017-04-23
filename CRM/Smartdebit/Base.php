<?php
/**
 * Class CRM_Smartdebit_Base
 *
 * Base class for classes that interact with Xero using push and pull methods.
 */
class CRM_Smartdebit_Base
{
  protected static $_apiUrl = 'https://secure.ddprocessing.co.uk';

  /**
   * Return API URL with base prepended
   * @param string $path
   * @param string $request
   * @return string
   */
  public static function getApiUrl($path = '', $request = '') {
    return self::$_apiUrl.$path.'?'.$request;
  }
  /**
   * Generate a Direct Debit Reference (BACS reference)
   * @return string
   */
  public static function getDDIReference() {
    $tempDDIReference = self::rand_str(16);

    CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_direct_debit
        (ddi_reference, created)
        VALUES
        (%1, NOW())
        ", array(1 => array((string)$tempDDIReference , 'String'))
    );

    // Now get the ID for the record we've just created and create a sequenced DDI Reference Number
    $selectSql  = " SELECT id ";
    $selectSql .= " FROM civicrm_direct_debit cdd ";
    $selectSql .= " WHERE cdd.ddi_reference = %1 ";
    $selectParams  = array( 1 => array( $tempDDIReference , 'String' ) );
    $dao = CRM_Core_DAO::executeQuery( $selectSql, $selectParams );
    $dao->fetch();

    $directDebitId = $dao->id;

    // Replace the DDI Reference Number with our new unique sequenced version
    $transactionPrefix = CRM_Smartdebit_Base::getTransactionPrefix();
    $DDIReference      = $transactionPrefix . sprintf( "%08s", $directDebitId );

    $updateSql  = " UPDATE civicrm_direct_debit cdd ";
    $updateSql .= " SET cdd.ddi_reference = %0 ";
    $updateSql .= " WHERE cdd.id = %1 ";

    $updateParams = array( array( (string) $DDIReference , 'String' ),
      array( (int)    $directDebitId, 'Int'    ),
    );

    CRM_Core_DAO::executeQuery( $updateSql, $updateParams );

    return $DDIReference;
  }

  /**
   * Check if direct debit submission is completed
   * @param $DDIReference
   * @return bool
   */
  static function isDDSubmissionComplete( $DDIReference ) {
    $isComplete = false;

    $selectSql     =  " SELECT complete_flag ";
    $selectSql     .= " FROM civicrm_direct_debit cdd ";
    $selectSql     .= " WHERE cdd.ddi_reference = %1 ";
    $selectParams  = array( 1 => array( $DDIReference , 'String' ) );
    $dao           = CRM_Core_DAO::executeQuery( $selectSql, $selectParams );

    if ( $dao->fetch() ) {
      if ( $dao->complete_flag == 1 ) {
        $isComplete = true;
      }
    }
    return $isComplete;
  }

  static function getDDFormDetails($params) {
    $ddDetails = array();
    $ddDetails['account_holder'] = CRM_Utils_Array::value('account_holder', $params);
    $ddDetails['bank_account_number'] = CRM_Utils_Array::value('bank_account_number', $params);
    $ddDetails['bank_identification_number'] = CRM_Utils_Array::value('bank_identification_number', $params);
    $ddDetails['bank_name'] = CRM_Utils_Array::value('bank_name', $params);

    $ddDetails['sun'] = CRM_Smartdebit_Base::getSUN();

    // Format as array of characters for display
    $ddDetails['sunParts'] = str_split($ddDetails['sun']);
    $ddDetails['binParts'] = str_split($ddDetails['bank_identification_number']);

    if (!empty($params['ddi_reference'])) {
      $sql = "
SELECT * FROM civicrm_direct_debit
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

    $ddDetails['company_address'] = CRM_Smartdebit_Base::getCompanyAddress();
    $date = new DateTime();

    $ddDetails['today'] = $date->format('Ymd');

    return $ddDetails;
  }

  /**
   * Called after contribution page has been completed
   * Main purpose is to tidy the contribution
   * And to setup the relevant Direct Debit Mandate Information
   *
   * // FIXME: Do we need to send email?
   *
   * @param $objects
   */
  static function completeDirectDebitSetup( $params )  {
    // Create an activity to indicate Direct Debit Sign up
    $activityID = CRM_Smartdebit_Base::createDDSignUpActivity($params);

    // Set the DD Record to be complete
    $sql = "
UPDATE civicrm_direct_debit
SET    complete_flag = 1
WHERE  ddi_reference = %0";

    CRM_Core_DAO::executeQuery($sql, array(array((string)$params['trxn_id'], 'String'))
    );
  }

  /**
   *   Send a post request with cURL
   *
   * @param $url URL to send request to
   * @param $data POST data to send (in URL encoded Key=value pairs)
   * @param $username
   * @param $password
   * @param $path
   * @return mixed
   */
  public static function requestPost($url, $data, $username, $password, $path){
    // Set a one-minute timeout for this script
    set_time_limit(160);

    $options = array(
      CURLOPT_RETURNTRANSFER => true, // return web page
      CURLOPT_HEADER => false, // don't return headers
      CURLOPT_POST => true,
      CURLOPT_USERPWD => $username . ':' . $password,
      CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
      CURLOPT_HTTPHEADER => array("Accept: application/xml"),
      CURLOPT_USERAGENT => "CiviCRM PHP DD Client", // Let Smartdebit see who we are
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
    );

    $session = curl_init( $url . $path);
    curl_setopt_array( $session, $options );

    // Tell curl that this is the body of the POST
    curl_setopt ($session, CURLOPT_POSTFIELDS, $data );

    // $output contains the output string
    $output = curl_exec($session);
    $header = curl_getinfo($session);

    //Store the raw response for later as it's useful to see for integration and understanding
    $_SESSION["rawresponse"] = $output;

    if(curl_errno($session)) {
      $resultsArray["Status"] = "FAIL";
      $resultsArray['StatusDetail'] = curl_error($session);
    }
    else {
      // Results are XML so turn this into a PHP Array
      $resultsArray = json_decode(json_encode((array) simplexml_load_string($output)),1);

      // Determine if the call failed or not
      switch ($header["http_code"]) {
        case 200:
          $resultsArray["Status"] = "OK";
          break;
        default:
          $resultsArray["Status"] = "INVALID";
      }
    }
    // Return the output
    return $resultsArray;
  }

  /**
   * Calculate the earliest possible collection date based on todays date plus the collection interval setting.
   * @param $collectionDay
   * @return DateTime
   */
  static function firstCollectionDate($collectionDay) {
    // Initialise date objects with today's date
    $today                    = new DateTime();
    $earliestCollectionDate   = new DateTime();
    $collectionDateThisMonth  = new DateTime();
    $collectionDateNextMonth  = new DateTime();
    $collectionDateMonthAfter = new DateTime();
    $collectionInterval = smartdebit_civicrm_getSetting('collection_interval');

    // Calculate earliest possible collection date
    $earliestCollectionDate->add(new DateInterval( 'P'.$collectionInterval.'D' ));

    // Get the current year, month and next month to create the 2 potential collection dates
    $todaysMonth = $today->format('m');
    $nextMonth   = $today->format('m') + 1;
    $monthAfter  = $today->format('m') + 2;
    $todaysYear  = $today->format('Y');

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
   * @param $collectionDay
   * @return string
   */
  static function formatPreferredCollectionDay( $collectionDay ) {
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
    if ( ( $collectionDay%100 ) >= 11 && ( $collectionDay%100 ) <= 13 )
      $abbreviation = $collectionDay . 'th';
    else
      $abbreviation = $collectionDay . $ends[$collectionDay % 10];

    return $abbreviation;
  }

  /**
   * Function will return the possible array of collection days with formatted label
   */
  static function getCollectionDaysOptions() {
    $intervalDate = new DateTime();
    $interval     = smartdebit_civicrm_getSetting('collection_interval');

    $intervalDate->modify( "+$interval day" );
    $intervalDay = $intervalDate->format( 'd' );

    $collectionDays = smartdebit_civicrm_getSetting('collection_days');

    // Split the array
    $tempCollectionDaysArray  = explode( ',', $collectionDays );
    $earlyCollectionDaysArray = array();
    $lateCollectionDaysArray  = array();

    // Build 2 arrays around next collection date
    foreach( $tempCollectionDaysArray as $key => $value ){
      if ( $value >= $intervalDay ) {
        $earlyCollectionDaysArray[] = $value;
      }
      else {
        $lateCollectionDaysArray[]  = $value;
      }
    }
    // Merge arrays for select list
    $allCollectionDays = array_merge( $earlyCollectionDaysArray, $lateCollectionDaysArray );

    // Loop through and format each label
    foreach( $allCollectionDays as $key => $value ){
      $collectionDaysArray[$value] = self::formatPreferredCollectionDay( $value );
    }
    return $collectionDaysArray;
  }

  /**
   * Create a Direct Debit Sign Up Activity for contact
   *
   * @param $params
   * @return mixed
   */
  static function createDDSignUpActivity( &$params ) {
    if ( $params['confirmation_method'] == 'POST' ) {
      $activityTypeLetterID = CRM_Smartdebit_Base::getActivityTypeLetter();
      $activityLetterParams = array(
        'source_contact_id'  => $params['contactID'],
        'target_contact_id'  => $params['contactID'],
        'activity_type_id'   => $activityTypeLetterID,
        'subject'            => sprintf("Direct Debit Confirmation Letter, Mandate ID : %s", $params['trxn_id'] ),
        'activity_date_time' => date( 'YmdHis' ),
        'status_id'          => 1,
        'version'            => 3
      );

      $resultLetter = civicrm_api( 'activity'
        , 'create'
        , $activityLetterParams
      );
    }

    $activityTypeID = CRM_Smartdebit_Base::getActivityType();
    $activityParams = array(
      'source_contact_id'  => $params['contactID'],
      'target_contact_id'  => $params['contactID'],
      'activity_type_id'   => $activityTypeID,
      'subject'            => sprintf("Direct Debit Sign Up, Mandate ID : %s", $params['trxn_id'] ) ,
      'activity_date_time' => date( 'YmdHis' ),
      'status_id'          => 2,
      'version'            => 3
    );

    $result     = civicrm_api( 'activity','create', $activityParams );
    $activityID = $result['id'];

    return $activityID;
  }

  private static function rand_str( $len )
  {
    // The alphabet the random string consists of
    $abc = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // The default length the random key should have
    $defaultLength = 3;

    // Ensure $len is a valid number
    // Should be less than or equal to strlen( $abc ) but at least $defaultLength
    $len = max( min( intval( $len ), strlen( $abc )), $defaultLength );

    // Return snippet of random string as random string
    return substr( str_shuffle( $abc ), 0, $len );
  }

  static function getCompanyName() {
    $domain = CRM_Core_BAO_Domain::getDomain();
    return $domain->name;
  }

  static function getCompanyAddress() {
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

  static function getActivityType() {
    return smartdebit_civicrm_getSetting('activity_type');
  }

  static function getActivityTypeLetter() {
    return smartdebit_civicrm_getSetting('activity_type_letter');
  }

  static function getTransactionPrefix() {
    return smartdebit_civicrm_getSetting('transaction_prefix');
  }

  /**
   * Function will return the SUN number broken down into individual characters passed as an array
   */
  static function getSUN() {
    return smartdebit_civicrm_getSetting('service_user_number');
  }

  /**
   * Function will return the Payment instrument to be used by DD payment processor
   */
  static function getDefaultPaymentInstrumentID() {
    return smartdebit_civicrm_getSetting('payment_instrument_id');
  }

  /**
   * Function will return the default Financial Type to be used by DD payment processor
   */
  static function getDefaultFinancialTypeID() {
    return smartdebit_civicrm_getSetting('financial_type');
  }

  /**
   * Translate Smart Debit Frequency Unit/Factor to CiviCRM frequency unit/interval (eg. W,1 = day,7)
   * @param $sdFrequencyUnit
   * @param $sdFrequencyFactor
   * @return array ($civicrm_frequency_unit, $civicrm_frequency_interval)
   */
  static function translateSmartdebitFrequencytoCiviCRM($sdFrequencyUnit, $sdFrequencyFactor) {
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
   * Create a new recurring contribution for the direct debit instruction we set up.
   * @param $recurParams
   * // FIXME: No longer used, we can remove
   */
  static function createRecurContribution($recurParams) {
    // Mandatory Parameters
    // Amount
    if (empty($recurParams['amount'])) {
      CRM_Core_Error::debug_log_message('Smartdebit createRecurContribution: ERROR must specify amount!');
      return FALSE;
    }
    else {
      // Make sure it's properly formatted (ie remove symbols etc)
      $recurParams['amount'] = preg_replace("/([^0-9\\.])/i", "", $recurParams['amount']);
    }
    if (empty($recurParams['contact_id'])) {
      CRM_Core_Error::debug_log_message('Smartdebit createRecurContribution: ERROR must specify contact_id!');
      return FALSE;
    }

    // Optional parameters
    // Set default processor_id
    if (empty($recurParams['payment_processor_id'])) {
      $recurParams['payment_processor_id'] = CRM_Core_Payment_Smartdebit::getSmartdebitPaymentProcessorID();
    }
    // Set status
    if (empty($recurParams['contribution_status_id'])) {
      // Default to "In Progress" as we assume setup was successful at this point
      $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
    }
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
    // Default to today for next_sched_contribution date
    if (empty($recurParams['next_sched_contribution'])) {
      $recurParams['next_sched_contribution'] = date('YmdHis');
    }
    else {
      $recurParams['next_sched_contribution'] = CRM_Utils_Date::processDate($recurParams['next_sched_contribution']);
    }
    // Cycle day defaults to day of start date
    if (empty($recurParams['cycle_day'])) {
      $recurParams['cycle_day'] = date('j', strtotime($recurParams['start_date'])); //Day of the month without leading zeros
    }
    // processor id (match trxn_id)
    if (empty($recurParams['processor_id'])) {
      if (!empty($recurParams['reference_number'])) {
        $recurParams['processor_id'] = $recurParams['reference_number'];
      }
      elseif (!empty($recurParams['trxn_id'])) {
        $recurParams['processor_id'] = $recurParams['trxn_id'];
      }
      else {
        $recurParams['processor_id'] = '';
      }
    }
    // trxn_id (match processor id)
    if (empty($recurParams['trxn_id'])) {
      if (!empty($recurParams['processor_id'])) {
        $recurParams['trxn_id'] = $recurParams['processor_id'];
      }
      else {
        $recurParams['trxn_id'] = '';
      }
    }
    // Default value for payment_instrument id (payment method, eg. "Direct Debit")
    if (empty($recurParams['payment_instrument_id'])){
      $recurParams['payment_instrument_id'] = CRM_Smartdebit_Base::getDefaultPaymentInstrumentID();
    }
    // Default value for financial_type_id (eg. "Member dues")
    if (empty($recurParams['financial_type_id'])){
      $recurParams['financial_type_id'] = CRM_Smartdebit_Base::getDefaultFinancialTypeID();
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

    // Build recur params
    $params = array(
      'contact_id' =>  $recurParams['contact_id'],
      'create_date' => $recurParams['create_date'],
      'modified_date' => $recurParams['modified_date'],
      'start_date' => $recurParams['start_date'],
      'next_sched_contribution' => $recurParams['next_sched_contribution'],
      'amount' => $recurParams['amount'],
      'frequency_unit' => $recurParams['frequency_unit'],
      'frequency_interval' => $recurParams['frequency_interval'],
      'payment_processor_id' => $recurParams['payment_processor_id'],
      'contribution_status_id'=> $recurParams['contribution_status_id'],
      'trxn_id'	=> $recurParams['trxn_id'],
      'financial_type_id'	=> $recurParams['financial_type_id'],
      'auto_renew' => '1', // Make auto renew
      'cycle_day' => $recurParams['cycle_day'],
      'currency' => $recurParams['currency'],
      'processor_id' => $recurParams['processor_id'],
      'payment_instrument_id' => $recurParams['payment_instrument_id'],
      'invoice_id' => $recurParams['invoice_id'],
    );

    if (!empty($recurParams['id'])) {
      // We're updating an existing recurring contribution
      $params['id'] = $recurParams['id'];
    }

    // Create the recurring contribution
    $result = civicrm_api3('ContributionRecur', 'create', $params);
    return $result;
  }

  /**
   * Create/update a contribution for the direct debit.
   * @param $params
   * @return object
   * // FIXME: No longer used, we can remove
   */
  static function createContribution($params) {
    // Mandatory Parameters
    // Amount
    if (empty($params['total_amount'])) {
      CRM_Core_Error::debug_log_message('Smartdebit createRecurContribution: ERROR must specify amount!');
      return FALSE;
    }
    else {
      // Make sure it's properly formatted (ie remove symbols etc)
      $params['total_amount'] = preg_replace("/([^0-9\\.])/i", "", $params['total_amount']);
    }
    if (empty($params['contact_id'])) {
      CRM_Core_Error::debug_log_message('Smartdebit createRecurContribution: ERROR must specify contact_id!');
      return FALSE;
    }

    // Optional parameters
    // Set default processor_id
    if (empty($params['payment_processor_id'])) {
      $params['payment_processor_id'] = CRM_Core_Payment_Smartdebit::getSmartdebitPaymentProcessorID();
    }
    // Set status
    if (empty($params['contribution_status_id'])) {
      // Default to "In Progress" as we assume setup was successful at this point
      $params['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
    }
    // Default to today for modified date
    if (empty($params['receive_date'])) {
      $params['receive_date'] = date('YmdHis');
    }
    else {
      $params['receive_date'] = CRM_Utils_Date::processDate($params['receive_date']);
    }
    // processor id (match trxn_id)
    if (empty($params['processor_id'])) {
      if (!empty($params['reference_number'])) {
        $params['processor_id'] = $params['reference_number'];
      }
      elseif (!empty($params['trxn_id'])) {
        $params['processor_id'] = $params['trxn_id'];
      }
      else {
        $params['processor_id'] = '';
      }
    }
    // trxn_id (match processor id)
    if (empty($params['trxn_id'])) {
      if (!empty($params['processor_id'])) {
        $params['trxn_id'] = $params['processor_id'];
      }
      else {
        $params['trxn_id'] = '';
      }
    }
    // Default value for payment_instrument id (payment method, eg. "Direct Debit")
    if (empty($params['payment_instrument_id'])){
      $params['payment_instrument_id'] = CRM_Smartdebit_Base::getDefaultPaymentInstrumentID();
    }
    // Default value for financial_type_id (eg. "Member dues")
    if (empty($params['financial_type_id'])){
      $params['financial_type_id'] = CRM_Smartdebit_Base::getDefaultFinancialTypeID();
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

    // Build recur params
    $contributionParams = array(
      'contact_id' =>  $params['contact_id'],
      'receive_date' => $params['receive_date'],
      'total_amount' => $params['total_amount'],
      'payment_processor_id' => $params['payment_processor_id'],
      'contribution_status_id'=> $params['contribution_status_id'],
      'trxn_id'	=> $params['trxn_id'],
      'financial_type_id'	=> $params['financial_type_id'],
      'currency' => $params['currency'],
      'processor_id' => $params['processor_id'],
      'payment_instrument_id' => $params['payment_instrument_id'],
      'invoice_id' => $params['invoice_id'],
    );
    if (!empty($params['contribution_id'])) {
      $contributionParams['contribution_id'] = $params['contribution_id'];
    }
    if (!empty($params['contribution_recur_id'])) {
      $contributionParams['contribution_recur_id'] = $params['contribution_recur_id'];
    }

    // Create/Update the contribution
    $result = civicrm_api3('Contribution', 'create', $contributionParams);
    return $result;
  }
}