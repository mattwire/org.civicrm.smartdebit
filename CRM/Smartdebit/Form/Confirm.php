<?php

/**
 * Class CRM_Smartdebit_Form_Confirm
 *
 * Path: civicrm/smartdebit/syncsd/confirm
 * This displays a confirmation button for import of matched/unmatched, failed and successful contributions from Smartdebit
 * Clicking next will start an import runner which cannot be cancelled.
 * This is the final page in the import process (starting at civicrm/smartdebit/syncsd)
 */
class CRM_Smartdebit_Form_Confirm extends CRM_Core_Form {

  private $status = 0;

  public function preProcess() {
    $state = CRM_Utils_Request::retrieve('state', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');

    if ($state == 'done') {
      $this->status = 1;
      $rejectedAuddis = smartdebit_civicrm_getSetting('rejected_auddis');
      $rejectedArudd = smartdebit_civicrm_getSetting('rejected_arudd');
      $this->assign('rejectedAuddis', $rejectedAuddis);
      $this->assign('rejectedArudd', $rejectedArudd);
      $rejectedids = $rejectedAuddis+$rejectedArudd;
      $this->assign('rejectedids', $rejectedAuddis+$rejectedArudd);

      $getSQL = "SELECT * FROM veda_smartdebit_success_contributions";
      $getDAO = CRM_Core_DAO::executeQuery($getSQL);
      $ids = $totalContributionAmount = array();
      while ($getDAO->fetch()){
        $transactionURL = CRM_Utils_System::url("civicrm/contact/view/contribution", "action=view&reset=1&id={$getDAO->contribution_id}&cid={$getDAO->contact_id}&context=home");
        $contactURL     = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$getDAO->contact_id}");
        $ids[] = array(
          'transaction_id'  => sprintf("<a href=%s>%s</a>", $transactionURL, $getDAO->transaction_id),
          'display_name'    => sprintf("<a href=%s>%s</a>", $contactURL, $getDAO->contact),
          'amount'          => CRM_Utils_Money::format($getDAO->amount),
          'frequency'       => ucwords($getDAO->frequency),
        );
        $totalContributionAmount[] =  $getDAO->amount;
      }
      $totalAmountAdded = array_sum($totalContributionAmount);
      $totalAmountAdded = CRM_Utils_Money::format($totalAmountAdded);
      $this->assign('ids', $ids);
      $this->assign('totalAmountAdded', $totalAmountAdded);
      $this->assign('totalValidContribution', count($ids));
      $this->assign('totalRejectedContribution', count($rejectedids));
    }
    $this->assign('status', $this->status);
  }

  public function buildQuickForm() {
    $auddisIDs = array_filter(explode(',', CRM_Utils_Request::retrieve('auddisID', 'String', $this, false)));
    $aruddIDs = array_filter(explode(',', CRM_Utils_Request::retrieve('aruddID', 'String', $this, false)));
    $this->add('hidden', 'auddisIDs', serialize($auddisIDs));
    $this->add('hidden', 'aruddIDs', serialize($aruddIDs));
    $redirectUrlBack = CRM_Utils_System::url('civicrm', 'reset=1');

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Confirm Sync'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'js' => array('onclick' => "location.href='{$redirectUrlBack}'; return false;"),
          'name' => ts('Cancel'),
        )
      )
    );

    if ($this->status) {
      CRM_Utils_System::setTitle('Synchronise CiviCRM with Smart Debit: Results of Sync');
    }
    else {
      CRM_Utils_System::setTitle('Synchronise CiviCRM with Smart Debit: Confirm Sync');
    }
  }

  public function postProcess() {
    $params     = $this->controller->exportValues();
    $auddisIDs = unserialize($params['auddisIDs']);
    $aruddIDs = unserialize($params['aruddIDs']);

    $runner = CRM_Smartdebit_Sync::getRunner(TRUE, $auddisIDs, $aruddIDs);
    CRM_Smartdebit_Sync::runViaWeb($runner);
  }
}
