<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_Form_ReconciliationFixSelect
 *
 * Path: civicrm/smartdebit/reconciliation/fix/select
 */
class CRM_Smartdebit_Form_ReconciliationFixSelect extends CRM_Core_Form {
  CONST c_current_membership_status = "Current"; // MV, to set current membership as default

  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    // Don't try and load form if no reference number is specified!
    $reference_number = CRM_Utils_Request::retrieve('reference_number', 'String');
    if (empty($reference_number)) {
      CRM_Core_Session::setStatus('You must specify a reference number to reconcile a transaction!', 'Smart Debit', 'error');
      $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', 'reset=1');
      CRM_Core_Session::singleton()->pushUserContext($url);
      return;
    }

    $cid = CRM_Utils_Array::value('cid', $_GET);

    $this->addElement( 'select'
      , 'membership_record'
      , ts('Membership')
      , ['' => ts('Loading...')]
    );

    $this->addElement( 'select'
      , 'contribution_recur_record'
      , ts('Recurring Contribution')
      , ['' => ts('Loading...')]
    );

    $this->addEntityRef('contact_name', ts('Contact'), [
      'create' => FALSE,
      'api' => ['extra' => ['email']],
    ]);

    $this->addElement('hidden', 'cid', $cid);
    $this->addElement('text', 'reference_number', 'Smart Debit Reference', ['size' => 50, 'maxlength' => 255]);
    $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', 'reset=1');
    $buttons[] = [
      'type' => 'back',
      'js' => ['onclick' => "location.href='{$url}'; return false;"],
      'name' => ts('Back')
    ];
    $buttons[] = [
      'type' => 'next',
      'name' => ts('Continue')
    ];
    $this->addButtons($buttons);

    // Get the smart Debit mandate details
    if (CRM_Utils_Array::value('reference_number', $_GET)) {
      $smartDebitResponse = CRM_Smartdebit_Mandates::getbyReference(['trxn_id' => CRM_Utils_Array::value('reference_number', $_GET)]);
      $smartDebitMandate = $smartDebitResponse;
      $this->assign('SDMandateArray', $smartDebitMandate);
    }

    // Display the smart debit payments details
    $el = $this->addElement('text', 'first_name', 'First Name', ['size' => 50, 'maxlength' => 255]);
    $el->freeze();
    $el = $this->addElement('text', 'last_name', 'Last Name', ['size' => 50, 'maxlength' => 255]);
    $el->freeze();
    $el = $this->addElement('text', 'email_address', 'Email Address', ['size' => 50, 'maxlength' => 255]);
    $el->freeze();
    $el = $this->addElement('text', 'default_amount', 'Amount', ['size' => 50, 'maxlength' => 255]);
    $el->freeze();
    $el = $this->addElement('text', 'start_date', 'Start Date', ['size' => 50, 'maxlength' => 255]);
    $el->freeze();

    $this->assign('memStatusCurrent', self::c_current_membership_status); //MV, to set the current membership as default, when ajax loading
    $this->assign('cid', $cid);
    $this->addFormRule(['CRM_Smartdebit_Form_ReconciliationFixSelect', 'formRule'], $this);

    CRM_Utils_System::setTitle('Select Contact Membership and Recurring Contribution');

    parent::buildQuickForm();
  }

  public static function formRule($params) {
    $errors = [];
    // Check end date greater than start date
    if (empty($params['cid'])) {
      $errors['contact_name'] = 'Contact Not Matched In CiviCRM';
    }
    if (!empty($errors)) {
      return $errors;
    }
    return TRUE;
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    $defaults['reference_number'] = CRM_Utils_Array::value('reference_number', $_GET);
    $defaults['cid']              = CRM_Utils_Array::value('cid', $_GET);
    $defaults['mid']              = CRM_Utils_Array::value('mid', $_GET);
    $defaults['cr_id']              = CRM_Utils_Array::value('cr_id', $_GET);

    return $defaults;
  }

  public function postProcess() {
    $submitValues = $this->_submitValues;
    $cid = $submitValues['cid'];
    $mid = $submitValues['membership_record'];
    $reference_number = $submitValues['reference_number'];
    $cr_id = $submitValues['contribution_recur_record'];
    $params = sprintf('cid=%d&mid=%d&cr_id=%d&reference_number=%s', $cid, $mid, $cr_id, $reference_number);
    $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/fix/confirm', $params);
    CRM_Core_Session::singleton()->pushUserContext($url);
  }
}
