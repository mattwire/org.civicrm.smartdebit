<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Core_Payment_Smartdebit
 *
 * Implementation of the Smartdebit payment processor class
 */
use CRM_Smartdebit_ExtensionUtil as E;

class CRM_Core_Payment_Smartdebit extends CRM_Core_Payment {

  use CRM_Core_Payment_SmartdebitTrait;

  /**
   * mode of operation: live or test
   *
   * @var object
   */
  protected $_mode = NULL;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   * @param $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor)
  {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = E::ts('Smart Debit Processor');
  }

  /**
   * We can use the smartdebit processor on the backend
   * @return bool
   */
  public function supportsBackOffice() {
    return TRUE;
  }

  /**
   * We can edit smartdebit recurring contributions
   * @return bool
   */
  public function supportsEditRecurringContribution() {
    return TRUE;
  }

  /**
   * We can configure a start date for a smartdebit mandate
   * @return bool
   */
  public function supportsFutureRecurStartDate() {
    return TRUE;
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   */
  public function checkConfig()
  {
    $error = [];

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = E::ts('The "username" is not set in the Smart Debit Payment Processor settings.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = E::ts('The "password" is not set in the Smart Debit Payment Processor settings.');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * Get Smart Debit User Details
   *
   * @param null $id
   * @param bool $test
   * @param bool $isActive
   *
   * @return array|bool
   */
  public static function getProcessorDetails($params = []) {
    $params['is_test'] = CRM_Utils_Array::value('is_test', $params, FALSE);
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, TRUE);
    $params['id'] = CRM_Utils_Array::value('payment_processor_id', $params, CRM_Utils_Array::value('id', $params, NULL));
    $params['domain_id'] = CRM_Utils_Array::value('domain_id', $params, CRM_Core_Config::domainID());

    if (empty($params['id'])) {
      $paymentProcessorTypeId = CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', 'Smart_Debit');
      $params['payment_processor_type_id'] = $paymentProcessorTypeId;
      $params['options'] = ['sort' => "id DESC", 'limit' => 1];
    }

    try {
      $paymentProcessorDetails = civicrm_api3('PaymentProcessor', 'getsingle', $params);
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(E::ts('Smart Debit API User Details Missing, Please check that the Smart Debit Payment Processor is configured.'), 'Smart Debit', 'error');
      return FALSE;
    }

    $paymentProcessorDetails['user_name'] = self::getUserStatic($paymentProcessorDetails);
    $paymentProcessorDetails['password'] = self::getPasswordStatic($paymentProcessorDetails);
    $paymentProcessorDetails['signature'] = self::getSignatureStatic($paymentProcessorDetails);
    return $paymentProcessorDetails;
  }

  /**
   * @param \CRM_Core_Form $form
   *
   * @return bool|void
   * @throws \HTML_QuickForm_Error
   */
  public function buildForm(&$form)
  {
    if ($form->isSubmitted()) return;

    // Set ddi_reference
    $defaults = [];
    $defaults['ddi_reference'] = CRM_Smartdebit_Base::getDDIReference();
    // Set preferred collection day default to the first choice.
    $collectionDaysArray = CRM_Smartdebit_DateUtils::getCollectionDaysOptions();
    if (count($collectionDaysArray) > 0) {
      $defaults['preferred_collection_day'] = CRM_Utils_Array::first(array_keys($collectionDaysArray));
    }
    $form->setDefaults($defaults);

    // Add help and javascript
    CRM_Core_Region::instance('billing-block')->add(
      ['template' => 'CRM/Core/Payment/Smartdebit/Smartdebit.tpl', 'weight' => -1]);

    return;
  }

  /**
   * Override custom Payment Instrument validation
   *  to validate payment details with SmartDebit
   * Sets appropriate parameters and calls Smart Debit API to validate a payment (does not setup the payment)
   *
   * @param array $params
   * @param array $errors
   *
   * @throws \Exception
   */
  public function validatePaymentInstrument($params, &$errors) {
    CRM_Core_Form::validateMandatoryFields($this->getMandatoryFields(), $params, $errors);

    $smartDebitParams = self::preparePostArray($params);
    CRM_Smartdebit_Hook::alterVariableDDIParams($params, $smartDebitParams, 'validate');
    self::checkSmartDebitParams($smartDebitParams);

    $url = CRM_Smartdebit_Api::buildUrl($this->_paymentProcessor, 'api/ddi/variable/validate');
    $response = CRM_Smartdebit_Api::requestPost($url, $smartDebitParams, $this->getUser(), $this->getPassword());

    $directDebitResponse = [
      'data_type' => 'recurring',
      'entity_type' => 'contribution_recur',
      'first_collection_date' => $smartDebitParams['variable_ddi[start_date]'],
      'preferred_collection_day' => $params['preferred_collection_day'],
      'ddi_reference' => $params['ddi_reference'],
      'response_status' => $response['message'],
    ];

    // On success an array is returned, last success element is an array of attributes
    if ((is_array($response['success'])) && isset(end($response['success'])['@attributes'])) {
      foreach (end($response['success'])['@attributes'] as $key => $value) {
        $directDebitResponse[$key] = $value;
      }
    }

    // Take action based upon the response status
    if ($response['success']) {
      $directDebitResponse['entity_id'] = isset($params['entity_id']) ? $params['entity_id'] : 0;
      self::recordSmartDebitResponse($directDebitResponse);
    }
    else {
      self::formatErrorsForContributionForm($response['error'], $errors, $params);
    }
  }

  /**
   * Parse and format error message for display on contribution form
   *
   * @param $responseErrors
   * @param array $errors
   * @param array $params Parameters passed to original function
   */
  public static function formatErrorsForContributionForm($responseErrors, &$errors, $params) {
    if (!is_array($responseErrors)) {
      $responseErrors = [$responseErrors];
    }
    foreach ($responseErrors as $error) {
      $shortErr = substr($error, 0, 14);
      switch ($shortErr) {
        case 'Sort code is i': // Sort code ..
          $errors['bank_identification_number'] = CRM_Utils_Array::value('bank_identification_number', $errors) . $error . '. ';
          break;
        case 'Account number': // Account number ..
          $errors['bank_account_number'] = CRM_Utils_Array::value('bank_account_number', $errors) . $error . '. ';
          break;
        case 'Account name i': // Account name ..
          $errors['account_holder'] = CRM_Utils_Array::value('account_holder', $errors) . $error . '. ';
          break;
        case 'Start date mus': // Start date ..
          $errors['preferred_collection_day'] = CRM_Utils_Array::value('preferred_collection_day', $errors) . $error . '. ';
          break;
        case 'Frequency type': //' must be in list'
          Civi::log()->debug('Smartdebit validation error: Frequency type must be in list. collection_frequency='
            . CRM_Utils_Array::value('collection_frequency', $params) . '; collection_interval='
            . CRM_Utils_Array::value('collection_interval', $params));
          $errors['unknown'] = CRM_Utils_Array::value('unknown', $errors) . $error . '. ';
          break;
        default:
          $errors['unknown'] = CRM_Utils_Array::value('unknown', $errors) . $error . '. ';
      }
    }
    if (isset($errors['unknown'])) {
      CRM_Core_Session::setStatus($errors['unknown'], 'Payment validation error', 'error');
    }
  }

  /**
   * Override CRM_Core_Payment function
   *
   * @return string
   */
  public function getPaymentTypeName() {
    return 'direct_debit';
  }

  /**
   * Override CRM_Core_Payment function
   *
   * @return string
   */
  public function getPaymentTypeLabel() {
    return 'Direct Debit';
  }

  /**
   * Override CRM_Core_Payment function
   *
   * @return array
   */
  public function getPaymentFormFields() {
    return [
      'payer_confirmation',
      'preferred_collection_day',
      'account_holder',
      'bank_account_number',
      'bank_identification_number',
      'ddi_reference',
    ];
  }

  /**
   * Return an array of all the details about the fields potentially required for payment fields.
   *
   * Only those determined by getPaymentFormFields will actually be assigned to the form
   *
   * @return array
   *   field metadata
   */
  public function getPaymentFormFieldsMetadata() {
    // Get the collection days options
    $collectionDaysArray = CRM_Smartdebit_DateUtils::getCollectionDaysOptions();

    return [
      'account_holder' => [
        'htmlType' => 'text',
        'name' => 'account_holder',
        'title' => E::ts('Account Holder'),
        'cc_field' => TRUE,
        'attributes' => [
          'size' => 20,
          'maxlength' => 18,
          'autocomplete' => 'on'
        ],
        'is_required' => TRUE,
        'description' => E::ts('Should be no more than 18 characters. Should not include punctuation (e.g. O\'Callaghan should be OCallaghan). First initial and surname is valid. (e.g. D Watson).'),
      ],
      // UK BACS Account number is 8 digits
      'bank_account_number' => [
        'htmlType' => 'text',
        'name' => 'bank_account_number',
        'title' => E::ts('Bank Account Number'),
        'cc_field' => TRUE,
        'attributes' => [
          'size' => 20,
          'maxlength' => 8,
          'autocomplete' => 'off'
        ],
        'is_required' => TRUE,
        'description' => E::ts('8 digits (e.g. 12345678).'),
      ],
      // UK BACS sortcode is 6 digits
      'bank_identification_number' => [
        'htmlType' => 'text',
        'name' => 'bank_identification_number',
        'title' => E::ts('Sort Code'),
        'cc_field' => TRUE,
        'attributes' => [
          'size' => 20,
          'maxlength' => 6,
          'autocomplete' => 'off'
        ],
        'is_required' => TRUE,
        'description' => E::ts('6 digits (e.g. 01 23 45).'),
      ],
      'preferred_collection_day' => [
        'htmlType' => (count($collectionDaysArray) > 1) ? 'select' : 'hidden',
        'name' => 'preferred_collection_day',
        'title' => E::ts('Preferred Collection Day'),
        'cc_field' => TRUE,
        'attributes' => $collectionDaysArray, // eg. array('1' => '1st', '8' => '8th', '21' => '21st'),
        'is_required' => TRUE
      ],
      'payer_confirmation' => [
        'htmlType' => 'checkbox',
        'name' => 'payer_confirmation',
        'title' => E::ts('Confirm'),
        'cc_field' => TRUE,
        'attributes' => '',
        'is_required' => TRUE,
        'description' => E::ts('Please confirm that you are the account holder and the only person required to authorise Direct Debits from this account.'),
      ],
      'ddi_reference' => [
        'htmlType' => 'hidden',
        'name' => 'ddi_reference',
        'title' => 'DDI Reference',
        'cc_field' => TRUE,
        'attributes' => [
          'size' => 20,
          'maxlength' => 64,
          'autocomplete' => 'off'
        ],
        'is_required' => TRUE,
      ]
    ];
  }

  /**
   * Get form metadata for billing address fields.
   *
   * @param int $billingLocationID
   *
   * @return array
   *    Array of metadata for address fields.
   */
  public function getBillingAddressFieldsMetadata($billingLocationID = NULL) {
    $metadata = parent::getBillingAddressFieldsMetadata($billingLocationID);
    if (!$billingLocationID) {
      // Note that although the billing id is passed around the forms the idea that it would be anything other than
      // the result of the function below doesn't seem to have eventuated.
      // So taking this as a param is possibly something to be removed in favour of the standard default.
      $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    }

    // State/county field is not required.
    if (!empty($metadata["billing_state_province_id-{$billingLocationID}"]['is_required'])) {
      $metadata["billing_state_province_id-{$billingLocationID}"]['is_required'] = FALSE;
    }

    return $metadata;
  }

  /**
   * Get an array of the fields that can be edited on the recurring contribution.
   *
   * @return array
   */
  public function getEditableRecurringScheduleFields() {
    return [
      'amount',
      'frequency_interval',
      'frequency_unit',
      'start_date',
    ];
  }

  /**
   * @param array $params
   *      collection_start_date: DateTime
   *      collection_frequency: Smartdebit formatted collection frequency
   *      collection_interval: Smartdebit formatted collection interval
   * @param boolean $single Whether this is a recurring or one-off payment
   *
   * @return \DateTime|bool
   * @throws \Exception
   */
  public static function getCollectionEndDate($params, $single) {
    if (!empty($params['installments'])) {
      $installments = $params['installments'];
    }
    else {
      $installments = 0;
    }
    if (!empty($installments)) {
      // Need to set an end date after final installment
      $plus = [
        'years' => 0,
        'months' => 0,
        'weeks' => 0
      ];
      switch ($params['collection_frequency']) {
        case 'Y':
          $plus['years'] = $installments * $params['collection_interval'];
          break;
        case 'Q':
          $plusQuarters = $installments * $params['collection_interval'];
          $plus['months'] = $plusQuarters * 3;
          break;
        case 'M':
          $plus['months'] = $installments * $params['collection_interval'];
          break;
        case 'W':
          $plus['weeks'] = $installments * $params['collection_interval'];
          break;
        default:
          Civi::log()->debug('Smartdebit getCollectionEndDate: An unknown collection frequency (' . $params['collection_frequency'] . ') was passed!');
      }
      $intervalSpec= 'P' . $plus['years'] . 'Y' . $plus['months'] . 'M' . $plus['weeks'] . 'W';
    }
    elseif ($single) {
      // Set end date 6 days after start date (min DD freq with Smart Debit is 1 week/7days)
      $intervalSpec = 'P6D';
    }
    else {
      return FALSE;
    }
    $endDate = $params['collection_start_date']->add(new DateInterval($intervalSpec));
    return $endDate;
  }

  /**
   * Determine the frequency based on the recurring params if set
   * Should check the [frequency_unit] and if set use that
   * Smart debit supports frequency intervals of 1-4 for each Y,Q,M,W.
   *
   * @return array (string Y,Q,M,W,O; int frequencyInterval)
   */
  private static function getCollectionFrequency($params) {
    // Smart Debit supports Y, Q, M, W parameters
    // We return 'O' if the payment is not recurring.  You should then supply an end date to smart debit
    //    to ensure only a single payment is taken.
    // Get frequency unit
    if (!empty($params['frequency_unit'])) {
      $frequencyUnit = $params['frequency_unit'];
    }
    else {
      $frequencyUnit = '';
    }
    // Get frequency interval
    if (!empty($params['frequency_interval'])) {
      $frequencyInterval = $params['frequency_interval'];
    }
    else {
      $frequencyInterval = 1;
    }

    $single = FALSE; // Used as a flag that it's a single payment
    switch (strtolower($frequencyUnit)) {
      case 'year':
        $collectionFrequency = 'Y';
        break;
      case 'month':
        if ($frequencyInterval % 3 != 0) {
          // Monthly
          if ($frequencyInterval > 4) {
            Civi::log()->debug('The maximum monthly collection interval for Smart Debit is 4 months but you specified ' . $frequencyInterval . ' months. 
            Resetting to 4 months. If you meant to select a quarterly interval make sure the collection interval is a multiple of 3.');
            $frequencyInterval = 4;
          }
          $collectionFrequency = 'M';
        } else {
          // Quarterly (frequencyInterval is a multiple of 3)
          if ($frequencyInterval > 12) {
            Civi::log()->debug('The maximum quarterly collection interval for Smart Debit is 4 quarters but you specified ' . $frequencyInterval . ' months. Resetting to 4 quarters');
            $frequencyInterval = 12;
          }
          // Convert frequencyInterval from months to quarters
          $frequencyInterval = ($frequencyInterval / 3);
          $collectionFrequency = 'Q';
        }
        break;
      case 'week':
        // weekly
        if ($frequencyInterval > 4) {
          Civi::log()->debug('The maximum weekly collection interval for Smart Debit is 4 weeks but you specified ' . $frequencyInterval . ' weeks. 
            Resetting to 4 weeks.');
          $frequencyInterval = 4;
        }
        $collectionFrequency = 'W';
        break;
      case 'day':
        // Make sure frequencyInterval is a multiple of 7 days (ie 1 week)
        if ($frequencyInterval % 7 != 0) {
          Civi::log()->debug('The minimum collection interval for Smart Debit is 1 week but you specified ' . $frequencyInterval . ' days. Resetting to 1 week');
          $frequencyInterval = 7;
        }
        if ($frequencyInterval > 28) {
          Civi::log()->debug('The maximum weekly collection interval for Smart Debit is 4 weeks but you specified ' . $frequencyInterval . ' days. Resetting to 4 weeks');
          $frequencyInterval = 28;
        }
        // Convert frequencyInterval from days to weeks
        $frequencyInterval = ($frequencyInterval / 7);
        $collectionFrequency = 'W';
        break;
      default:
        $collectionFrequency = 'Y';
        $single = TRUE; // Used as a flag that it's a single payment
    }
    return [$collectionFrequency, $frequencyInterval, $single];
  }

  /**
   * @param array $params
   *
   * @return array
   * @throws \Exception
   */
  private static function getCollectionFrequencyPostParams($params) {
    $collectionDate = CRM_Smartdebit_DateUtils::getNextAvailableCollectionDate($params['preferred_collection_day'], TRUE);
    $smartDebitParams['variable_ddi[start_date]'] = $collectionDate->format('Y-m-d');
    list($collectionFrequency, $collectionInterval, $single) = self::getCollectionFrequency($params);
    $params['collection_start_date'] = $collectionDate;
    $params['collection_frequency'] = $collectionFrequency;
    $params['collection_interval'] = $collectionInterval;
    $endDate = self::getCollectionEndDate($params, $single);
    if (!empty($endDate)) {
      $smartDebitParams['variable_ddi[end_date]'] = $endDate->format("Y-m-d");
    }
    $smartDebitParams['variable_ddi[frequency_type]'] = $collectionFrequency;
    if (!empty($collectionFrequency)) {
      $smartDebitParams['variable_ddi[frequency_factor]'] = $collectionInterval;
    }
    return $smartDebitParams;
  }

  /**
   * Get the currency for the transaction.
   * ( Added to core in 4.7.31 )
   *
   * @param $params
   *
   * @return string
   */
  public function getAmount($params) {
    return self::formatAmount($params);
  }

  /**
   * Get the Signature (PSLID)
   *
   * @return string
   */
  public function getSignature() {
    return self::getSignatureStatic($this->_paymentProcessor);
  }

  /**
   * Get the Signature (PSLID)
   *
   * @param array $paymentProcessorParams
   *
   * @return string
   */
  public static function getSignatureStatic($paymentProcessorParams) {
    return trim(CRM_Utils_Array::value('signature', $paymentProcessorParams));
  }

  /**
   * Get the Username
   *
   * @return string
   */
  public function getUser() {
    return self::getUserStatic($this->_paymentProcessor);
  }

  /**
   * Get the Username
   *
   * @param array $paymentProcessorParams
   *
   * @return string
   */
  public static function getUserStatic($paymentProcessorParams) {
    return trim(CRM_Utils_Array::value('user_name', $paymentProcessorParams));
  }

  /**
   * Get the Password
   *
   * @return string
   */
  public function getPassword() {
    return self::getPasswordStatic($this->_paymentProcessor);
  }

  /**
   * Get the Password
   *
   * @param array $paymentProcessorParams
   *
   * @return string
   */
  public static function getPasswordStatic($paymentProcessorParams) {
    return trim(CRM_Utils_Array::value('password', $paymentProcessorParams));
  }

  /**
   * Get the currency for the transaction formatted for smartdebit
   * Smartdebit requires that the amount is sent in "pence" eg. £12.37 would be 1237.
   *
   * @param $params
   *
   * @return int|string
   */
  public static function formatAmount($params) {
    if (empty($params['amount'])) {
      return 0;
    }
    else {
      $amount = CRM_Utils_Money::format($params['amount'], NULL, NULL, TRUE);
      return (int) preg_replace('/[^\d]/', '', strval($amount));
    }
  }

  private static function checkSmartDebitParams(&$smartDebitParams) {
    foreach ($smartDebitParams as $key => &$value) {
      switch ($key) {
        case 'variable_ddi[address_1]':
        case 'variable_ddi[town]':
          $value = str_replace(',', ' ', $value);
          break;

        case 'variable_ddi[first_amount]':
        case 'variable_ddi[default_amount]':
          $value = self::formatAmount(['amount' => $value]);
          break;
      }
    }
  }

  /**
   * Prepare Post Array for POSTing to Smart Debit APi
   *
   * @param array $params
   *
   * @return array
   * @throws \Exception
   */
  private function preparePostArray($params) {
    // When passed in from backend forms via AJAX (ie. select from multiple payprocs
    //  $params is not fully set for doDirectPayment, but $_REQUEST has the missing info
    foreach ($_REQUEST as $key => $value) {
      if (!isset($params[$key])) {
        $params[$key] = CRM_Utils_Array::value($key, $_REQUEST);
      }
    }

    $contactId = $this->getContactId($params);
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();

    // Construct params list to send to Smart Debit ...
    $smartDebitParams = [
      'variable_ddi[service_user][pslid]' => $this->getSignature(),
      'variable_ddi[reference_number]' => CRM_Utils_Array::value('ddi_reference', $params),
      'variable_ddi[payer_reference]' => $contactId,
      'variable_ddi[first_name]' => CRM_Utils_Array::value('billing_first_name', $params),
      'variable_ddi[last_name]' => CRM_Utils_Array::value('billing_last_name', $params),
      'variable_ddi[address_1]' => CRM_Utils_Array::value('billing_street_address-' . $billingLocationID, $params),
      'variable_ddi[town]' => CRM_Utils_Array::value('billing_city-' . $billingLocationID, $params),
      'variable_ddi[postcode]' => CRM_Utils_Array::value('billing_postal_code-' . $billingLocationID, $params),
      'variable_ddi[country]' => CRM_Utils_Array::value('billing_country_id-' . $billingLocationID, $params),
      'variable_ddi[account_name]' => CRM_Utils_Array::value('account_holder', $params),
      'variable_ddi[sort_code]' => CRM_Utils_Array::value('bank_identification_number', $params),
      'variable_ddi[account_number]' => CRM_Utils_Array::value('bank_account_number', $params),
      'variable_ddi[first_amount]' => CRM_Utils_Array::value('amount', $params, 0),
      'variable_ddi[default_amount]' => CRM_Utils_Array::value('amount', $params, 0),
      'variable_ddi[email_address]' => $this->getBillingEmail($params, $contactId),
    ];

    $smartDebitFrequencyParams = self::getCollectionFrequencyPostParams($params);
    $smartDebitParams = array_merge($smartDebitParams, $smartDebitFrequencyParams);
    return $smartDebitParams;
  }

  /**
   * Process payment
   *
   * Sets appropriate parameters and calls Smart Debit API to create a payment
   *
   * Payment processors should set payment_status_id.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @param string $component
   *
   * @return array
   *   Result array
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   */
  public function doPayment(&$params, $component = 'contribute') {
    $this->beginDoPayment($params);

    $smartDebitParams = self::preparePostArray($params);
    CRM_Smartdebit_Hook::alterVariableDDIParams($params, $smartDebitParams, 'create');
    self::checkSmartDebitParams($smartDebitParams);

    $url = CRM_Smartdebit_Api::buildUrl($this->_paymentProcessor, 'api/ddi/variable/create');
    $response = CRM_Smartdebit_Api::requestPost($url, $smartDebitParams, $this->getUser(), $this->getPassword());

    // Take action based upon the response status
    if ($response['success']) {
      if (isset($smartDebitParams['variable_ddi[reference_number]'])) {
        $params['trxn_id'] = $smartDebitParams['variable_ddi[reference_number]'];
      }
      // Record the start/end date for the recurring contribution
      if (isset($smartDebitParams['variable_ddi[start_date]'])) {
        $params['start_date'] = $smartDebitParams['variable_ddi[start_date]'];
        $params['next_sched_contribution_date'] = $params['start_date'];
      }
      if (isset($smartDebitParams['variable_ddi[end_date]'])) {
        $params['end_date'] = $smartDebitParams['variable_ddi[end_date]'];
      }
      $params['is_test'] = CRM_Utils_Array::value('is_test', $this->_paymentProcessor, FALSE);
      $params = $this->setRecurTransactionId($params);
      CRM_Smartdebit_Base::completeDirectDebitSetup($params);
    }
    else {
      $message = CRM_Utils_Array::value('message', $response) . ': ' . CRM_Smartdebit_Api::formatResponseError(CRM_Utils_Array::value('error', $response));
      Civi::log()->error('Smartdebit::doPayment error: ' . $message . ' ' . print_r($smartDebitParams, TRUE));
      throw new \Civi\Payment\Exception\PaymentProcessorException($message, CRM_Utils_Array::value('code', $response), $smartDebitParams);
    }

    // This allows us to set the contribution to completed if
    //   "Mark initial contribution as completed" is enabled in smartdebit settings
    $params['contribution_status_id'] = self::getInitialContributionStatus(FALSE);

    $contributionParams['receive_date'] = $params['start_date'];
    $contributionParams['trxn_id'] = CRM_Smartdebit_DateUtils::getContributionTransactionId($params['trxn_id'], $params['start_date']);
    if (empty($params['payment_instrument_id'])) {
      $contributionParams['payment_instrument_id'] = (int) CRM_Smartdebit_Settings::getValue('payment_instrument_id');
    }
    if ($this->getContributionId($params)) {
      $contributionParams['id'] = $this->getContributionId($params);
      civicrm_api3('Contribution', 'create', $contributionParams);
      unset($contributionParams['id']);
    }
    $params = array_merge($params, $contributionParams);

    // We need to set this to ensure that contributions are set to the correct status
    if (!empty($params['contribution_status_id'])) {
      $params['payment_status_id'] = $params['contribution_status_id'];
    }
    return $params;
  }

  /**
   * As the recur transaction is created before payment, we need to update it with our params after payment
   *
   * @param $params
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private function setRecurTransactionId($params) {
    if (!empty($params['trxn_id'])) {
      // Common parameters
      $recurParams = [
        'trxn_id' => $params['trxn_id'],
        'is_test' => CRM_Utils_Array::value('is_test', $params, FALSE),
        'payment_processor_id' => CRM_Utils_Array::value('payment_processor_id', $params, NULL),
        'start_date' => $params['start_date'],
        'next_sched_contribution_date' => $params['next_sched_contribution_date'],
        'contribution_status_id' => self::getInitialContributionStatus(TRUE),
      ];
      if (!empty($params['end_date'])) {
        $recurParams['end_date'] = $params['end_date'];
      }
      if (!empty($params['preferred_collection_day'])) {
        $recurParams['cycle_day'] = $params['preferred_collection_day'];
      }

      if (!empty($params['contributionRecurID'])) {
        // Recurring transaction, so this is a recurring payment
        $recurParams['id'] = $params['contributionRecurID'];

        // Update the related contribution before the recur (so we can access it from the hook)
        if (!empty($params['contributionID'])) {
          // contributionID not set if we're creating a pledge
          $contributionParams['id'] = $params['contributionID'];
          // Receive date will be the date that the direct debit is taken, not today.
          $contributionParams['receive_date'] = $recurParams['start_date'];
          civicrm_api3('Contribution', 'create', $contributionParams);
        }

        // Hook to allow modifying recurring contribution params
        CRM_Smartdebit_Hook::updateRecurringContribution($recurParams);
        // Update the recurring payment
        civicrm_api3('ContributionRecur', 'create', $recurParams);
        // Update the contribution status
      }
      else {
        // No recurring transaction, assume this is a non-recurring payment (so create a recurring contribution with a single installment
        // Fill recurring transaction parameters
        if (empty($params['receive_date'])) {
          $params['receive_date'] = date('YmdHis');
        }
        $recurParams['contact_id'] = $this->getContactId($params);
        $recurParams['create_date'] = $params['receive_date'];
        $recurParams['modified_date'] = $params['receive_date'];
        $recurParams['start_date'] = $params['start_date'];
        $recurParams['next_sched_contribution_date'] = $params['next_sched_contribution_date'];
        $recurParams['amount'] = $params['amount'];
        $recurParams['frequency_unit'] = 'year';
        $recurParams['frequency_interval'] = '1';
        $recurParams['installments'] = '1';
        $recurParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params, CRM_Utils_Array::value('financialTypeID', $params));
        $recurParams['auto_renew'] = '0'; // Make auto renew
        $recurParams['currency'] = $params['currencyID'];
        $recurParams['invoice_id'] = $params['invoiceID'];

        $recur = CRM_Smartdebit_Base::createRecurContribution($recurParams);
        // Record recurring contribution ID in params for return
        $params['contributionRecurID'] = $recur['id'];
        $params['contribution_recur_id'] = $recur['id'];
        // We need to link the recurring contribution and contribution record, as Civi won't do it for us (4.7.21)
        $contributionParams = [
          'contribution_recur_id' => $params['contribution_recur_id'],
          'contact_id' => $this->getContactId($params),
          'is_test' => $params['is_test'],
        ];
        if (empty($params['contributionID'])) {
          Civi::log()->debug('Smartdebit: No contribution ID specified.  Is this a non-recur transaction?');
        }
        else {
          // Update the related contribution before the recur (so we can access it from the hook)
          $contributionParams['id'] = $params['contributionID'];
          civicrm_api3('Contribution', 'create', $contributionParams);
        }
        if ($contributionParams['contribution_status_id'] ==
          CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
          $params['is_recur'] = 1; // Required for CRM_Core_Payment to set contribution status = Pending
        }
      }
    }
    return $params;
  }

  /**
   * Get the initial (recur) contribution status based on the desired configuration.
   * If initial_completed=TRUE we need to set initial contribution to completed.
   *
   * @param bool $isRecur TRUE if we should return status of recurring contribution instead.
   *
   * @return bool|int|null|string
   */
  public static function getInitialContributionStatus($isRecur = FALSE) {
    $initialCompleted = (boolean) CRM_Smartdebit_Settings::getValue('initial_completed');

    if ($isRecur) {
      return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
    }

    if ($initialCompleted) {
      return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    }
    return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
  }

  /**
   * Record the response from SmartDebit after validatePayment()
   *
   * @param array $direct_debit_response
   */
  private static function recordSmartDebitResponse($direct_debit_response) {
    $sql = "
UPDATE " . CRM_Smartdebit_Base::TABLENAME . " SET
                 created                  = NOW()
          ,      request_counter          = request_counter + 1
    ";
    isset($direct_debit_response['data_type']) ? $sql .= ", data_type                = \"{$direct_debit_response['data_type']}\"" : NULL;
    isset($direct_debit_response['entity_type']) ? $sql .= ", entity_type              = \"{$direct_debit_response['entity_type']}\"" : NULL;
    isset($direct_debit_response['entity_id']) ? $sql .= "  , entity_id                = {$direct_debit_response['entity_id']}" : NULL;
    isset($direct_debit_response['bank_name']) ? $sql .= "  , bank_name                = \"{$direct_debit_response['bank_name']}\"" : NULL;
    isset($direct_debit_response['branch']) ? $sql .= "     , branch                   = \"{$direct_debit_response['branch']}\"" : NULL;
    isset($direct_debit_response['address1']) ? $sql .= "   , address1                 = \"{$direct_debit_response['address1']}\"" : NULL;
    isset($direct_debit_response['address2']) ? $sql .= "   , address2                 = \"{$direct_debit_response['address2']}\"" : NULL;
    isset($direct_debit_response['address3']) ? $sql .= "   , address3                 = \"{$direct_debit_response['address3']}\"" : NULL;
    isset($direct_debit_response['address4']) ? $sql .= "   , address4                 = \"{$direct_debit_response['address4']}\"" : NULL;
    isset($direct_debit_response['town']) ? $sql .= "       , town                     = \"{$direct_debit_response['town']}\"" : NULL;
    isset($direct_debit_response['county']) ? $sql .= "     , county                   = \"{$direct_debit_response['county']}\"" : NULL;
    isset($direct_debit_response['postcode']) ? $sql .= "   , postcode                 = \"{$direct_debit_response['postcode']}\"" : NULL;
    isset($direct_debit_response['first_collection_date']) ? $sql .= "   , first_collection_date    = \"{$direct_debit_response['first_collection_date']}\"" : NULL;
    isset($direct_debit_response['preferred_collection_day']) ? $sql .= ", preferred_collection_day = \"{$direct_debit_response['preferred_collection_day']}\"" : NULL;
    isset($direct_debit_response['response_status']) ? $sql .= "         , response_status          = \"{$direct_debit_response['response_status']}\"" : NULL;
    isset($direct_debit_response['response_raw']) ? $sql .= "            , response_raw             = \"{$direct_debit_response['response_raw']}\"" : NULL;
    $sql .= " WHERE  ddi_reference           = \"{$direct_debit_response['ddi_reference']}\"";

    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Change the subscription amount using the Smart Debit API
   *
   * @param string $message
   * @param array $params
   *
   * @return bool
   * @throws \Exception
   */
  public function changeSubscriptionAmount(&$message = '', $params = []) {
    // Smartdebit start_date can change during the subscription (eg. if we update amount)
    //  so we need to be careful when setting it here.
    // Logic: If a start_date is passed in via UpdateSubscription form we use it, otherwise
    //   we don't touch
    $startDate = NULL;
    if (!empty($params['start_date']) && (array_key_exists('_qf_UpdateSubscription_next', $params))) {
      $startDate = $params['start_date'];
    }
    unset($params['start_date']);
    return self::changeSubscription($this->_paymentProcessor, $params, $startDate);
  }

  /**
   * This function allows to update the subscription with Smartdebit
   * @param array $paymentProcessor
   * @param array $recurContributionParams
   * @param date $startDate We pass this in separately to recurContributionParams as we don't use recur start_date
   *                        because subscription can change during life
   *
   * @return bool
   * @throws \Exception
   */
  public static function changeSubscription($paymentProcessor, $recurContributionParams, $startDate = NULL) {
    try {
      $recurRecord = civicrm_api3('ContributionRecur', 'getsingle', [
        'id' => $recurContributionParams['id'],
        'options' => ['limit' => 1],
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Error::statusBounce('No recurring record! ' . $e->getMessage());
      return FALSE;
    }

    if (empty($recurRecord['trxn_id'])) {
      CRM_Core_Session::setStatus('Update Subscription - No trxn_id!', 'Smart Debit', 'error');
      return FALSE;
    }

    $recurContributionParams['amount'] = isset($recurContributionParams['amount']) ? $recurContributionParams['amount'] : 0;

    $smartDebitParams = [
      'variable_ddi[service_user][pslid]' => self::getSignatureStatic($paymentProcessor),
      'variable_ddi[first_amount]' => $recurContributionParams['amount'],
      'variable_ddi[default_amount]' => $recurContributionParams['amount'],
    ];

    // End Date
    $recurContributionParams['end_date'] = CRM_Utils_Array::value('end_date', $recurContributionParams, CRM_Utils_Array::value('end_date', $recurRecord, NULL));
    if (!empty($recurContributionParams['end_date'])) {
      $smartDebitParams['variable_ddi[end_date]'] = date("Y-m-d", strtotime($recurContributionParams['end_date']));
    }

    if (!isset($recurContributionParams['frequency_unit'])) {
      $recurContributionParams['frequency_unit'] = $recurRecord['frequency_unit'];
    }
    if (!isset($recurContributionParams['frequency_interval'])) {
      $recurContributionParams['frequency_interval'] = $recurRecord['frequency_interval'];
    }
    if (isset($recurContributionParams['frequency_unit']) || isset($recurContributionParams['frequency_interval'])) {
      $smartDebitFrequencyParams = self::getCollectionFrequencyPostParams($recurContributionParams);
      $smartDebitParams = array_merge($smartDebitParams, $smartDebitFrequencyParams);
    }

    if (!empty($startDate)) {
      // We want to update the start_date
      $smartDebitParams['variable_ddi[start_date]'] = $startDate;
      $recurContributionParams['start_date'] = $startDate;
    }

    $paramsThatChange = ['frequency_unit', 'frequency_interval', 'amount', 'start_date', 'end_date'];
    foreach ($paramsThatChange as $param) {
      if ($recurRecord[$param] !== $recurContributionParams[$param]) {
        $changed = TRUE;
      }
    }

    if (!$changed) {
      return TRUE;
    }

    CRM_Smartdebit_Hook::alterVariableDDIParams($recurContributionParams, $smartDebitParams, 'edit');
    self::checkSmartDebitParams($smartDebitParams);
    $response = CRM_Smartdebit_Api::requestUpdate($paymentProcessor, $recurRecord['trxn_id'], $smartDebitParams);

    if (!$response['success']) {
      $msg = CRM_Smartdebit_Api::formatResponseError($response['error']);
      $msg .= '<br />Update Subscription Failed.';
      CRM_Core_Session::setStatus($msg, 'Smart Debit', 'error');
      Civi::log()->warning('Smartdebit changeSubscription: ' . $msg);
      return FALSE;
    }

    // Update the cached mandate
    CRM_Smartdebit_Mandates::getbyReference($recurRecord);

    if (!empty($startDate)) {
      // Update the date of the linked Contribution to match the new start date
      CRM_Smartdebit_Base::updateContributionDateForLinkedRecur($recurRecord['id'], $recurRecord['start_date'], $startDate);
    }
    return TRUE;
  }

  /**
   * Cancel the Direct Debit Subscription using the Smart Debit API
   *
   * @param array $params
   *
   * @return bool
   * @throws \Exception
   */
  public function cancelSubscription($params = []) {
    $contributionRecurId = CRM_Utils_Array::value('crid', $_GET);
    try {
      $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', [
        'id' => $contributionRecurId,
      ]);
    }
    catch (Exception $e) {
      return FALSE;
    }
    if (empty($contributionRecur['trxn_id'])) {
      CRM_Core_Session::setStatus(E::ts('The recurring contribution cannot be cancelled (No reference (trxn_id) found).'), 'Smart Debit', 'error');
      return FALSE;
    }
    $reference = $contributionRecur['trxn_id'];
    $smartDebitParams = [
      'variable_ddi[service_user][pslid]' => $this->getSignature(),
      'variable_ddi[reference_number]' => $reference,
    ];

    $recurParams = CRM_Utils_Array::crmArrayMerge($params, $contributionRecur);
    CRM_Smartdebit_Hook::alterVariableDDIParams($recurParams, $smartDebitParams, 'cancel');
    self::checkSmartDebitParams($smartDebitParams);

    $url = CRM_Smartdebit_Api::buildUrl($this->_paymentProcessor, 'api/ddi/variable/' . $reference . '/cancel');
    $response = CRM_Smartdebit_Api::requestPost($url, $smartDebitParams, $this->getUser(), $this->getPassword());
    if (!$response['success']) {
      $msg = CRM_Smartdebit_Api::formatResponseError($response['error']);
      $msg .= '<br />Cancel Subscription Failed.';
      CRM_Core_Session::setStatus($msg, 'Smart Debit', 'error');
      return FALSE;
    }

    // Refresh the cached mandate from Smartdebit
    $params = [
      'trxn_id' => $reference,
      'refresh' => TRUE,
    ];
    CRM_Smartdebit_Mandates::retrieve($params);

    return TRUE;
  }

  /**
   * Called when ...
   *
   * @param string $message
   * @param array $params
   *
   * @return bool
   * @throws \Exception
   */
  public function updateSubscriptionBillingInfo(&$message = '', $params = []) {
    $reference = $params['subscriptionId'];

    $smartDebitParams = [
      'variable_ddi[service_user][pslid]' => $this->getSignature(),
      'variable_ddi[reference_number]' => $reference,
      'variable_ddi[first_name]' => $params['first_name'],
      'variable_ddi[last_name]' => $params['last_name'],
      'variable_ddi[address_1]' => $params['street_address'],
      'variable_ddi[town]' => $params['city'],
      'variable_ddi[postcode]' => $params['postal_code'],
      'variable_ddi[county]' => $params['state_province'],
      'variable_ddi[country]' => $params['country'],
    ];

    CRM_Smartdebit_Hook::alterVariableDDIParams($params, $smartDebitParams, 'updatebilling');
    self::checkSmartDebitParams($smartDebitParams);

    $response = CRM_Smartdebit_Api::requestUpdate($this->_paymentProcessor, $reference, $smartDebitParams);
    if (!$response['success']) {
      $msg = CRM_Smartdebit_Api::formatResponseError($response['error']);
      CRM_Core_Session::setStatus($msg, 'Smart Debit', 'error');
      return FALSE;
    }

    // Update the cached mandate
    $params = [
      'trxn_id' => $smartDebitParams['reference_number'],
      'refresh' => TRUE,
    ];
    CRM_Smartdebit_Mandates::getbyReference($params);

    return TRUE;
  }

  /**
   * Get ID of first payment processor with class name "Payment_Smartdebit"
   *
   * @return int|bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function getSmartDebitPaymentProcessorID($recurParams) {
    if (!empty($recurParams['payment_processor_id'])) {
      return $recurParams['payment_processor_id'];
    }

    $processorParams = [
      'is_test' => CRM_Utils_Array::value('is_test', $recurParams, FALSE),
      'sequential' => 1,
      'class_name' => 'Payment_Smartdebit',
      'return' => ['id'],
    ];
    $paymentProcessor = civicrm_api3('PaymentProcessor', 'get', $processorParams);
    if ($paymentProcessor['count'] > 0) {
      // Return the first one, it's possible there is more than one payment processor of the same type configured
      //  so we'll just return the first one here.
      if (isset($paymentProcessor['values'][0]['id'])) {
        return $paymentProcessor['values'][0]['id'];
      }
    }
    // If we don't have a valid processor id return false;
    return FALSE;
  }

  /**
   * Get the name of the payment processor
   * @param $ppId
   *
   * @return string
   */
  public static function getSmartDebitPaymentProcessorName($ppId) {
    $paymentProcessorName = 'Unknown';
    try {
      $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
        'return' => ["name"],
        'id' => $ppId,
      ]);
      if (isset($paymentProcessor['name'])) {
        $paymentProcessorName = $paymentProcessor['name'];
      }
    }
    catch (Exception $e) {
      // Payment processor not found, use the default already set above.
    }
    return $paymentProcessorName;
  }

}
