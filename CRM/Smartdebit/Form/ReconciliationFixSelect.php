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
      , array( '' => ts('Loading...'))
    );

    $this->addElement( 'select'
      , 'contribution_recur_record'
      , ts('Recurring Contribution')
      , array( '' => ts('Loading...'))
    );

    $this->addEntityRef('contact_name', ts('Contact'), array(
      'create' => FALSE,
      'api' => array('extra' => array('email')),
    ));

    $this->addElement('hidden', 'cid', $cid);
    $this->addElement('text', 'reference_number', 'Smart Debit Reference', array('size' => 50, 'maxlength' => 255));
    $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list', 'reset=1');
    $buttons[] = array(
      'type' => 'back',
      'js' => array('onclick' => "location.href='{$url}'; return false;"),
      'name' => ts('Back'));
    $buttons[] = array(
      'type' => 'next',
      'name' => ts('Continue'));
    $this->addButtons($buttons);

    // Get the smart Debit mandate details
    if (CRM_Utils_Array::value('reference_number', $_GET)) {
      $smartDebitResponse = CRM_Smartdebit_Mandates::getbyReference(CRM_Utils_Array::value('reference_number', $_GET), TRUE);
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
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
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
