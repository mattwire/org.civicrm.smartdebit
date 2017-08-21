<?php

class CRM_Smartdebit_Api {

  /**
   * Return API URL with base prepended
   * @param array $processorDetails Array of processor details from CRM_Core_Payment_Smartdebit::getProcessorDetails()
   * @param string $path
   * @param string $request
   * @return string
   */
  public static function buildUrl($processorDetails, $path = '', $request = '') {
    if (empty($processorDetails['url_api'])) {
      throw new Exception('Missing API URL in payment processor configuration!');
    }
    $baseUrl = $processorDetails['url_api'];

    $url = $baseUrl . $path;
    if (!empty($request)) {
      if ($request[0] != '?') {
        $request = '?' . $request;
      }
      $url .= $request;
    }
    return $url;
  }

  /**
   *   Send a post request with cURL
   *
   * @param string $url URL to send request to
   * @param string $data POST data to send (in URL encoded Key=value pairs)
   * @param string $username
   * @param string $password
   * @param string $path
   * @return array
   */
  public static function requestPost($url, $data, $username, $password, $path){
    // Set a one-minute timeout for this script
    set_time_limit(60);

    $options = array(
      CURLOPT_RETURNTRANSFER => true, // return web page
      CURLOPT_HEADER => false, // don't return headers
      CURLOPT_POST => true,
      CURLOPT_USERPWD => $username . ':' . $password,
      CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
      CURLOPT_HTTPHEADER => array("Accept: application/xml"),
      CURLOPT_USERAGENT => "CiviCRM PHP DD Client", // Let Smartdebit see who we are
      CURLOPT_SSL_VERIFYHOST => TRUE,
      CURLOPT_SSL_VERIFYPEER => TRUE,
    );

    $session = curl_init( $url . $path);
    curl_setopt_array( $session, $options );

    // Tell curl that this is the body of the POST
    curl_setopt ($session, CURLOPT_POSTFIELDS, $data );

    // $output contains the output string
    $output = curl_exec($session);
    $header = curl_getinfo($session);

    //Store the raw response for later as it's useful to see for integration and understanding
    $_SESSION["rawresponse"] = $output;

    // Set return values
    if (isset($header['http_code'])) {
      $resultsArray['statuscode'] = $header['http_code'];
    }
    else {
      $resultsArray['statuscode'] = -1;
    }

    if(curl_errno($session)) {
      $resultsArray['success'] = FALSE;
      $resultsArray['message'] = 'cURL Error';
      $resultsArray['error'] = curl_error($session);
    }
    else {
      // Results are XML so turn this into a PHP Array
      $resultsArray = (array) simplexml_load_string($output);
      if (!isset($resultsArray['error'])) {
        $resultsArray['error'] = NULL;
      }

      // Determine if the call failed or not
      $resultsArray['statuscode'] = $header['http_code'];
      switch ($header['http_code']) {
        case 200:
          $resultsArray['message'] = 'OK';
          $resultsArray['success'] = TRUE;
          break;
        case 400:
          $resultsArray['message'] = 'Bad Request';
          $resultsArray['success'] = FALSE;
          break;
        case 422:
          $resultsArray['message'] = 'Unprocessable Entity';
          $resultsArray['success'] = FALSE;
          break;
        default:
          $resultsArray['message'] = 'Unknown Error';
          $resultsArray['success'] = FALSE;
      }
    }
    // Return the output
    return $resultsArray;
  }

  /**
   * Format response error for display to user
   *
   * @param array $responseErrors Array or string of errors
   * @return string
   */
  static function formatResponseError($responseErrors)
  {
    if (!$responseErrors) {
      return NULL;
    }

    $message = '';
    if (!is_array($responseErrors)) {
      $message = $responseErrors . '<br />';
      $message .= '<br />';
    }
    else {
      foreach ($responseErrors as $error) {
        $message .= $error . '<br />';
      }
      $message .= '<br />';
    }
    $message .= ts('Please correct the errors and try again');
    return $message;
  }

  /**
   * Retrieve Audit Log from Smartdebit
   * Called during daily sync job
   * @param null $referenceNumber
   * @return array|bool
   */
  static function getSystemStatus($test = FALSE)
  {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails(NULL, $test);
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    // Send payment POST to the target URL
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, 'api/system_status');

    $response = CRM_Smartdebit_Api::requestPost($url, '', $username, $password, '');

    /* Expected Response:
    Array (
      [api_version] => 1.1
      [user] => Array (
        [login] => londoncycapitest
        [assigned_service_users] => Array (
          [service_user] => Array (
            [pslid] => londoncyctest
          )
        )
      )
      [Status] => OK
    )
    */
    return $response;
  }

  /**
   * Retrieve Audit Log from Smartdebit
   * Called during daily sync job
   * @param null $referenceNumber
   * @return array|bool
   */
  static function getAuditLog($referenceNumber = NULL) {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    // Send payment POST to the target URL
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/data/auditlog', "query[service_user][pslid]="
      . urlencode($pslid) . "&query[report_format]=XML");

    // Restrict to a single payer if we have a reference
    if ($referenceNumber) {
      $url .= "&query[reference_number]=" . urlencode($referenceNumber);
    }
    $response = CRM_Smartdebit_Api::requestPost($url, '', $username, $password, '');

    // Take action based upon the response status
    if ($response['success']) {
      $smartDebitArray = array();
      if (isset($response['Data']['AuditDetails']['@attributes'])) {
        // Cater for a single response
        $smartDebitArray[] = $response['Data']['AuditDetails']['@attributes'];
      }
      else {
        // Multiple records
        foreach ($response['Data']['AuditDetails'] as $key => $value) {
          $smartDebitArray[] = $value['@attributes'];
        }
      }
      return $smartDebitArray;
    }
    else {
      $msg = $response['error'];
      CRM_Core_Session::setStatus(ts($msg), 'Smart Debit', 'error');
      return false;
    }
  }

  /**
   * Retrieve Payer Contact Details from Smartdebit
   * Called during daily sync job
   * @param null $referenceNumber
   * @return array|bool
   */
  static function getPayerContactDetails($referenceNumber = NULL)
  {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    // Send payment POST to the target URL
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/data/dump', "query[service_user][pslid]="
      .urlencode($pslid)."&query[report_format]=XML");

    // Restrict to a single payer if we have a reference
    if ($referenceNumber) {
      $url .= "&query[reference_number]=".urlencode($referenceNumber);
    }
    $response = CRM_Smartdebit_Api::requestPost($url, '', $username, $password, '');

    // Take action based upon the response status
    if ($response['success']) {
      $smartDebitArray = array();
      // Cater for a single response
      if (isset($response['Data']['PayerDetails']['@attributes'])) {
        $smartDebitArray[] = $response['Data']['PayerDetails']['@attributes'];
      }
      else {
        foreach ($response['Data']['PayerDetails'] as $key => $value) {
          $smartDebitArray[] = $value['@attributes'];
        }
      }
      return $smartDebitArray;
    }
    else {
      $msg = $response['error'];
      CRM_Core_Session::setStatus(ts($msg), 'Smart Debit', 'error');
      return false;
    }
  }

  /**
   * Retrieve Collection Report from Smart Debit
   * @param $dateOfCollection
   * @return array|bool
   */
  static function getCollectionReport( $dateOfCollection ) {
    if( empty($dateOfCollection)){
      CRM_Core_Session::setStatus(ts('Please select the collection date'), ts('Smart Debit'), 'error');
      return false;
    }

    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();
    $username    = CRM_Utils_Array::value('user_name', $userDetails);
    $password    = CRM_Utils_Array::value('password', $userDetails);
    $pslid       = CRM_Utils_Array::value('signature', $userDetails);

    $collections = array();
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/get_successful_collection_report', "query[service_user][pslid]=$pslid&query[collection_date]=$dateOfCollection");
    $response    = CRM_Smartdebit_Api::requestPost($url, '', $username, $password, '');

    // Take action based upon the response status
    if ($response['success']) {
      if (!isset($response['Successes']['Success']) || !isset($response['Rejects'])) {
        $collections['error'] = $response['Summary'];
        return $collections;
      }
      // Cater for a single response
      if (isset($response['Successes']['Success']['@attributes'])) {
        $collections[] = $response['Successes']['Success']['@attributes'];
      }
      else {
        foreach ($response['Successes']['Success'] as $key => $value) {
          $collections[] = $value['@attributes'];
        }
      }
      return $collections;
    }
    else {
      $url = CRM_Utils_System::url('civicrm/smartdebit/syncsd', 'reset=1'); // DataSource Form
      CRM_Core_Session::setStatus($response['error'], ts('Smart Debit'), 'error');
      CRM_Utils_System::redirect($url);
    }
  }

  /**
   * Get AUDDIS file from Smart Debit. $uri is retrieved using getAuddisList
   * @param null $uri
   * @return array
   */
  static function getAuddisFile($fileId)
  {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    if (empty($fileId)) {
      CRM_Core_Error::debug_log_message('Smartdebit getSmartdebitAuddisFile: Must specify file ID!');
      return false;
    }
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, "/api/auddis/$fileId",
      "query[service_user][pslid]=$pslid");
    $responseAuddis = CRM_Smartdebit_Api::requestPost($url, '', $username, $password, '');
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
   * Get ARUDD file from Smart Debit. $uri is retrieved using getAruddList
   * @param null $uri
   * @return array
   */
  static function getAruddFile($fileId)
  {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();
    $username = CRM_Utils_Array::value('user_name', $userDetails);
    $password = CRM_Utils_Array::value('password', $userDetails);
    $pslid = CRM_Utils_Array::value('signature', $userDetails);

    if (empty($fileId)) {
      CRM_Core_Error::debug_log_message('Smartdebit getSmartdebitAruddFile: Must specify file ID!');
      return false;
    }

    $url = CRM_Smartdebit_Api::buildUrl($userDetails, "/api/arudd/$fileId",
      "query[service_user][pslid]=$pslid");
    $responseArudd = CRM_Smartdebit_Api::requestPost($url, '', $username, $password, '');
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

}