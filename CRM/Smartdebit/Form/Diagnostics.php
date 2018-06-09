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

use CRM_Smartdebit_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Smartdebit_Form_Diagnostics extends CRM_Core_Form {

  function buildQuickForm() {
    parent::buildQuickForm();

    try {
      $sdStatus = CRM_Smartdebit_Api::getSystemStatus(FALSE);
      $sdStatusTest = CRM_Smartdebit_Api::getSystemStatus(TRUE);
      $this->assign('sdStatus', $sdStatus);
      $this->assign('sdStatusTest', $sdStatusTest);

      // Get counts
      $counts['mandatewithrecur'] = CRM_Smartdebit_Mandates::count(TRUE);
      $counts['mandatenorecur'] = CRM_Smartdebit_Mandates::count(FALSE) - $counts['mandatewithrecur'];
      $counts['collectionssuccess'] = CRM_Smartdebit_CollectionReports::count(array('successes' => TRUE, 'rejects' => FALSE));
      $counts['collectionsrejected'] = CRM_Smartdebit_CollectionReports::count(array('successes' => FALSE, 'rejects' => TRUE));
      $counts['collectionreports'] = CRM_Smartdebit_CollectionReports::countReports();
      $this->assign('sdcounts', $counts);
      $collectionReports = CRM_Smartdebit_CollectionReports::getReports(array('limit' => 10));
      $this->assign('collectionreports', $collectionReports);
    } catch (Exception $e) {
      // Do nothing here. Api will throw exception if API URL is not configured, which it won't be if
      // Smartdebit payment processor has not been setup yet.
      $this->assign('apiStatus', 'No Smartdebit payment processors are configured yet!');
    }

    $this->addButtons(array(
      array (
        'type' => 'cancel',
        'name' => ts('Done'),
      )
    ));
  }

}
