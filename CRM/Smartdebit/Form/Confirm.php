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
      $rejectedAuddis = CRM_Smartdebit_Settings::getValue('rejected_auddis');
      $rejectedArudd = CRM_Smartdebit_Settings::getValue('rejected_arudd');
      $this->assign('rejectedAuddis', $rejectedAuddis);
      $this->assign('rejectedArudd', $rejectedArudd);
      $rejectedids = array_merge(is_array($rejectedAuddis) ? $rejectedAuddis : array(), is_array($rejectedArudd) ? $rejectedArudd : array());
      $this->assign('rejectedids', $rejectedids);

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
    // Retrieve auddisIDs/aruddIDs if specified as parameters
    $auddisIDs = CRM_Utils_Request::retrieve('auddisID', 'String', $this, false);
    if (isset($auddisIDs)) {
      $auddisIDs = array_filter(explode(',', $auddisIDs));
      $this->add('hidden', 'auddisIDs', serialize($auddisIDs));
    }
    $aruddIDs = CRM_Utils_Request::retrieve('aruddID', 'String', $this, false);
    if (isset($aruddIDs)) {
      $aruddIDs = array_filter(explode(',', $aruddIDs));
      $this->add('hidden', 'aruddIDs', serialize($aruddIDs));
    }

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
    isset($params['auddisIDs']) ? $auddisIDs = unserialize($params['auddisIDs']) : $auddisIDs = NULL;
    isset($params['aruddIDs']) ? $aruddIDs = unserialize($params['aruddIDs']) : $aruddIDs = NULL;

    $runner = CRM_Smartdebit_Sync::getRunner(TRUE, $auddisIDs, $aruddIDs);
    CRM_Smartdebit_Sync::runViaWeb($runner);
  }
}
