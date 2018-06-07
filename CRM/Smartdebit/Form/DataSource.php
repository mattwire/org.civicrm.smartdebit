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
 * Class CRM_Smartdebit_Form_DataSource
 *
 * Path: civicrm/smartdebit/syncsd
 * This is the first step of the import and allows the user to select a collection date window of one month (specifying the end date)
 */
class CRM_Smartdebit_Form_DataSource extends CRM_Core_Form {

  public function buildQuickForm() {
    $this->assign('period', CRM_Smartdebit_Settings::getValue('cr_cache'));

    try {
      $syncJob = civicrm_api3('Job', 'getsingle', [
        'return' => ["is_active"],
        'name' => "Sync from Smart Debit",
        'options' => ['sort' => "is_active DESC", 'limit' => 1],
      ]);
      $this->assign('sync_active', CRM_Utils_Array::value('is_active', $syncJob, 0));
    }
    catch (Exception $e) {}

    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ),
      )
    );
    CRM_Utils_System::setTitle(ts('Synchronise CiviCRM with Smart Debit'));
  }

  /**
   * Process the collection report
   *
   * @throws \Exception
   */
  public function postProcess() {
    // If no collection date specified we retrieve the daily collection report (just like scheduled sync)
    $count = CRM_Smartdebit_CollectionReports::retrieveDaily();

    $queryParams = [];
    $queryParams['crcount'] = $count;
    $queryParams['reset'] = 1;
    $url = CRM_Utils_System::url('civicrm/smartdebit/syncsd/select', $queryParams); // SyncSD form
    CRM_Utils_System::redirect($url);
  }
}

