<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_Form_ReconciliationFixConfirm
 *
 * This page is used to confirm and submit the fix for the contact record
 * Path: civicrm/smartdebit/reconciliation/fix/confirm
 */
class CRM_Smartdebit_Form_ReconciliationFixConfirm extends CRM_Core_Form {

  public function buildQuickForm() {
    // Get parameters
    $cid = CRM_Utils_Array::value('cid', $_GET);
    $mid = CRM_Utils_Array::value('mid', $_GET);
    $cr_id = CRM_Utils_Array::value('cr_id', $_GET);
    $reference_number = CRM_Utils_Array::value('reference_number', $_GET);
    $this->addElement('hidden', 'cid', $cid);
    $this->addElement('hidden', 'mid', $mid);
    $this->addElement('hidden', 'cr_id', $cr_id);
    $this->addElement('hidden', 'reference_number', $reference_number);

    $this->assign('reference_number', $reference_number);

    // Get the smart Debit mandate details
    if (CRM_Utils_Array::value('reference_number', $_GET)) {
      $smartDebitResponse = CRM_Smartdebit_Mandates::getbyReference(['trxn_id' => CRM_Utils_Array::value('reference_number', $_GET), 'refresh' => FALSE]);
      $smartDebitMandate = $smartDebitResponse[0];
      $this->assign('SDMandateArray', $smartDebitMandate);
    }

    // Get contact details if set
    if(!empty($cid)){
      $contact = CRM_Smartdebit_Utils::getContactDetails($cid);
      $this->assign('aContact', $contact);
    }
    // If 'Donation' option is chosen for membership, don't process
    if(!empty($mid) && $mid != 'donation') {
      $membership = CRM_Smartdebit_Utils::getContactMemberships($cid, $mid);
      $this->assign('aMembership', $membership);
    }
    // If 'Create New Recurring' option is chosen for recurring, don't process
    if(!empty($cr_id) && $cr_id != 'new_recur') {
      $cRecur = CRM_Smartdebit_Utils::getRecurringContributionRecord($cr_id);
      $this->assign('aContributionRecur', $cRecur);
    }

    // Back URL
    $params = sprintf('cid=%d&mid=%d&cr_id=%d&reference_number=%s', $cid, $mid, $cr_id, $reference_number);
    $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/fix/select', $params, FALSE, NULL, FALSE);

    $buttons[] = [
      'type' => 'back',
      'js' => ['onclick' => "location.href='{$url}'; return false;"],
      'name' => ts('Back')
    ];
    $buttons[] = [
      'type'      => 'submit',
      'name'      => ts('Confirm')
    ];
    $this->addButtons($buttons);

    CRM_Utils_System::setTitle('Confirm changes to Contact');
    parent::buildQuickForm();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function postProcess() {
    $submitValues = $this->_submitValues;
    $params['contact_id'] = $submitValues['cid'];
    $params['payer_reference'] = $submitValues['reference_number'];
    $params['contribution_recur_id'] = $submitValues['cr_id'];
    $params['membership_id'] = $submitValues['mid'];
    $recurResult = CRM_Smartdebit_Form_ReconciliationList::reconcileRecordWithCiviCRM($params);

    if ($recurResult) {
      CRM_Core_Session::setStatus('Successfully fixed record for ' . $params['payer_reference'] . ' (Contact ID: ' . $params['contact_id'] . ')');
    }
    $url = CRM_Utils_System::url(CRM_Smartdebit_Utils::$reconcileUrl . '/list');
    CRM_Utils_System::redirect($url);
  }
}
