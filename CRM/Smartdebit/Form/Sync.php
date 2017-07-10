<?php

/**
 * Class CRM_Smartdebit_Form_Sync
 * This form is accessed at civicrm/smartdebit/sync
 * It shows the results of the Smart Debit Sync Scheduled job
 */
class CRM_Smartdebit_Form_Sync extends CRM_Core_Form
{
  function preProcess()
  {
    $state = CRM_Utils_Request::retrieve('state', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');
    if ($state == 'done') {
      $stats = smartdebit_civicrm_getSetting('sd_stats');
      $total = smartdebit_civicrm_getSetting('total');
      $stats['Total'] = $total;
      $this->assign('stats', $stats);
    }
  }

  public function buildQuickForm()
  {
    // Create the Submit Button.
    $buttons = array(
      array(
        'type' => 'submit',
        'name' => ts('Run Smart Debit Sync now'),
      ),
    );
    // Add the Buttons.
    $this->addButtons($buttons);

    $this->setTitle('Smart Debit Sync Scheduled Job');
  }

  public function postProcess()
  {
    $financialType = smartdebit_civicrm_getSetting('financial_type');
    if (empty($financialType)) {
      CRM_Core_Session::setStatus(ts('Make sure financial Type is set in the setting'), 'Smart Debit', 'error');
      return FALSE;
    }
    $runner = CRM_Smartdebit_Sync::getRunner();
    CRM_Smartdebit_Sync::runViaWeb($runner);
  }
}
