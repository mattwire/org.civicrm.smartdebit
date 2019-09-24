<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Smartdebit_Form_Payerdetails extends CRM_Core_Form {
  public function buildQuickForm() {
    if ($this->_flagSubmitted) return;

    $reference_number = CRM_Utils_Array::value('reference_number', $_GET);
    if (empty($reference_number)) {
      CRM_Core_Error::statusBounce('You must specify a reference number!');
      return;
    }

    // Get Smartdebit Mandate details
    $smartDebitResponse = CRM_Smartdebit_Mandates::getbyReference(['trxn_id' => $reference_number]);
    $smartDebitDetails = self::formatDetails($smartDebitResponse);

    foreach ($smartDebitDetails as $key => $value) {
      $smartDebitDisplay[] = ['label' => $key, 'text' => $value];
    }

    $this->assign('transactionId', $reference_number);
    $this->assign('smartDebitDetails', $smartDebitDisplay);

    $url = $_SERVER['HTTP_REFERER'];
    $buttons[] = [
      'type' => 'back',
      'js' => ['onclick' => "location.href='{$url}'; return false;"],
      'name' => ts('Back')
    ];
    $this->addButtons($buttons);

    parent::buildQuickForm();
  }

  public static function formatDetails($smartDebitResponse) {
    // Convert fields for display
    $smartDebitDetails['Title'] = isset($smartDebitResponse['title']) ? $smartDebitResponse['title'] : NULL;
    $smartDebitDetails['First Name'] = isset($smartDebitResponse['first_name']) ? $smartDebitResponse['first_name'] : NULL;
    $smartDebitDetails['Last Name'] = isset($smartDebitResponse['last_name']) ? $smartDebitResponse['last_name'] : NULL;
    $smartDebitDetails['Email Address'] = isset($smartDebitResponse['email_address']) ? $smartDebitResponse['email_address'] : NULL;
    $smartDebitDetails['Address 1'] = isset($smartDebitResponse['address_1']) ? $smartDebitResponse['address_1'] : NULL;
    $smartDebitDetails['Address 2'] = isset($smartDebitResponse['address_2']) ? $smartDebitResponse['address_2'] : NULL;
    $smartDebitDetails['Address 3'] = isset($smartDebitResponse['address_3']) ? $smartDebitResponse['address_3'] : NULL;
    $smartDebitDetails['Town'] = isset($smartDebitResponse['town']) ? $smartDebitResponse['town'] : NULL;
    $smartDebitDetails['County'] = isset($smartDebitResponse['county']) ? $smartDebitResponse['county'] : NULL;
    $smartDebitDetails['Postcode'] = isset($smartDebitResponse['postcode']) ? $smartDebitResponse['postcode'] : NULL;
    $smartDebitDetails['First Amount'] = isset($smartDebitResponse['first_amount']) ? $smartDebitResponse['first_amount'] : NULL;
    $smartDebitDetails['Default Amount'] = isset($smartDebitResponse['default_amount']) ? $smartDebitResponse['default_amount'] : NULL;
    $smartDebitDetails['Frequency Type'] = isset($smartDebitResponse['frequency_type']) ? $smartDebitResponse['frequency_type'] : NULL;
    $smartDebitDetails['Frequency Factor'] = isset($smartDebitResponse['frequency_factor']) ? $smartDebitResponse['frequency_factor'] : NULL;
    $smartDebitDetails['Start Date'] = isset($smartDebitResponse['start_date']) ? $smartDebitResponse['start_date'] : NULL;
    $smartDebitDetails['State'] = isset($smartDebitResponse['current_state']) ? CRM_Smartdebit_Api::SD_STATES[$smartDebitResponse['current_state']] : NULL;
    $smartDebitDetails['Reference Number (Transaction ID)'] = isset($smartDebitResponse['reference_number']) ? $smartDebitResponse['reference_number'] : NULL;
    $smartDebitDetails['Payer Reference (Contact Id)'] = isset($smartDebitResponse['payerReference']) ? $smartDebitResponse['payerReference'] : NULL;
    return $smartDebitDetails;
  }

  public function postProcess() {
    CRM_Core_Session::singleton()->pushUserContext($_SESSION['http_referer']);
    parent::postProcess();
  }
}
