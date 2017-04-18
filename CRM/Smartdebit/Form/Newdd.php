<?php

/**
 * Class CRM_Smartdebit_Form_Newdd
 * This form is accessed at civicrm/smartdebit/new
 * It allows for creation of a new membership direct debit via the backend
 */
class CRM_Smartdebit_Form_Newdd extends CRM_Core_Form
{
  public $_contactID;

  public $_paymentProcessor = array();

  public $_id;

  public $_action;

  public $_paymentFields = array();

  public $_fields = array();

  public $_bltID = 5;

  public $_membershipAmount;

  public function preProcess()
  {
    // Check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::ADD)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    // Get the contact id
    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    // Get the action.
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    // Get the membership id
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    // Get the smart debit payment processor details
    $this->_paymentProcessor = CRM_Smartdebit_Auddis::getSmartdebitUserDetails();

  }

  public function buildQuickForm()
  {
    // Membership amount
    $this->addMoney('amount', ts('Amount'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'total_amount'), TRUE, 'currency', NULL);
    // Membership Frequench Month/Year
    $this->add('hidden', 'frequency_unit');
    $this->add('hidden', 'frequency_interval');

    $submitButton = array(
      array('type' => 'upload',
        'name' => ts('Confirm Direct Debit'),
        'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE,),
      array('type' => 'cancel',
        'name' => ts('Cancel'),)
    );

    // Build Direct Debit Payment Fields including Billing
    $ddForm = new CRM_Smartdebit_Form_Main();
    $ddForm->buildDirectDebitForm($this);
    // Required for validation
    $defaults['ddi_reference'] = CRM_Smartdebit_Base::getDDIReference();
    $this->setDefaults($defaults);
    // Required for billing blocks to be displayed
    $this->assign('bltID', $this->_bltID);

    $this->addFormRule(array('CRM_Smartdebit_Form_Newdd', 'formRule'), $this);
    $this->addButtons($submitButton);
  }

  public function setDefaultValues()
  {
    $membershipRecord = civicrm_api3('Membership', 'getsingle', array(
      'sequential' => 1,
      'return' => array("membership_type_id.minimum_fee", "membership_type_id.duration_unit", "membership_type_id.duration_interval"),
      'id' => $this->_id,
    ));
    $defaults['amount'] = $membershipRecord['membership_type_id.minimum_fee'];
    $this->_membershipAmount = $defaults['amount'];
    $defaults['frequency_unit'] = $membershipRecord['membership_type_id.duration_unit'];
    $defaults['frequency_interval'] = $membershipRecord['membership_type_id.duration_interval'];
    return $defaults;
  }

  public static function formRule($fields, $files, $self)
  {
    $errors = array();
    if ($fields['amount'] < $self->_membershipAmount) {
      $errors['amount'] = ts('Amount can not be less than corresponding membership amount');
      return $errors;
    }
    $validateOutput = CRM_Core_Payment_Smartdebit::validatePayment($fields, $files, $self);
    if ($validateOutput['is_error'] == 1) {
      $errors['_qf_default'] = $validateOutput['error_message'];
    }
    return $errors ? $errors : TRUE;
  }

  function postProcess()
  {
    $params = $this->controller->exportValues($this->_name);
    $params['contactID'] = $this->_contactID;
    self::setupMembershipDirectDebit($this->_id, $this->_contactID);
  }

  /**
   * Create recurring contribution, update contribution and link membership to recurring contribution
   *
   * @param $membershipId
   * @param $contactId
   */
  static function setupMembershipDirectDebit($membershipId, $contactId)
  {
    $smartDebitResponse = CRM_Core_Payment_Smartdebit::doDirectPayment($params);
    if ($smartDebitResponse['is_error'] == 1) {
      CRM_Core_Session::singleton()->pushUserContext($params['entryURL']);
      return;
    }
    $start_date = date('Y-m-d', strtotime($smartDebitResponse['start_date']));
    $trxn_id = $smartDebitResponse['trxn_id'];

    $membershipRecord = civicrm_api3('Membership', 'getsingle', array(
      'sequential' => 1,
      'id' => $membershipId,
    ));

    $contributionRecurID = $membershipRecord['contribution_recur_id'];

    // Get latest contribution record
    $contributionRecord = CRM_Smartdebit_Utils::getContributionRecordForRecurringContribution($contributionRecurID);
    if (empty($contributionRecord['is_error'])) {
      $contributionID = $contributionRecord['id'];
    }

    // Need the financial_type_id
    $membershipTypeRecord = civicrm_api3('MembershipType', 'getsingle', array(
      'sequential' => 1,
      'id' => $membershipRecord['membership_type_id'],
    ));

    if (empty($contributionRecurID)) {
      // Got no recurring contribution so we'll create one
      // Build recur params
      $recurParams = array(
        'contact_id' => $contactId,
        'create_date' => date('YmdHis'),
        'modified_date' => date('YmdHis'),
        'start_date' => CRM_Utils_Date::processDate($start_date),
        'amount' => $params['amount'],
        'financial_type_id' => $membershipTypeRecord['financial_type_id'],
        'auto_renew' => '1', // Make auto renew
        'processor_id' => $trxn_id,
      );
      $recurParams['frequency_type'] = $smartDebitResponse['frequency_type'];
      $recurParams['frequency_factor'] = $smartDebitResponse['frequency_factor'];

      $recurRecord = CRM_Smartdebit_Base::createRecurContribution($recurParams);
      if (empty($recurRecord['is_error'])) {
        $contributionRecurID = $recurRecord['id'];
      }
    } elseif ($contributionRecurID && empty($contributionID)) {
      // Got a recurring contribution but no contribution so we'll create a membership payment
      $contributionParams = array(
        'financial_type_id' => $membershipTypeRecord['financial_type_id'],
        'contact_id' => $contactId,
        'source' => 'Offline Membership Direct Debit',
        'total_amount' => $params['amount'],
        'trxn_id' => $trxn_id,
        'contribution_recur_id' => $contributionRecurID,
        'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
        'receive_date' => CRM_Utils_Date::processDate($start_date),
      );

      $contributionRecord = CRM_Smartdebit_Base::createContribution($contributionParams);
      if (empty($contributionRecord['is_error'])) {
        $contributionID = $contributionRecord['id'];
      }
      // Attach Contribution to Membership
      $membershipPayment = civicrm_api3('MembershipPayment', 'create', array(
        'sequential' => 1,
        'membership_id' => $membershipId,
        'contribution_id' => $contributionID,
      ));
    } elseif ($contributionRecurID && $contributionID) {
      // Update Contribution trxn_id, recur_id, receive_date if there was already a contribution for this membership
      $contributionParams = array(
        'contribution_recur_id' => $contributionRecurID,
        'trxn_id' => $trxn_id,
        'receive_date' => CRM_Utils_Date::processDate($start_date),
        'id' => $contributionID,
      );
      $contributionRecord = CRM_Smartdebit_Base::createContribution($contributionParams);
    }
    // Link membership to recurring contribution
    $params['contribution_recur_id'] = $contributionRecurID;
    $params['membership_id'] = $membershipId;
    $params['contact_id'] = $contactId;
    CRM_Smartdebit_Form_ReconciliationList::linkMembershipToRecurContribution($params);
  }
}


