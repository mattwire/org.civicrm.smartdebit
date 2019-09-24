<?php
/**
 * https://civicrm.org/licensing
 */

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
      $counts['collectionssuccess'] = CRM_Smartdebit_CollectionReports::count(['successes' => TRUE, 'rejects' => FALSE]);
      $counts['collectionsrejected'] = CRM_Smartdebit_CollectionReports::count(['successes' => FALSE, 'rejects' => TRUE]);
      $counts['collectionreports'] = CRM_Smartdebit_CollectionReports::countReports();
      $this->assign('sdcounts', $counts);
      $collectionReports = CRM_Smartdebit_CollectionReports::getReports([]);
      $this->assign('collectionreports', $collectionReports);
    } catch (Exception $e) {
      // Do nothing here. Api will throw exception if API URL is not configured, which it won't be if
      // Smartdebit payment processor has not been setup yet.
      $this->assign('apiStatus', 'No Smartdebit payment processors are configured yet!');
    }
  }

}
