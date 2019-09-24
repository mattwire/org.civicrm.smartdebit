<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_Auddis
 *
 * Notification of failed debits and cancelled or amended DDIs are made available via Automated Direct Debit
 * Instruction Service (AUDDIS), Automated Return of Unpaid Direct Debit (ARUDD) files and Automated Direct Debit
 * Amendment and Cancellation (ADDACS) files. Notification of any claims relating to disputed Debits are made via
 * Direct Debit Indemnity Claim Advice (DDICA) reports.
 */
class CRM_Smartdebit_Auddis {

  private $_auddisList = NULL;
  private $_aruddList = NULL;
  private $_aruddDatesList = NULL;
  private $_auddisDatesList = NULL;

  public function getAuddisList() {
    return $this->_auddisList;
  }

  public function getAruddList() {
    return $this->_aruddList;
  }

  public function getAuddisDatesList() {
    return $this->_auddisDatesList;
  }

  public function getAruddDatesList() {
    return $this->_aruddDatesList;
  }

  /**
   * Get List of AUDDIS files from Smartdebit for the past month.
   * If dateOfCollection is not specified it defaults to today.
   * FIXME: Move to CRM_Smartdebit_Api
   *
   * @param string $dateOfCollectionStart
   * @param string $dateOfCollectionEnd
   *
   * @return bool
   * @throws \Exception
   */
  public function getSmartdebitAuddisList($dateOfCollectionStart = NULL, $dateOfCollectionEnd = NULL) {
    if (!isset($dateOfCollectionEnd)) {
      $endDate = new DateTime();
      $dateOfCollectionEnd = $endDate->format('Y-m-d'); // Today
    }
    if (!isset($dateOfCollectionStart)) {
      $dateOfCollectionStart = date('Y-m-d', strtotime($dateOfCollectionEnd . CRM_Smartdebit_Settings::getValue('cr_cache')));
    }
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();

    // Send payment POST to the target URL
    $urlAuddis = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/auddis/list',
      "query[service_user][pslid]=" . $userDetails['signature'] . "&query[from_date]=$dateOfCollectionStart&query[till_date]=$dateOfCollectionEnd");
    $responseAuddis = CRM_Smartdebit_Api::requestPost($urlAuddis, NULL, $userDetails['user_name'], $userDetails['password']);
    // Take action based upon the response status
    if ($responseAuddis['success']) {
      $this->_auddisList = $responseAuddis;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get List of ARUDD files from Smartdebit for the past month.
   * If dateOfCollection is not specified it defaults to today.
   *
   * @param string $dateOfCollectionStart
   * @param string $dateOfCollectionEnd
   *
   * @return bool
   * @throws \Exception
   */
  public function getSmartdebitAruddList($dateOfCollectionStart = NULL, $dateOfCollectionEnd = NULL) {
    if (!isset($dateOfCollectionEnd)) {
      $endDate = new DateTime();
      $dateOfCollectionEnd = $endDate->format('Y-m-d'); // Today
    }
    if (!isset($dateOfCollectionStart)) {
      $dateOfCollectionStart = date('Y-m-d', strtotime($dateOfCollectionEnd . CRM_Smartdebit_Settings::getValue('cr_cache')));
    }
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();

    // Send payment POST to the target URL
    $urlArudd = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/arudd/list', "query[service_user][pslid]=" . $userDetails['signature'] . "&query[from_date]=$dateOfCollectionStart&query[till_date]=$dateOfCollectionEnd");
    $responseArudd = CRM_Smartdebit_Api::requestPost($urlArudd, NULL, $userDetails['user_name'], $userDetails['password']);

    // Take action based upon the response status
    if ($responseArudd['success']) {
      $aruddArray = [];
      // Cater for a single response
      if (isset($responseArudd['arudd'])) {
        $aruddArray = $responseArudd['arudd'];
      }
      $this->_aruddList = $aruddArray;
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Build a list of auddis dates for processing
   * Call getAuddisDatesList for actual list
   *
   * @return bool True if successful, FALSE otherwise
   */
  public function getAuddisDates()
  {
    $auddisDates = [];
    $processedAuddisDates = [];

    if (($this->_auddisList) && isset($this->_auddisList['Status']) && ($this->_auddisList['Status'] == 'OK')) {
      // Get the auddis Dates from the Auddis Files
      if (isset($this->_auddisList['@attributes']['results']) && ($this->_auddisList['@attributes']['results'] > 1)) {
        // Multiple results returned
        foreach ($this->_auddisList['auddis'] as $key => $auddis) {
          list($processedAuddisDates, $auddisDates) = CRM_Smartdebit_Auddis::parseAuddisFromSmartDebit($auddis, $processedAuddisDates, $auddisDates);
        }
      } else {
        // Only one result returned (not in an array)
        if (isset($this->_auddisList['auddis']['report_generation_date'])) {
          $auddis = $this->_auddisList['auddis'];
          list($processedAuddisDates, $auddisDates) = CRM_Smartdebit_Auddis::parseAuddisFromSmartDebit($auddis, $processedAuddisDates, $auddisDates);
        }
      }
    }

    // Keys/Values => dates (eg. ['2017-04-04'] => '2017-04-04'
    if (!empty($auddisDates)) {
      $auddisDates = array_combine($auddisDates, $auddisDates);
      $this->_auddisDatesList = $auddisDates;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Parse returned data from smartdebit for an auddis record and add to processed/unprocessed array
   * @param array $auddis
   * @param array $processed
   * @param array $unprocessed
   *
   * @return array
   */
  private static function parseAuddisFromSmartDebit($auddis, $processed, $unprocessed) {
    if (isset($auddis['report_generation_date'])) {
      CRM_Smartdebit_Auddis::addAuddisRecord($auddis);
      $auddisDate = date('Y-m-d', strtotime($auddis['report_generation_date']));
      if (CRM_Smartdebit_Auddis::isAuddisRecordProcessed($auddis['auddis_id'])) {
        $processed[] = $auddisDate;
      }
      else {
        $unprocessed[] = $auddisDate;
      }
    }
    return [$processed, $unprocessed];
  }

  /**
   * Build a list of arudd dates for processing
   * Call getAruddDatesList for actual list
   *
   * @return bool True if successful, FALSE otherwise
   */
  public function getAruddDates() {
    $aruddDates = [];
    $processedAruddDates = [];

    // Get the arudd Dates from the Arudd Files
    if ($this->_aruddList) {
      if (isset($this->_aruddList[0]['@attributes'])) {
        // Multiple results returned
        foreach ($this->_aruddList as $key => $arudd) {
          list($processedAruddDates, $aruddDates) = CRM_Smartdebit_Auddis::parseAruddFromSmartDebit($arudd, $processedAruddDates, $aruddDates);
        }
      } else {
        // Only one result returned
        $arudd = $this->_aruddList;
        list($processedAruddDates, $aruddDates) = CRM_Smartdebit_Auddis::parseAruddFromSmartDebit($arudd, $processedAruddDates, $aruddDates);
      }
    }

    // Keys/Values => dates (eg. ['2017-04-04'] => '2017-04-04'
    if (!empty($aruddDates)) {
      $aruddDates = array_combine($aruddDates, $aruddDates);
      $this->_aruddDatesList = $aruddDates;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Parse returned data from smartdebit for an arudd record and add to processed/unprocessed array
   *
   * @param array $auddis
   * @param array $processed
   * @param array $unprocessed
   *
   * @return array
   */
  private static function parseAruddFromSmartDebit($arudd, $processed, $unprocessed) {
    if (isset($arudd['current_processing_date'])) {
      CRM_Smartdebit_Auddis::addAruddRecord($arudd);
      $aruddDate = date('Y-m-d', strtotime($arudd['current_processing_date']));
      if (self::isAuddisRecordProcessed($arudd['arudd_id'])) {
        $processed[] = $aruddDate;
      }
      else {
        $unprocessed[] = $aruddDate;
      }
    }
    return [$processed, $unprocessed];
  }

  /**
   * Gets an array of Auddis IDs for processing
   *
   * @param array $auddisDates
   *
   * @return array
   */
  public function getAuddisIdsForProcessing($auddisDates = NULL) {
    $auddisIDs = [];

    if (!isset($auddisDates)) {
      $auddisDates = $this->_auddisDatesList;
    }
    foreach ($auddisDates as $date) {
      // Find auddis ID
      if (isset($this->_auddisList['@attributes']['results']) && ($this->_auddisList['@attributes']['results'] > 1)) {
        foreach ($this->_auddisList['auddis'] as $key => $auddis) {
          if (isset($auddis['report_generation_date'])) {
            if ($date == date('Y-m-d', strtotime($auddis['report_generation_date']))) {
              $auddisIDs[] = $auddis['auddis_id'];
              break;
            }
          }
        }
      } else {
        // Handle single result
        $auddis = $this->_auddisList['auddis'];
        if (isset($auddis['report_generation_date'])) {
          if ($date == date('Y-m-d', strtotime($auddis['report_generation_date']))) {
            $auddisIDs[] = $auddis['auddis_id'];
            break;
          }
        }
      }
    }
    return $auddisIDs;
  }

  /**
   * Gets an array of Arudd IDs for processing
   *
   * @param array $aruddDates
   *
   * @return array
   */
  public function getAruddIdsForProcessing($aruddDates = NULL) {
    $aruddIDs = [];

    if (!isset($aruddDates)) {
      $aruddDates = $this->_aruddDatesList;
    }
    foreach ($aruddDates as $date) {
      // Find arudd ID
      if (isset($this->_aruddList[0]['@attributes'])) {
        foreach ($this->_aruddList as $key => $arudd) {
          if (isset($arudd['current_processing_date'])) {
            if ($date == date('Y-m-d', strtotime($arudd['current_processing_date']))) {
              $aruddIDs[] = $arudd['arudd_id'];
              break;
            }
          }
        }
      }
      else {
        // Handle single result
        $arudd = $this->_aruddList;
        if (isset($arudd['current_processing_date'])) {
          if ($date == date('Y-m-d', strtotime($arudd['current_processing_date']))) {
            $aruddIDs[] = $arudd['arudd_id'];
            break;
          }
        }
      }
    }
    return $aruddIDs;
  }

  /**
   * Return TRUE if auddis/arudd record has been processed, FALSE otherwise
   *
   * @param string $auddisId
   *
   * @return bool
   */
  private static function isAuddisRecordProcessed($auddisId) {
    if (empty($auddisId)) {
      return FALSE;
    }

    $auddis = CRM_Smartdebit_Auddis::getAuddisRecord($auddisId);
    if (empty($auddis['processed'])) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Set state of auddis/arudd record to processed
   *
   * @param string $auddisId
   * @param bool $processed
   *
   * @return bool
   */
  public static function setAuddisRecordProcessed($auddisId, $processed = TRUE) {
    if (empty($auddisId)) {
      return FALSE;
    }
    else {
      $sql = "UPDATE `veda_smartdebit_auddis` SET processed={$processed} WHERE id={$auddisId}";
      CRM_Core_DAO::executeQuery($sql);
      return TRUE;
    }
  }

  /**
   * Get an Auddis or Arudd record from the DB
   *
   * @param string $auddisId
   *
   * @return bool
   */
  private static function getAuddisRecord($auddisId) {
    if (empty($auddisId)) {
      return FALSE;
    }
    else {
      $sql = "SELECT id, date, type, processed FROM veda_smartdebit_auddis WHERE id={$auddisId}";
      $auddisRecord = CRM_Core_DAO::executeQuery($sql);
      if ($auddisRecord->fetch()) {
        $auddis['id'] = $auddisRecord->id;
        $auddis['date'] = $auddisRecord->date;
        $auddis['type'] = $auddisRecord->type;
        $auddis['processed'] = $auddisRecord->processed;
        return $auddis;
      }
    }
    return FALSE;
  }

  /**
   * @param array $auddis
   *
   * @return bool
   */
  private static function addAuddisRecord($auddis)
  {
    if (empty($auddis['report_generation_date']) || empty($auddis['auddis_id'])) {
      return FALSE;
    } else {
      if (!CRM_Smartdebit_Auddis::getAuddisRecord($auddis['auddis_id'])) {
        // Not found so add it
        $sql = "
INSERT INTO `veda_smartdebit_auddis`(`id`,`date`,`type`,`processed`) 
VALUES (%1,%2,%3,%4)";
        $params = [
          1 => [(integer)$auddis['auddis_id'], 'Integer'],
          2 => [CRM_Utils_Date::processDate($auddis['report_generation_date'], NULL, FALSE, 'Ymd'), 'Date'],
          3 => [0, 'Integer'],
          4 => [0, 'Boolean'],
        ];
        CRM_Core_DAO::executeQuery($sql, $params);
        return TRUE;
      }

      /* Auddis Record
      Array
      (
        [@attributes] => Array
        (
          [summary] => true
          [uri] => https://secure.ddprocessing.co.uk/api/auddis/?query[service_user][pslid]=occmedi&query[from_date]=2017-01-26&query[till_date]=2017-04-26629096
          )

      [file_name] => _DOWNLOADED_BacsReports_831113_20170330_021234_7002_AUDDIS Bank Returned DDI (7002)_17513.xml
      [report_generation_date] => 2017-03-29T00:00:00Z
      [advices] => 1
      [auddis_id] => 629096
      )*/
    }
    return FALSE;
  }

  /**
   * @param array $arudd
   *
   * @return bool
   */
  public static function addAruddRecord($arudd) {
    if (empty($arudd['current_processing_date']) || empty($arudd['arudd_id'])) {
      return FALSE;
    } else {
      if (!CRM_Smartdebit_Auddis::getAuddisRecord($arudd['arudd_id'])) {
        // Not found so add it
        $sql = "
INSERT INTO `veda_smartdebit_auddis`(`id`,`date`,`type`,`processed`) 
VALUES (%1,%2,%3,%4)";
        $params = [
          1 => [(integer)$arudd['arudd_id'], 'Integer'],
          2 => [CRM_Utils_Date::processDate($arudd['current_processing_date'], NULL, FALSE, 'Ymd'), 'Date'],
          3 => [1, 'Integer'],
          4 => [0, 'Boolean'],
        ];
        CRM_Core_DAO::executeQuery($sql, $params);
        return TRUE;
      }

      /*
       * Array
        (
        [@attributes] => Array
            (
                [summary] => true
                [uri] => https://secure.ddprocessing.co.uk/api/arudd/?query[service_user][pslid]=occmedi&query[from_date]=2017-01-26&query[till_date]=2017-04-26115178
            )

        [file_name] => _DOWNLOADED_BacsReports_831113_20170404_054946_1016_ARUDD Report (1016)_45161.xml
        [current_processing_date] => 2017-04-04T00:00:00Z
        [report_type] => REFT1019
        [advice_number] => 999
        [arudd_id] => 115178
        [failed_debits] => 9
        [failed_debits_value] => 167800
        )
       */
    }
    return FALSE;
  }

}
