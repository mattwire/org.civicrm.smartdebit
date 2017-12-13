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
    $smartDebitResponse = CRM_Smartdebit_Mandates::getbyReference($reference_number, TRUE);

    // Convert fields to labels for display
    foreach ($smartDebitResponse as $key => $value) {
      if ($key == 'current_state') {
        $value = CRM_Smartdebit_Api::SD_STATES[$value];
      }
      $smartDebitDetails[] = array('label' => $key, 'text' => $value);
    }

    $this->assign('transactionId', $reference_number);
    $this->assign('smartDebitDetails', $smartDebitDetails);

    $url = $_SERVER['HTTP_REFERER'];
    $buttons[] = array(
      'type' => 'back',
      'js' => array('onclick' => "location.href='{$url}'; return false;"),
      'name' => ts('Back'));
    $this->addButtons($buttons);

    parent::buildQuickForm();
  }

  public function postProcess() {
    CRM_Core_Session::singleton()->pushUserContext($_SESSION['http_referer']);
    parent::postProcess();
  }
}
