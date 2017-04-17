<?php

/**
 * Class CRM_Smartdebit_Form_ReconciliationFixSelect
 *
 * Path: civicrm/smartdebit/reconciliation/fixmissingcivi
 */
class CRM_Smartdebit_Form_ReconciliationFixSelect extends CRM_Core_Form {
  CONST c_current_membership_status = "Current"; // MV, to set current membership as default 

  public function preProcess() {
    parent::preProcess();
  }

  public function buildQuickForm() {
    $this->addElement( 'select'
      , 'membership_record'
      , ts('Membership')
      , array( '' => ts('Loading...'))
    );

    $this->addElement( 'select'
      , 'contribution_recur_record'
      , ts('Recurring Contribution')
      , array( '' => ts('Loading...'))
    );

    //$this->addElement('text', 'contact_name', 'Contact', array('size' => 50, 'maxlength' => 255));
    $this->addEntityRef('contact_name', ts('Contact'), array(
      'create' => FALSE,
      'api' => array('extra' => array('email')),
    ));
    $this->addElement('hidden', 'cid', 'cid');
    $this->addElement('text', 'reference_number', 'Smart Debit Reference', array('size' => 50, 'maxlength' => 255));
    $buttons[] = array(
      'type' => 'next',
      'name' => ts('Continue'));
    $this->addButtons($buttons);

    // Get the smart Debit mandate details
    if (CRM_Utils_Array::value('reference_number', $_GET)) {
      $smartDebitResponse = CRM_Smartdebit_Sync::getSmartdebitPayerContactDetails(CRM_Utils_Array::value('reference_number', $_GET));
      $smartDebitMandate = $smartDebitResponse[0];
      $this->assign('SDMandateArray', $smartDebitMandate);
    }

    // Display the smart debit payments details
    $el = $this->addElement('text', 'first_name', 'First Name', array('size' => 50, 'maxlength' => 255));
    $el->freeze();
    $el = $this->addElement('text', 'last_name', 'Last Name',array('size' => 50, 'maxlength' => 255));
    $el->freeze();
    $el = $this->addElement('text', 'email_address', 'Email Address', array('size' => 50, 'maxlength' => 255));
    $el->freeze();
    $el = $this->addElement('text', 'regular_amount', 'Amount', array('size' => 50, 'maxlength' => 255));
    $el->freeze();
    $el = $this->addElement('text', 'start_date', 'Start Date', array('size' => 50, 'maxlength' => 255));
    $el->freeze();

    $this->assign( 'memStatusCurrent', self::c_current_membership_status ); //MV, to set the current membership as default, when ajax loading
    $cid = CRM_Utils_Array::value('cid', $_GET);
    $this->assign('cid', $cid);
    $this->addFormRule(array('CRM_Smartdebit_Form_ReconciliationFixSelect', 'formRule'), $this);

    CRM_Utils_System::setTitle('Select Contact Membership and Recurring Contribution');

    parent::buildQuickForm();
  }

  public function formRule($params) {
    $errors = array();
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
   * @return None
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults['reference_number'] = CRM_Utils_Array::value('reference_number', $_GET);
    $defaults['cid']              = CRM_Utils_Array::value('cid', $_GET);
    return $defaults;
  }

  public function postProcess() {
    $submitValues = $this->_submitValues;
    $cid = $submitValues['cid'];
    $mid = $submitValues['membership_record'];
    $reference_number = $submitValues['reference_number'];
    $cr_id = $submitValues['contribution_recur_record'];
    $params = sprintf('cid=%d&mid=%d&cr_id=%d&reference_number=%s', $cid, $mid, $cr_id, $reference_number);
    $url = CRM_Utils_System::url('civicrm/smartdebit/reconciliation/fix-contact-rec-confirm',$params);
    CRM_Utils_System::redirect($url);
    parent::postProcess();
  }
}
