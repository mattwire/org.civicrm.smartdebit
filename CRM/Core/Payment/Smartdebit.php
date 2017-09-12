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
 * Class CRM_Core_Payment_Smartdebit
 *
 * Implementation of the Smartdebit payment processor class
 */
class CRM_Core_Payment_Smartdebit extends CRM_Core_Payment
{
  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

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
  function __construct($mode, &$paymentProcessor)
  {
    $this->_mode = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('Smart Debit Processor');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   * @param $paymentProcessor
   * @return object
   * @static
   *
   */
  static function &singleton($mode, &$paymentProcessor, &$paymentForm = NULL, $force = FALSE)
  {
    $processorName = $paymentProcessor['name'];
    if (self::$_singleton[$processorName] === NULL) {
      self::$_singleton[$processorName] = new self($mode, $paymentProcessor);
    }
    return self::$_singleton[$processorName];
  }

  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig()
  {
    $error = array();

    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('The "username" is not set in the Smart Debit Payment Processor settings.');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('The "password" is not set in the Smart Debit Payment Processor settings.');
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
  public static function getProcessorDetails($id = NULL, $test = FALSE, $isActive = TRUE) {
    $params = array(
      'is_test' => $test,
      'is_active' => $isActive,
      'domain_id' => CRM_Core_Config::domainID(),
    );
    if (!empty($id)) {
      $params['id'] = $id;
    }
    else {
      $paymentProcessorTypeId = CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', 'Smart_Debit');
      $params['payment_processor_type_id'] = $paymentProcessorTypeId;
      $params['options'] = array('sort' => "id DESC", 'limit' => 1);
    }

    try {
      $paymentProcessorDetails = civicrm_api3('PaymentProcessor', 'getsingle', $params);
    }
    catch (Exception $e) {
      CRM_Core_Session::setStatus(ts('Smart Debit API User Details Missing, Please check the Smart Debit Payment Processor is configured Properly'), 'Smart Debit', 'error');
      return FALSE;
    }

    return $paymentProcessorDetails;
  }

  /**
   * @param CRM_Core_Form $form
   * @return bool|void
   */
  function buildForm(&$form)
  {
    if ($form->isSubmitted()) return;

    // Set ddi_reference
    $defaults = array();
    $defaults['ddi_reference'] = CRM_Smartdebit_Base::getDDIReference();
    $form->setDefaults($defaults);

    // Add help and javascript
    CRM_Core_Region::instance('billing-block')->add(
      array('template' => 'CRM/Core/Payment/Smartdebit/Smartdebit.tpl', 'weight' => -1));

    return;
  }

  /**
   * Override custom PI validation
   *  to validate payment details with SmartDebit
   * Sets appropriate parameters and calls Smart Debit API to validate a payment (does not setup the payment)
   *
   * @param array $values
   * @param array $errors
   */
  public function validatePaymentInstrument($values, &$errors) {
    parent::validatePaymentInstrument($values, $errors);

    $smartDebitParams = self::preparePostArray($values);

    // Construct post string
    $post = '';
    foreach ($smartDebitParams as $key => $value) {
      $post .= ($key != 'variable_ddi[service_user][pslid]' ? '&' : '') . $key . '=' . urlencode($value);
    }

    // Get the API Username and Password
    $username = $this->_paymentProcessor['user_name'];
    $password = $this->_paymentProcessor['password'];

    // Send payment POST to the target URL
    $url = $this->_paymentProcessor['url_api'];

    $request_path = 'api/ddi/variable/validate';

    $response = CRM_Smartdebit_Api::requestPost($url, $post, $username, $password, $request_path);

    $direct_debit_response = array();
    $direct_debit_response['data_type'] = 'recurring';
    $direct_debit_response['entity_type'] = 'contribution_recur';
    $direct_debit_response['first_collection_date'] = $smartDebitParams['variable_ddi[start_date]'];
    $direct_debit_response['preferred_collection_day'] = $values['preferred_collection_day'];
    $direct_debit_response['confirmation_method'] = $values['confirmation_method'];
    $direct_debit_response['ddi_reference'] = $values['ddi_reference'];
    $direct_debit_response['response_status'] = $response['message'];

    // Take action based upon the response status
    if ($response['success']) {
      $direct_debit_response['entity_id'] = isset($values['entity_id']) ? $values['entity_id'] : 0;
      self::recordSmartDebitResponse($direct_debit_response);
    }
    else {
      self::formatErrorsForContributionForm($response['error'], $errors);
    }
  }

  public static function formatErrorsForContributionForm($responseErrors, &$errors) {
    if (!is_array($responseErrors)) {
      $responseErrors = array($responseErrors);
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
   */
  public function getPaymentTypeName() {
    return 'direct_debit';
  }

  /**
   * Override CRM_Core_Payment function
   */
  public function getPaymentTypeLabel() {
    return 'Direct Debit';
  }

  /**
   * Override CRM_Core_Payment function
   */
  public function getPaymentFormFields() {
    return array(
      'payer_confirmation',
      'preferred_collection_day',
      'confirmation_method',
      'account_holder',
      'bank_account_number',
      'bank_identification_number',
      'bank_name',
      'ddi_reference',
    );
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
    $collectionDaysArray = CRM_Smartdebit_Base::getCollectionDaysOptions();

    return array(
      'account_holder' => array(
        'htmlType' => 'text',
        'name' => 'account_holder',
        'title' => ts('Account Holder'),
        'cc_field' => TRUE,
        'attributes' => array('size' => 20
        , 'maxlength' => 18
        , 'autocomplete' => 'on'
        ),
        'is_required' => TRUE
      ),
      //e.g. IBAN can have maxlength of 34 digits
      'bank_account_number' => array(
        'htmlType' => 'text',
        'name' => 'bank_account_number',
        'title' => ts('Bank Account Number'),
        'cc_field' => TRUE,
        'attributes' => array('size' => 20
        , 'maxlength' => 34
        , 'autocomplete' => 'off'
        ),
        'is_required' => TRUE
      ),
      //e.g. SWIFT-BIC can have maxlength of 11 digits
      'bank_identification_number' => array(
        'htmlType' => 'text',
        'name' => 'bank_identification_number',
        'title' => ts('Sort Code'),
        'cc_field' => TRUE,
        'attributes' => array('size' => 20
        , 'maxlength' => 11
        , 'autocomplete' => 'off'
        ),
        'is_required' => TRUE
      ),
      'bank_name' => array(
        'htmlType' => 'text',
        'name' => 'bank_name',
        'title' => ts('Bank Name'),
        'cc_field' => TRUE,
        'attributes' => array('size' => 20
        , 'maxlength' => 64
        , 'autocomplete' => 'off'
        ),
        'is_required' => TRUE
      ),
      'preferred_collection_day' => array(
        'htmlType' => 'select',
        'name' => 'preferred_collection_day',
        'title' => ts('Preferred Collection Day'),
        'cc_field' => TRUE,
        'attributes' => $collectionDaysArray, // eg. array('1' => '1st', '8' => '8th', '21' => '21st'),
        'is_required' => TRUE
      ),
      'confirmation_method' => array(
        'htmlType' => 'select',
        'name' => 'confirmation_method',
        'title' => ts('Confirm By'),
        'cc_field' => TRUE,
        'attributes' => array('EMAIL' => 'Email'
        , 'POST' => 'Post'
        ),
        'is_required' => TRUE
      ),
      'payer_confirmation' => array(
        'htmlType' => 'checkbox',
        'name' => 'payer_confirmation',
        'title' => ts('Please confirm that you are the account holder and only person required to authorise Direct Debits from this account'),
        'cc_field' => TRUE,
        'attributes' => '',
        'is_required' => TRUE
      ),
      'ddi_reference' => array(
        'htmlType' => 'hidden',
        'name' => 'ddi_reference',
        'title' => 'DDI Reference',
        'cc_field' => TRUE,
        'attributes' => array('size' => 20
        , 'maxlength' => 64
        , 'autocomplete' => 'off'
        ),
        'is_required' => TRUE,
      )
    );
  }

  /**
   * Get contact email for POSTing to Smart Debit API
   * @param $params
   * @return mixed
   */
  static function getUserEmail(&$params)
  {
    // Set email
    if (!empty($params['email-Primary'])) {
      $useremail = $params['email-Primary'];
    } else {
      $useremail = $params['email-5'];
    }
    return $useremail;
  }

  /**
   * From the selected collection day determine when the actual collection start date could be
   * For direct debit we need to allow 10 working days prior to collection for cooling off
   * We also may need to send them a letter etc
   *
   */
  static function getCollectionStartDate(&$params)
  {
    $preferredCollectionDay = $params['preferred_collection_day'];
    return CRM_Smartdebit_Base::firstCollectionDate($preferredCollectionDay);
  }

  /**
   * Determine the frequency based on the recurring params if set
   * Should check the [frequency_unit] and if set use that
   * Smart debit supports frequency intervals of 1-4 for each Y,Q,M,W.
   *
   * @return array (string Y,Q,M,W,O; int frequencyInterval)
   */
  static function getCollectionFrequency(&$params)
  {
    // Smart Debit supports Y, Q, M, W parameters
    // We return 'O' if the payment is not recurring.  You should then supply an end date to smart debit
    //    to ensure only a single payment is taken.
    $frequencyUnit = (isset($params['frequency_unit'])) ? $params['frequency_unit'] : '';
    $frequencyInterval = (isset($params['frequency_interval'])) ? $params['frequency_interval'] : 1;

    switch (strtolower($frequencyUnit)) {
      case 'year':
        $collectionFrequency = 'Y';
        break;
      case 'month':
        if ($frequencyInterval % 3 != 0) {
          // Monthly
          if ($frequencyInterval > 4) {
            CRM_Core_Error::debug_log_message('The maximum monthly collection interval for Smart Debit is 4 months but you specified ' . $frequencyInterval . ' months. 
            Resetting to 4 months. If you meant to select a quarterly interval make sure the collection interval is a multiple of 3.');
            $frequencyInterval = 4;
          }
          $collectionFrequency = 'M';
        } else {
          // Quarterly (frequencyInterval is a multiple of 3)
          if ($frequencyInterval > 12) {
            CRM_Core_Error::debug_log_message('The maximum quarterly collection interval for Smart Debit is 4 quarters but you specified ' . $frequencyInterval . ' months. Resetting to 4 quarters');
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
          CRM_Core_Error::debug_log_message('The maximum weekly collection interval for Smart Debit is 4 weeks but you specified ' . $frequencyInterval . ' weeks. 
            Resetting to 4 weeks.');
          $frequencyInterval = 4;
        }
        $collectionFrequency = 'W';
        break;
      case 'day':
        // Make sure frequencyInterval is a multiple of 7 days (ie 1 week)
        if ($frequencyInterval % 7 != 0) {
          CRM_Core_Error::debug_log_message('The minimum collection interval for Smart Debit is 1 week but you specified ' . $frequencyInterval . ' days. Resetting to 1 week');
          $frequencyInterval = 7;
        }
        if ($frequencyInterval > 28) {
          CRM_Core_Error::debug_log_message('The maximum weekly collection interval for Smart Debit is 4 weeks but you specified ' . $frequencyInterval . ' days. Resetting to 4 weeks');
          $frequencyInterval = 28;
        }
        // Convert frequencyInterval from days to weeks
        $frequencyInterval = ($frequencyInterval / 7);
        $collectionFrequency = 'W';
        break;
      default:
        $collectionFrequency = 'O';
        $frequencyInterval = 1; // Not really needed here
    }
    return array($collectionFrequency, $frequencyInterval);
  }

  /**
   * Replace comma with space
   * @param $pString
   * @return mixed
   */
  static function replaceCommaWithSpace($pString)
  {
    return str_replace(',', ' ', $pString);
  }

  /**
   * Prepare Post Array for POSTing to Smart Debit APi
   * @param $fields
   * @param null $self
   * @return array
   */
  private function preparePostArray($fields)
  {
    $collectionDate = self::getCollectionStartDate($fields);
    $amount = 0;
    $serviceUserId = NULL;
    if (isset($fields['amount'])) {
      // Set amount in pence if not already set that way.
      $amount = $fields['amount'];
      // $amount might be a string (?) e.g. £12.00, so try just in case
      try {
        $amount = $amount * 100;
      } catch (Exception $e) {
        //Leave amount as it was
        $amount = $fields['amount'];
      }
    }

    if (isset($this->_paymentProcessor['signature'])) {
      $serviceUserId = $this->_paymentProcessor['signature'];
    }

    if (isset($fields['contactID'])) {
      $payerReference = $fields['contactID'];
    } elseif (isset($fields['cms_contactID'])) {
      $payerReference = $fields['cms_contactID'];
    } else {
      $payerReference = 'CIVICRMEXT';
    }

    // Construct params list to send to Smart Debit ...
    $smartDebitParams = array(
      'variable_ddi[service_user][pslid]' => $serviceUserId,
      'variable_ddi[reference_number]' => $fields['ddi_reference'],
      'variable_ddi[payer_reference]' => $payerReference,
      'variable_ddi[first_name]' => $fields['billing_first_name'],
      'variable_ddi[last_name]' => $fields['billing_last_name'],
      'variable_ddi[address_1]' => self::replaceCommaWithSpace($fields['billing_street_address-5']),
      'variable_ddi[town]' => self::replaceCommaWithSpace($fields['billing_city-5']),
      'variable_ddi[postcode]' => $fields['billing_postal_code-5'],
      'variable_ddi[country]' => $fields['billing_country_id-5'],
      'variable_ddi[account_name]' => $fields['account_holder'],
      'variable_ddi[sort_code]' => $fields['bank_identification_number'],
      'variable_ddi[account_number]' => $fields['bank_account_number'],
      'variable_ddi[regular_amount]' => $amount,
      'variable_ddi[first_amount]' => $amount,
      'variable_ddi[default_amount]' => $amount,
      'variable_ddi[start_date]' => $collectionDate->format("Y-m-d"),
      'variable_ddi[email_address]' => self::getUserEmail($fields),
    );

    list($collectionFrequency, $collectionInterval) = self::getCollectionFrequency($fields);
    if ($collectionFrequency == 'O') {
      $collectionFrequency = 'Y';
      // Set end date 6 days after start date (min DD freq with Smart Debit is 1 week/7days)
      $endDate = $collectionDate->add(new DateInterval('P6D'));
      $smartDebitParams['variable_ddi[end_date]'] = $endDate->format("Y-m-d");
    }
    $smartDebitParams['variable_ddi[frequency_type]'] = $collectionFrequency;
    $smartDebitParams['variable_ddi[frequency_factor]'] = $collectionInterval;

    return $smartDebitParams;
  }

  /**
   * Sets appropriate parameters and calls Smart Debit API to create a payment
   *
   * @param array $params name value pair of contribution data
   *
   * @return array $result
   * @access public
   *
   */
  function doDirectPayment(&$params) {
    $smartDebitParams = self::preparePostArray($params);
    $serviceUserId = $this->_paymentProcessor['signature'];

    // Construct post string
    $post = '';
    foreach ($smartDebitParams as $key => $value) {
      $post .= ($key != 'variable_ddi[service_user][pslid]' ? '&' : '') . $key . '=' . ($key != 'variable_ddi[service_user][pslid]' ? urlencode($value) : $serviceUserId);
    }
    // Get the API Username and Password
    $username = $this->_paymentProcessor['user_name'];
    $password = $this->_paymentProcessor['password'];

    // Send payment POST to the target URL
    $url = $this->_paymentProcessor['url_api'];
    $request_path = 'api/ddi/variable/create';

    $response = CRM_Smartdebit_Api::requestPost($url, $post, $username, $password, $request_path);

    // Take action based upon the response status
    if ($response['success']) {
      $params['trxn_id'] = $response['reference_number'];
      self::setRecurTransactionId($params);
      CRM_Smartdebit_Base::completeDirectDebitSetup($params);
      return $params;
    }
    else {
      throw new Exception($response['message'] . ': ' . CRM_Smartdebit_Api::formatResponseError($response['error']));
    }
  }

  /**
   * Add transaction Id to recurring contribution
   * @param $params
   */
  static function setRecurTransactionId(&$params) {
    // As the recur transaction is created before payment, we need to update it with our params after payment
    if (!empty($params['trxn_id'])) {
      if (!empty($params['contributionRecurID'])) {
        // Recurring transaction, so this is a recurring payment
        $recurParams = array (
          'id' => $params['contributionRecurID'],
          'trxn_id' => $params['trxn_id'],
          'contribution_status_id' => self::getInitialContributionStatus(TRUE),
        );
        // Update the recurring payment
        civicrm_api3('ContributionRecur', 'create', $recurParams);
        // Update the contribution status
        $contributionParams['id'] = $params['contributionID'];
        $contributionParams['contribution_status_id'] = self::getInitialContributionStatus(FALSE);
        civicrm_api3('Contribution', 'create', $contributionParams);
      }
      else {
        // No recurring transaction, assume this is a non-recurring payment (so create a recurring contribution with a single installment
        // Get the financial type ID
        $financialType['name'] = $params['contributionType_name'];
        $financialType=CRM_Financial_BAO_FinancialType::retrieve($financialType,$defaults);
        // Fill recurring transaction parameters
        $recurParams = array(
          'contact_id' =>  $params['contactID'],
          'create_date' => $params['receive_date'],
          'modified_date' => $params['receive_date'],
          'start_date' => $params['receive_date'],
          'amount' => $params['amount'],
          'frequency_unit' => 'year',
          'frequency_interval' => '1',
          'trxn_id'	=> $params['trxn_id'],
          'financial_type_id'	=> $financialType->id,
          'auto_renew' => '0', // Make auto renew
          'cycle_day' => $params['preferred_collection_day'],
          'currency' => $params['currencyID'],
          'invoice_id' => $params['invoiceID'],
          'installments' => 1,
          'contribution_status_id' => self::getInitialContributionStatus(TRUE),
        );
        $recur = CRM_Smartdebit_Base::createRecurContribution($recurParams);
        $params['contributionRecurID'] = $recur['id'];
        $params['contribution_recur_id'] = $recur['id'];
        // We need to link the recurring contribution and contribution record, as Civi won't do it for us (4.7.21)
        $contributionParams['id'] = $params['contributionID'];
        $contributionParams['contribution_recur_id'] = $params['contribution_recur_id'];
        $contributionParams['contact_id'] = $params['contactID'];
        $contributionParams['contribution_status_id'] = self::getInitialContributionStatus(FALSE);
        civicrm_api3('Contribution', 'create', $contributionParams);
        $params['is_recur'] = 1; // Required for CRM_Core_Payment to set contribution status = Pending
      }

      // Check and update membership
      if (!empty($params['membershipID'])) {
        self::updateMembershipStatus($params['membershipID']);
      }
    }
  }

  /**
   * Get the initial (recur) contribution status based on the desired configuration.
   * If initial_completed=TRUE we need to set initial contribution to completed.
   *
   * @param bool $isRecur TRUE if we should return status of recurring contribution instead.
   *
   * @return bool|int|null|string
   */
  static function getInitialContributionStatus($isRecur = FALSE) {
    $initialCompleted = (boolean) CRM_Smartdebit_Settings::getValue('initial_completed');

    if ($initialCompleted) {
      if ($isRecur) {
        return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'In Progress');
      }
      return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
    }
    return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
  }

  /**
   * If we are forcing initial payment status to completed we have to update the membership status as well or it will stay in pending
   * @param $membershipId
   */
  static function updateMembershipStatus($membershipId) {
    $initialCompleted = (boolean) CRM_Smartdebit_Settings::getValue('initial_completed');

    if ($initialCompleted) {
      // Force an update of the membership status
      $membership = civicrm_api3('Membership', 'getsingle', array('membership_id' => $membershipId));
      $dates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membership['membership_type_id']);

      $membershipParams = array(
        'membership_id' => $membershipId,
        'start_date' => $dates['start_date'],
        'end_date' => $dates['end_date'],
        'join_date' => $dates['join_date'],
        'skipStatusCal' => 0,
      );
      civicrm_api3('Membership', 'create', $membershipParams);
    }
  }

  /**
   * Record the response from SmartDebit after validatePayment()
   * @param $direct_debit_response
   */
  static function recordSmartDebitResponse($direct_debit_response)
  {
    $sql = "
UPDATE civicrm_direct_debit SET
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
    isset($direct_debit_response['confirmation_method']) ? $sql .= "     , confirmation_method      = \"{$direct_debit_response['confirmation_method']}\"" : NULL;
    isset($direct_debit_response['response_status']) ? $sql .= "         , response_status          = \"{$direct_debit_response['response_status']}\"" : NULL;
    isset($direct_debit_response['response_raw']) ? $sql .= "            , response_raw             = \"{$direct_debit_response['response_raw']}\"" : NULL;
    $sql .= " WHERE  ddi_reference           = \"{$direct_debit_response['ddi_reference']}\"";

    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Sets appropriate parameters for checking out to UCM Payment Collection
   *
   * @param array $params name value pair of contribution datat
   * @param $component
   * @access public
   *
   */
  function doTransferCheckout(&$params, $component)
  {
    CRM_Core_Error::fatal(ts('SmartDebit::doTransferCheckout: This function is not implemented'));
  }

  /**
   * Change the subscription amount using the Smart Debit API
   * @param string $message
   * @param array $params
   * @return bool
   */
  function changeSubscriptionAmount(&$message = '', $params = array())
  {
    if ($this->_paymentProcessor['payment_processor_type'] == 'Smart_Debit') {
      $post = '';
      $serviceUserId = $this->_paymentProcessor['signature'];
      $username = $this->_paymentProcessor['user_name'];
      $password = $this->_paymentProcessor['password'];
      $url = $this->_paymentProcessor['url_api'];
      $accountHolder = $params['account_holder'];
      $accountNumber = $params['bank_account_number'];
      $sortcode = $params['bank_identification_number'];
      $amount = $params['amount'];
      $amount = $amount * 100;
      $reference = $params['subscriptionId'];
      $frequencyType = $params['frequency_unit'];
      $eDate = $params['end_date'];
      $sDate = $params['start_date'];

      if (!empty($eDate)) {
        $endDate = strtotime($eDate);
        $endDate = date("Y-m-d", $endDate);
      }

      if (!empty($sDate)) {
        $startDate = strtotime($sDate);
        $startDate = date("Y-m-d", $startDate);
      }

      $request_path = 'api/ddi/variable/' . $reference . '/update';

      $smartDebitParams = array(
        'variable_ddi[service_user][pslid]' => $serviceUserId,
        'variable_ddi[reference_number]' => $reference,
        'variable_ddi[regular_amount]' => $amount,
        'variable_ddi[first_amount]' => $amount,
        'variable_ddi[default_amount]' => $amount,
        'variable_ddi[start_date]' => $startDate,
        'variable_ddi[end_date]' => $endDate,
        'variable_ddi[account_name]' => $accountHolder,
        'variable_ddi[sort_code]' => $sortcode,
        'variable_ddi[account_number]' => $accountNumber,
        'variable_ddi[frequency_type]' => $frequencyType
      );

      foreach ($smartDebitParams as $key => $value) {
        if (!empty($value))
          $post .= ($key != 'variable_ddi[service_user][pslid]' ? '&' : '') . $key . '=' . ($key != 'variable_ddi[service_user][pslid]' ? urlencode($value) : $serviceUserId);
      }

      $response = CRM_Smartdebit_Api::requestPost($url, $post, $username, $password, $request_path);
      if (!$response['success']) {
        $msg = CRM_Smartdebit_Api::formatResponseError($response['error']);
        $msg .= '<br />Update Subscription Failed.';
        CRM_Core_Session::setStatus(ts($msg), 'Smart Debit', 'error');
        return FALSE;
      }
      return TRUE;
    }
  }

  /**
   * Cancel the Direct Debit Subscription using the Smart Debit API
   * @param string $message
   * @param array $params
   * @return bool
   */
  function cancelSubscription($params = array())
  {
    if ($this->_processorName == 'Smart Debit Processor') {
      $post = '';
      $serviceUserId = $this->_paymentProcessor['signature'];
      $username = $this->_paymentProcessor['user_name'];
      $password = $this->_paymentProcessor['password'];
      $url = $this->_paymentProcessor['url_api'];
      try {
        $contributionRecur = civicrm_api3('ContributionRecur', 'getsingle', array(
          'sequential' => 1,
          'id' => $_GET['crid'],
        ));
      }
      catch (Exception $e) {
        return FALSE;
      }
      if (empty($contributionRecur['trxn_id'])) {
        CRM_Core_Session::setStatus(ts('The recurring contribution cannot be cancelled (No reference (trxn_id) found).'), 'Smart Debit', 'error');
        return FALSE;
      }
      $reference = $contributionRecur['trxn_id'];
      $request_path = 'api/ddi/variable/' . $reference . '/cancel';
      $smartDebitParams = array(
        'variable_ddi[service_user][pslid]' => $serviceUserId,
        'variable_ddi[reference_number]' => $reference,
      );
      foreach ($smartDebitParams as $key => $value) {
        $post .= ($key != 'variable_ddi[service_user][pslid]' ? '&' : '') . $key . '=' . ($key != 'variable_ddi[service_user][pslid]' ? urlencode($value) : $serviceUserId);
      }

      $response = CRM_Smartdebit_Api::requestPost($url, $post, $username, $password, $request_path);
      if (!$response['success']) {
        $msg = CRM_Smartdebit_Api::formatResponseError($response['error']);
        $msg .= '<br />Cancel Subscription Failed.';
        CRM_Core_Session::setStatus(ts($msg), 'Smart Debit', 'error');
        return FALSE;
      }
      return TRUE;
    }
  }

  /**
   * Called when
   * @param string $message
   * @param array $params
   * @return bool
   */
  function updateSubscriptionBillingInfo(&$message = '', $params = array())
  {
    if ($this->_paymentProcessor['payment_processor_type'] == 'Smart_Debit') {
      $postData = '';
      $serviceUserId = $this->_paymentProcessor['signature'];
      $username = $this->_paymentProcessor['user_name'];
      $password = $this->_paymentProcessor['password'];
      $url = $this->_paymentProcessor['url_api'];
      $reference = $params['subscriptionId'];
      $firstName = $params['first_name'];
      $lastName = $params['last_name'];
      $streetAddress = $params['street_address'];
      $city = $params['city'];
      $postcode = $params['postal_code'];
      $state = $params['state_province'];
      $country = $params['country'];

      $request_path = 'api/ddi/variable/' . $reference . '/update';
      $smartDebitParams = array(
        'variable_ddi[service_user][pslid]' => $serviceUserId,
        'variable_ddi[reference_number]' => $reference,
        'variable_ddi[first_name]' => $firstName,
        'variable_ddi[last_name]' => $lastName,
        'variable_ddi[address_1]' => self::replaceCommaWithSpace($streetAddress),
        'variable_ddi[town]' => $city,
        'variable_ddi[postcode]' => $postcode,
        'variable_ddi[county]' => $state,
        'variable_ddi[country]' => $country,
      );
      foreach ($smartDebitParams as $key => $value) {
        $postData .= ($key != 'variable_ddi[service_user][pslid]' ? '&' : '') . $key . '=' . ($key != 'variable_ddi[service_user][pslid]' ? urlencode($value) : $serviceUserId);
      }

      $response = CRM_Smartdebit_Api::requestPost($url, $postData, $username, $password, $request_path);
      if (!$response['success']) {
        $msg = CRM_Smartdebit_Api::formatResponseError($response['error']);
        CRM_Core_Session::setStatus(ts($msg), 'Smart Debit', 'error');
        return FALSE;
      }
      return TRUE;
    }
  }

  /**
   * Format response error for display to user
   * @param $responseErrors
   * @return string
   */
  static function formatResponseError($responseErrors)
  {
    $errorMsg = '';
    if (!is_array($responseErrors)) {
      $errorMsg = $responseErrors . '<br />';
      $errorMsg .= '<br />';
    } else {
      foreach ($responseErrors as $error) {
        $errorMsg .= $error . '<br />';
      }
      $errorMsg .= '<br />';
    }
    $errorMsg .= 'Please correct the errors and try again';
    return $errorMsg;
  }

  /**
   * Get ID of payment processor with class name "Payment_Smartdebit"
   * @return int
   */
  static function getSmartDebitPaymentProcessorID() {
    $result = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 1,
      'return' => array("id"),
      'class_name' => "Payment_Smartdebit",
      'is_test' => 0,
    ));
    if ($result['count'] > 0) {
      // Return the first one, it's possible there is more than one payment processor of the same type configured
      //  so we'll just return the first one here.
      if (isset($result['values'][0]['id'])) {
        return $result['values'][0]['id'];
      }
    }
    // If we don't have a valid processor id return false;
    return FALSE;
  }

  /**
   * Get the name of the payment processor
   * @param $ppId
   * @return string
   */
  static function getSmartDebitPaymentProcessorName($ppId) {
    $paymentProcessorName = 'Unknown';
    try {
      $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', array(
        'return' => array("name"),
        'id' => $ppId,
      ));
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
