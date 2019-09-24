<?php
/**
 * https://civicrm.org/licensing
 */

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

    $latestReport = CRM_Smartdebit_CollectionReports::getReports(['limit' => 1]);
    if (count($latestReport) > 0) {
      $this->assign('latestReportDate', CRM_Utils_Array::first($latestReport)['collection_date']);
    }

    $this->add('checkbox', 'retrieve_collectionreport', ts('Retrieve latest daily collection report from SmartDebit?'));
    $this->addButtons([
        [
          'type' => 'submit',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ],
      ]
    );
    CRM_Utils_System::setTitle(ts('Synchronise CiviCRM with Smart Debit'));
  }

  public function setDefaultValues() {
    $defaults['retrieve_collectionreport'] = TRUE;
    return $defaults;
  }

  /**
   * Process the collection report
   *
   * @throws \Exception
   */
  public function postProcess() {
    $params = $this->controller->exportValues();
    if (!empty($params['retrieve_collectionreport'])) {
      // If no collection date specified we retrieve the daily collection report (just like scheduled sync)
      CRM_Smartdebit_CollectionReports::retrieveDaily();
    }
    $queryParams = [];
    $queryParams['reset'] = 1;
    $url = CRM_Utils_System::url('civicrm/smartdebit/syncsd/select', $queryParams); // SyncSD form
    CRM_Utils_System::redirect($url);
  }
}

