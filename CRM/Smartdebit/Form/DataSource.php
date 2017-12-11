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
    // Add datepicker to select the end date
    $this->add('datepicker', 'collection_date', ts('Collection Date'), array(), FALSE, array('time' => FALSE));
    $this->addButtons(array(
        array(
          'type' => 'submit',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ),
      )
    );
    CRM_Utils_System::setTitle('Synchronise CiviCRM with Smart Debit: Choose Date Range');
  }

  /**
   * Process the collection report
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $exportValues     = $this->controller->exportValues();
    $dateOfCollection = $exportValues['collection_date'];

    $queryParams='';
    // Set collection date, otherwise we'll default to todays date
    if (!empty($dateOfCollection)) {
      $dateOfCollection = date('Y-m-d', strtotime($dateOfCollection));
      $queryParams.='collection_date='.urlencode($dateOfCollection);
    }

    $collections = CRM_Smartdebit_Auddis::getSmartdebitCollectionReports($dateOfCollection);
    if (!isset($collections['error'])) {
      CRM_Smartdebit_Auddis::saveSmartdebitCollectionReport($collections);
    }

    if (!empty($queryParams)){
      $queryParams.='&';
    }
    $queryParams.='reset=1';
    $url = CRM_Utils_System::url('civicrm/smartdebit/syncsd/select', $queryParams); // SyncSD form
    CRM_Utils_System::redirect($url);
  }
}

