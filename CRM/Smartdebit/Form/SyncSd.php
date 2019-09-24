<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_Form_SyncSd
 *
 * This form retrieves a list of AUDDIS / ARUDD dates and displays them for selection.
 * This is the second page in the import process (starting at civicrm/smartdebit/syncsd)
 *
 * Path: civicrm/smartdebit/syncsd/select
 */
class CRM_Smartdebit_Form_SyncSd extends CRM_Core_Form {
  private $_auddisArray = NULL;
  private $_aruddArray = NULL;
  private $_auddisProcessor;

  /**
   * Retrieves a list of available AUDDIS / ARUDD dates and displays them for selection
   */
  public function buildQuickForm() {
    // Get details of latest collection report
    $this->assign('collectionreports', CRM_Smartdebit_CollectionReports::getReports(['limit' => 1]));

    // Get date of collection (or set to today if not set)
    $dateOfCollectionEnd = CRM_Utils_Request::retrieve('collection_date', 'String', $this, FALSE);
    if (empty($dateOfCollectionEnd)) {
      $now = new DateTime();
      $dateOfCollectionEnd = date('Y-m-d', (string)$now->getTimestamp()); // Today
    }
    $dateOfCollectionStart = date('Y-m-d', strtotime($dateOfCollectionEnd . CRM_Smartdebit_Settings::getValue('cr_cache')));
    $this->assign('dateOfCollectionEnd', $dateOfCollectionEnd);
    $this->assign('dateOfCollectionStart', $dateOfCollectionStart);

    // Get list of Auddis/Arudd
    $this->_auddisProcessor = new CRM_Smartdebit_Auddis();
    if ($this->_auddisProcessor->getSmartdebitAuddisList($dateOfCollectionStart, $dateOfCollectionEnd)) {
      $this->_auddisArray = $this->_auddisProcessor->getAuddisList();
    }
    if ($this->_auddisProcessor->getSmartdebitAruddList($dateOfCollectionStart, $dateOfCollectionEnd)) {
      $this->_aruddArray = $this->_auddisProcessor->getAruddList();
    }

    // get auddis/arudd dates for processing
    if ($this->_auddisProcessor->getAuddisDates()) {
      $auddisDates = $this->_auddisProcessor->getAuddisDatesList();
    }
    else {
      $auddisDates = [];
    }

    if ($this->_auddisProcessor->getAruddDates()) {
      $aruddDates = $this->_auddisProcessor->getAruddDatesList();
    }
    else {
      $aruddDates = [];
    }

    if (count($auddisDates) <= 10) {
      // setting minimum height to 2 since widget looks strange when size (height) is 1
      $groupSize = max(count($auddisDates), 2);
    }
    else {
      $groupSize = 10;
    }

    if (count($aruddDates) <= 10) {
      // setting minimum height to 2 since widget looks strange when size (height) is 1
      $groupSizeArudd = max(count($aruddDates), 2);
    }
    else {
      $groupSizeArudd = 10;
    }

    $this->addElement('advmultiselect', 'includeAuddisDate',
      ts('Include Auddis Date(s)') . ' ',
      $auddisDates,
      [
        'size' => $groupSize,
        'style' => 'width:auto; min-width:240px;',
        'class' => 'advmultiselect',
      ]
    );

    $this->addElement('advmultiselect', 'includeAruddDate',
      ts('Include Arudd Date(s)') . ' ',
      $aruddDates,
      [
        'size' => $groupSizeArudd,
        'style' => 'width:auto; min-width:240px;',
        'class' => 'advmultiselect',
      ]
    );

    $this->assign('groupCount', count($auddisDates));
    $this->assign('groupCountArudd', count($aruddDates));

    $auddisDatesArray = ['' => ts('- select -')];
    $aruddDatesArray = ['' => ts('- select -')];
    if (!empty($auddisDates)) {
      $auddisDatesArray = $auddisDatesArray + $auddisDates;
    }
    if (!empty($aruddDates)) {
      $aruddDatesArray = $aruddDatesArray + $aruddDates;
    }
    $this->addElement('select', 'auddis_date', ts('Auddis Date'), $auddisDatesArray);
    $this->addElement('select', 'arudd_date', ts('Arudd Date'), $aruddDatesArray);
    $this->assign('dateOfCollectionStart', $dateOfCollectionStart);
    $this->assign('dateOfCollectionEnd', $dateOfCollectionEnd);

    $redirectUrlBack = CRM_Utils_System::url('civicrm/smartdebit/syncsd', 'reset=1');

    $this->addButtons([
        [
          'type' => 'back',
          'js' => ['onclick' => "location.href='{$redirectUrlBack}'; return false;"],
          'name' => ts('Back'),
        ],
        [
          'type' => 'submit',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ],
      ]
    );
    CRM_Utils_System::setTitle(ts('Synchronise CiviCRM with Smart Debit'));
    parent::buildQuickForm();
  }

  function postProcess() {
    $params = $this->controller->exportValues();
    $auddisDates = $params['includeAuddisDate'];
    $aruddDates = $params['includeAruddDate'];

    // Get IDs for processing
    $auddisIDs = $this->_auddisProcessor->getAuddisIDsForProcessing($auddisDates);
    $aruddIDs = $this->_auddisProcessor->getAruddIDsForProcessing($aruddDates);

    // Make the query string to send in the url for the next page
    $queryParams = [];
    if (isset($auddisIDs)) {
      $queryParams['auddisID'] = urlencode(implode(',',$auddisIDs));
    }
    if (isset($aruddIDs)) {
      $queryParams['aruddID'] = urlencode(implode(',',$aruddIDs));
    }
    $queryParams['reset'] = 1;

    CRM_Utils_System::redirect(CRM_Utils_System::url( 'civicrm/smartdebit/syncsd/auddis', $queryParams));
    parent::postProcess();
  }
}
