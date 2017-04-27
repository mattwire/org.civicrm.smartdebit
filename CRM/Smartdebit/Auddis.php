<?php

class CRM_Smartdebit_Auddis
{
  /*
   * Notification of failed debits and cancelled or amended DDIs are made available via Automated Direct Debit
   * Instruction Service (AUDDIS), Automated Return of Unpaid Direct Debit (ARUDD) files and Automated Direct Debit
   * Amendment and Cancellation (ADDACS) files. Notification of any claims relating to disputed Debits are made via
   * Direct Debit Indemnity Claim Advice (DDICA) reports.
   */

  private $_auddisList = NULL;
  private $_aruddList = NULL;
  private $_aruddDatesList = NULL;
  private $_auddisDatesList = NULL;

  function getAuddisList() {
    return $this->_auddisList;
  }

  function getAruddList() {
    return $this->_aruddList;
  }

  function getAuddisDatesList() {
    return $this->_auddisDatesList;
  }

  function getAruddDatesList()
  {
    return $this->_aruddDatesList;
  }


  /**
   * Get List of AUDDIS files from Smartdebit for the past month.
   * If dateOfCollection is not specified it defaults to today.
   * @param null $dateOfCollection
   * @return bool|mixed
   */
  function getSmartdebitAuddisList($dateOfCollectionStart = null, $dateOfCollectionEnd = null)
  {
    if (!isset($dateOfCollectionEnd)) {
      $endDate = new DateTime();
      $dateOfCollectionEnd = $endDate->format('Y-m-d'); // Today
    }
    if (!isset($dateOfCollectionStart)) {
      $dateOfCollectionStart = date('Y-m-d', strtotime($dateOfCollectionEnd . CRM_Smartdebit_Sync::COLLECTION_REPORT_AGE));
    }
    $userDetails = CRM_Smartdebit_Auddis::getSmartdebitUserDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    // Send payment POST to the target URL

    $urlAuddis = CRM_Smartdebit_Base::getApiUrl('/api/auddis/list',
      "query[service_user][pslid]=$pslid&query[from_date]=$dateOfCollectionStart&query[till_date]=$dateOfCollectionEnd");
    $responseAuddis = CRM_Smartdebit_Base::requestPost($urlAuddis, '', $username, $password, '');
    // Take action based upon the response status
    switch (strtoupper($responseAuddis['Status'])) {
      case 'OK':
        $this->_auddisList = $responseAuddis;
        return true;
      default:
        return false;
    }
  }

  /**
   * Get AUDDIS file from Smart Debit. $uri is retrieved using getSmartdebitAuddisList
   * @param null $uri
   * @return array
   */
  static function getSmartdebitAuddisFile($fileId)
  {
    $userDetails = CRM_Smartdebit_Auddis::getSmartdebitUserDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    if (empty($fileId)) {
      CRM_Core_Error::debug_log_message('Smartdebit getSmartdebitAuddisFile: Must specify file ID!');
      return false;
    }
    $url = CRM_Smartdebit_Base::getApiUrl("/api/auddis/$fileId",
      "query[service_user][pslid]=$pslid");
    $responseAuddis = CRM_Smartdebit_Base::requestPost($url, '', $username, $password, '');
    $scrambled = str_replace(" ", "+", $responseAuddis['file']);
    $outputafterencode = base64_decode($scrambled);
    $auddisArray = json_decode(json_encode((array)simplexml_load_string($outputafterencode)), 1);
    $result = array();

    if ($auddisArray['Data']['MessagingAdvices']['MessagingAdvice']['@attributes']) {
      $result[0] = $auddisArray['Data']['MessagingAdvices']['MessagingAdvice']['@attributes'];
    } else {
      foreach ($auddisArray['Data']['MessagingAdvices']['MessagingAdvice'] as $key => $value) {
        $result[$key] = $value['@attributes'];
      }
    }
    if (isset($auddisArray['Data']['MessagingAdvices']['Header']['@attributes']['report-generation-date'])) {
      $result['auddis_date'] = $auddisArray['Data']['MessagingAdvices']['Header']['@attributes']['report-generation-date'];
    }
    return $result;
  }

  /**
   * Get List of ARUDD files from Smartdebit for the past month.
   * If dateOfCollection is not specified it defaults to today.
   * @param null $dateOfCollection
   * @return bool|mixed
   */
  function getSmartdebitAruddList($dateOfCollectionStart = null, $dateOfCollectionEnd = null)
  {
    if (!isset($dateOfCollectionEnd)) {
      $endDate = new DateTime();
      $dateOfCollectionEnd = $endDate->format('Y-m-d'); // Today
    }
    if (!isset($dateOfCollectionStart)) {
      $dateOfCollectionStart = date('Y-m-d', strtotime($dateOfCollectionEnd . CRM_Smartdebit_Sync::COLLECTION_REPORT_AGE));
    }
    $userDetails = CRM_Smartdebit_Auddis::getSmartdebitUserDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    // Send payment POST to the target URL
    $urlArudd = CRM_Smartdebit_Base::getApiUrl('/api/arudd/list', "query[service_user][pslid]=$pslid&query[from_date]=$dateOfCollectionStart&query[till_date]=$dateOfCollectionEnd");
    $responseArudd = CRM_Smartdebit_Base::requestPost($urlArudd, '', $username, $password, '');

    // Take action based upon the response status
    switch (strtoupper($responseArudd["Status"])) {
      case 'OK':
        $aruddArray = array();
        // Cater for a single response
        if (isset($responseArudd['arudd'])) {
          $aruddArray = $responseArudd['arudd'];
        }
        $this->_aruddList = $aruddArray;
        return true;
      default:
        return false;
    }
  }

  /**
   * Get ARUDD file from Smart Debit. $uri is retrieved using getSmartdebitAuddisList
   * @param null $uri
   * @return array
   */
  static function getSmartdebitAruddFile($fileId)
  {
    $userDetails = CRM_Smartdebit_Auddis::getSmartdebitUserDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    if (empty($fileId)) {
      CRM_Core_Error::debug_log_message('Smartdebit getSmartdebitAruddFile: Must specify file ID!');
      return false;
    }

    $url = CRM_Smartdebit_Base::getApiUrl("/api/arudd/$fileId",
      "query[service_user][pslid]=$pslid");
    $responseArudd = CRM_Smartdebit_Base::requestPost($url, '', $username, $password, '');
    $scrambled = str_replace(" ", "+", $responseArudd['file']);
    $outputafterencode = base64_decode($scrambled);
    $aruddArray = json_decode(json_encode((array)simplexml_load_string($outputafterencode)), 1);
    $result = array();

    if (isset($aruddArray['Data']['ARUDD']['Advice']['OriginatingAccountRecords']['OriginatingAccountRecord']['ReturnedDebitItem']['@attributes'])) {
      // Got a single result
      // FIXME: Check that this is correct (ie. results not in array at ReturnedDebitItem if single
      $result[0] = $aruddArray['Data']['ARUDD']['Advice']['OriginatingAccountRecords']['OriginatingAccountRecord']['ReturnedDebitItem']['@attributes'];
    } else {
      foreach ($aruddArray['Data']['ARUDD']['Advice']['OriginatingAccountRecords']['OriginatingAccountRecord']['ReturnedDebitItem'] as $key => $value) {
        $result[$key] = $value['@attributes'];
      }
    }
    $result['arudd_date'] = $aruddArray['Data']['ARUDD']['Header']['@attributes']['currentProcessingDate'];
    return $result;
  }

  /**
   * Build a list of auddis dates for processing
   * Call getAuddisDatesList for actual list
   *
   * @return bool True if successful, false otherwise
   */
  function getAuddisDates()
  {
    $auddisDates = array();
    $processedAuddisDates = array();

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
      return true;
    }
    return false;
  }

  /**
   * Parse returned data from smartdebit for an auddis record and add to processed/unprocessed array
   * @param $auddis
   * @param $processed
   * @param $unprocessed
   * @return array
   */
  static function parseAuddisFromSmartDebit($auddis, $processed, $unprocessed) {
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
    return array($processed, $unprocessed);
  }

  /**
   * Build a list of arudd dates for processing
   * Call getAruddDatesList for actual list
   *
   * @return bool True if successful, false otherwise
   */
  function getAruddDates() {
    $aruddDates = array();
    $processedAruddDates = array();

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
      return true;
    }
    return false;
  }

  /**
   * Parse returned data from smartdebit for an arudd record and add to processed/unprocessed array
   * @param $auddis
   * @param $processed
   * @param $unprocessed
   * @return array
   */
  static function parseAruddFromSmartDebit($arudd, $processed, $unprocessed) {
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
    return array($processed, $unprocessed);
  }

  /**
   * Gets an array of Auddis IDs for processing
   *
   * @return array
   */
  function getAuddisIdsForProcessing($auddisDates = NULL) {
    $auddisIDs = array();

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
   * @return array
   */
  function getAruddIdsForProcessing($aruddDates = NULL) {
    $aruddIDs = array();

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
   * This gets all the collection reports for the time period ending $dateOfCollection and starting COLLECTION_REPORT_AGE days/months earlier
   * @param $dateOfCollection
   * @return array
   */
  static function getSmartdebitCollectionReports($dateOfCollection) {
    if( empty($dateOfCollection)){
      $collections['error'] = 'Please specify a collection date';
      return $collections;
    }
    $collections = array();

    // Empty the collection reports table
    $emptySql = "TRUNCATE TABLE veda_smartdebit_collectionreports";
    CRM_Core_DAO::executeQuery($emptySql);

    // Get a collection report for every day of the month
    $dateEnd = new DateTime($dateOfCollection);
    $dateStart = clone $dateEnd;
    $dateStart->modify(CRM_Smartdebit_Sync::COLLECTION_REPORT_AGE);
    $dateCurrent = clone $dateEnd;

    // Iterate back one day at a time requesting reports
    while ($dateCurrent > $dateStart) {
      $newCollections = CRM_Smartdebit_Auddis::getSmartdebitCollectionReport($dateCurrent->format('Y-m-d'));
      if (!isset($newCollections['error'])) {
        $collections = array_merge($collections, $newCollections);
      }
      $dateCurrent->modify('-1 day');
    }
    return $collections;
  }
  /**
   * Retrieve Collection Report from Smart Debit
   * @param $dateOfCollection
   * @return array|bool
   */
  static function getSmartdebitCollectionReport( $dateOfCollection ) {
    if( empty($dateOfCollection)){
      CRM_Core_Session::setStatus(ts('Please select the collection date'), ts('Smart Debit'), 'error');
      return false;
    }

    $userDetails = self::getSmartdebitUserDetails();
    $username    = CRM_Utils_Array::value('user_name', $userDetails);
    $password    = CRM_Utils_Array::value('password', $userDetails);
    $pslid       = CRM_Utils_Array::value('signature', $userDetails);

    $collections = array();
    $url = CRM_Smartdebit_Base::getApiUrl('/api/get_successful_collection_report', "query[service_user][pslid]=$pslid&query[collection_date]=$dateOfCollection");
    $response    = CRM_Smartdebit_Base::requestPost($url, '', $username, $password, '');

    // Take action based upon the response status
    switch ( strtoupper( $response["Status"] ) ) {
      case 'OK':
        if (!isset($response['Successes']) || !isset($response['Rejects'])) {
          $collections['error'] = $response['Summary'];
          return $collections;
        }
        // Cater for a single response
        if (isset($response['Successes']['Success']['@attributes'])) {
          $collections[] = $response['Successes']['Success']['@attributes'];
        } else {
          foreach ($response['Successes']['Success'] as $key => $value) {
            $collections[] = $value['@attributes'];
          }
        }
        return $collections;
      case 'INVALID':
        $url = CRM_Utils_System::url('civicrm/smartdebit/syncsd', 'reset=1'); // DataSource Form
        CRM_Core_Session::setStatus($response['error'], ts('Smart Debit'), 'error');
        CRM_Utils_System::redirect($url);
        $collections['error'] = $response['error'];
        return $collections;
      default:
        $collections['error'] = $response['error'];
        return $collections;
    }
  }

  /**
   * Save collection report (getSmartdebitCollectionReport) to database
   * @param $collections
   */
  static function saveSmartdebitCollectionReport($collections) {
    $tableName = "veda_smartdebit_collectionreports";
    if(!empty($collections)){
      foreach ($collections as $key => $value) {
        $resultCollection = array(
          'transaction_id' => $value['reference_number'],
          'contact'        => $value['account_name'],
          'contact_id'     => $value['customer_id'],
          'amount'         => $value['amount'],
          'receive_date'   => !empty($value['debit_date']) ? date('YmdHis', strtotime(str_replace('/', '-', $value['debit_date']))) : NULL,
        );
        $insertValue[] = " ( \"". implode('", "', $resultCollection) . "\" )";
      }
      $sql = " INSERT INTO `{$tableName}`
              (`transaction_id`, `contact`, `contact_id`, `amount`, `receive_date`)
              VALUES ".implode(', ', $insertValue)."
              ";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Remove all collection report date from veda_smartdebit_collectionreports that is older than one month
   */
  static function removeOldSmartdebitCollectionReports() {
    $date = new DateTime();
    $date->modify(CRM_Smartdebit_Sync::COLLECTION_REPORT_AGE);
    $dateString = $date->format('Ymd') . '000000';
    $query = "DELETE FROM `veda_smartdebit_collectionreports` WHERE receive_date < %1";
    $params = array(1 => array($dateString, 'String'));
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Get Smart Debit User Details
   * @return array|bool
   */
  static function getSmartdebitUserDetails(){
    $paymentProcessorType   = CRM_Core_PseudoConstant::paymentProcessorType(false, null, 'name');
    $paymentProcessorTypeId = CRM_Utils_Array::key('Smart_Debit', $paymentProcessorType);
    $domainID               = CRM_Core_Config::domainID();

    if(empty($paymentProcessorTypeId)) {
      CRM_Core_Session::setStatus(ts('Make sure Payment Processor Type (Smart Debit) is set in Payment Processor setting'), Error, 'error');
      return FALSE;
    }

    $sql  = " SELECT user_name ";
    $sql .= " ,      password ";
    $sql .= " ,      signature ";
    $sql .= " ,      billing_mode ";
    $sql .= " ,      payment_type ";
    $sql .= " ,      url_api ";
    $sql .= " ,      id ";
    $sql .= " FROM civicrm_payment_processor ";
    $sql .= " WHERE payment_processor_type_id = %1 ";
    $sql .= " AND is_test= %2 AND domain_id = %3";

    $params = array( 1 => array( $paymentProcessorTypeId, 'Integer' )
    , 2 => array( '0', 'Int' )
    , 3 => array( $domainID, 'Int' )
    );

    $dao    = CRM_Core_DAO::executeQuery( $sql, $params);
    $result = array();
    if ($dao->fetch()) {
      if(empty($dao->user_name) || empty($dao->password) || empty($dao->signature)) {
        CRM_Core_Session::setStatus(ts('Smart Debit API User Details Missing, Please check the Smart Debit Payment Processor is configured Properly'), Error, 'error');
        return FALSE;
      }
      $result   = array(
        'user_name'	  => $dao->user_name,
        'password'	  => $dao->password,
        'signature'	  => $dao->signature,
        'billing_mode'    => $dao->billing_mode,
        'payment_type'    => $dao->payment_type,
        'url_api'	  => $dao->url_api,
        'id'		  => $dao->id,
      );
    }
    return $result;
  }

  /**
   * Return true if auddis/arudd record has been processed, false otherwise
   * @param $auddisId
   * @return bool
   */
  static function isAuddisRecordProcessed($auddisId) {
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
   * @param $auddisId
   * @param bool $processed
   * @return bool
   */
  static function setAuddisRecordProcessed($auddisId, $processed = TRUE) {
    if (empty($auddisId)) {
      return false;
    }
    else {
      $sql = "UPDATE `veda_smartdebit_auddis` SET processed={$processed} WHERE id={$auddisId}";
      $auddisRecord = CRM_Core_DAO::executeQuery($sql);
      return TRUE;
    }
    return false;
  }

  /**
   * Get an Auddis or Arudd record from the DB
   *
   * @param $auddisId
   * @return bool
   */
  static function getAuddisRecord($auddisId) {
    if (empty($auddisId)) {
      return false;
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
    return false;
  }

  static function addAuddisRecord($auddis)
  {
    if (empty($auddis['report_generation_date']) || empty($auddis['auddis_id'])) {
      return false;
    } else {
      if (!CRM_Smartdebit_Auddis::getAuddisRecord($auddis['auddis_id'])) {
        // Not found so add it
        $sql = "
INSERT INTO `veda_smartdebit_auddis`(`id`,`date`,`type`,`processed`) 
VALUES (%1,%2,%3,%4)";
        $params = array(
          1 => array((integer)$auddis['auddis_id'], 'Integer'),
          2 => array(CRM_Utils_Date::processDate($auddis['report_generation_date'], NULL, FALSE, 'Ymd'), 'Date'),
          3 => array(0, 'Integer'),
          4 => array(0, 'Boolean'),
        );
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        return true;
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
  }

  static function addAruddRecord($arudd)
  {
    if (empty($arudd['current_processing_date']) || empty($arudd['arudd_id'])) {
      return false;
    } else {
      if (!CRM_Smartdebit_Auddis::getAuddisRecord($arudd['arudd_id'])) {
        // Not found so add it
        $sql = "
INSERT INTO `veda_smartdebit_auddis`(`id`,`date`,`type`,`processed`) 
VALUES (%1,%2,%3,%4)";
        $params = array(
          1 => array((integer)$arudd['arudd_id'], 'Integer'),
          2 => array(CRM_Utils_Date::processDate($arudd['current_processing_date'], NULL, FALSE, 'Ymd'), 'Date'),
          3 => array(1, 'Integer'),
          4 => array(0, 'Boolean'),
        );
        $dao = CRM_Core_DAO::executeQuery($sql, $params);
        return true;
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
  }
}
