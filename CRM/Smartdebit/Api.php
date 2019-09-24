<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_Api
 */
class CRM_Smartdebit_Api {

  CONST SD_STATE_DRAFT = 0;
  CONST SD_STATE_NEW = 1;
  CONST SD_STATE_LIVE = 10;
  CONST SD_STATE_CANCELLED = 11;
  CONST SD_STATE_REJECTED = 12;
  CONST SD_STATES = [0 => 'Draft', 1 => 'New', 10 => 'Live', 11 => 'Cancelled', 12 => 'Rejected'];

  /**
   * Return API URL with base prepended
   *
   * @param array $processorDetails Array of processor details from CRM_Core_Payment_Smartdebit::getProcessorDetails()
   * @param string $path
   * @param string $request
   *
   * @return string
   * @throws \Exception
   */
  public static function buildUrl($processorDetails, $path = '', $request = '') {
    if (empty($processorDetails['url_api'])) {
      throw new Exception('Missing API URL in payment processor configuration!');
    }
    $baseUrl = $processorDetails['url_api'];

    // Smartdebit API is picky about double // in URL path so make sure we remove it
    if (substr($baseUrl, -1) != '/') {
      $baseUrl .= '/';
    }
    if (substr($path,0,1) == '/') {
      $path = substr($path,1);
    }

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
   * Do the actual call to POST.  The purpose of this function is to allow retries as the smartdebit server sometimes fails to respond.
   *
   * @param string $url
   * @param array $data
   * @param string $username
   * @param string $password
   * @param int $retry
   *
   * @return array
   */
  private static function doPost($url, $data, $username, $password, $retry = 3, $method = 'POST') {
    while ($retry > 0) {
      if (getenv('CIVICRM_UF') === 'UnitTests') {
        list($header, $output, $error) = CRM_Smartdebit_Mock::post($url, $data, $username, $password, $method);
      }
      else {
        list($header, $output, $error) = self::post($url, $data, $username, $password, $method);
      }

      if($error['code']) {
        $message = 'Smartdebit doPost: Error ' . $error['code'] . ' : ' . $error['message'];
        $retry--;
        if ($retry > 0) {
          $message .= '. Retrying ' . $retry . ' times';
        }
        Civi::log()->warning($message);
      }
      else {
        break;
      }
    }
    return [$header, $output, $error];
  }

  public static function requestUpdate($paymentProcessor, $reference, $smartDebitParams) {
    $url = CRM_Smartdebit_Api::buildUrl($paymentProcessor, 'api/ddi/variable/' . $reference);
    if (CRM_Smartdebit_Settings::getValue('debug')) { Civi::log()->debug('Smartdebit requestUpdate: ' . $url . print_r($smartDebitParams, TRUE)); }
    $response = CRM_Smartdebit_Api::requestPost($url, $smartDebitParams,
      CRM_Core_Payment_Smartdebit::getUserStatic($paymentProcessor),
      CRM_Core_Payment_Smartdebit::getPasswordStatic($paymentProcessor), 'XML', 'PUT');
    return $response;
  }

  /**
   *   Send a post request with cURL
   *
   * @param string $url URL to send request to
   * @param array $data POST data to send
   * @param string $username
   * @param string $password
   *
   * @return array
   */
  public static function requestPost($url, $data, $username, $password, $format='XML', $method = 'POST') {
    // Prepare data
    $data = self::encodePostParams($data);

    list($header, $output, $error) = self::doPost($url, $data, $username, $password, 3, $method);

    // Set return values
    if (isset($header['http_code'])) {
      $resultsArray['statuscode'] = $header['http_code'];
    }
    else {
      $resultsArray['statuscode'] = -1;
    }

    if($error['code']) {
      $resultsArray['success'] = FALSE;
      $resultsArray['message'] = 'cURL Error';
      $resultsArray['error'] = $error['message'];
      Civi::log()->debug('Smartdebit requestPost: ' . $resultsArray['message'] . ' : ' . $resultsArray['error']);
    }
    else {
      // Decode main output
      switch ($format) {
        case 'XML':
          // Results are XML so turn this into a PHP Array (simplexml_load_string returns an object)
          $resultsArray = json_decode(json_encode((array) simplexml_load_string($output)),1);
          break;

        case 'CSV':
          $resultsArray['Data'] = str_getcsv($output, "\r\n"); //parse the rows into an array
          break;
      }

      if (!isset($resultsArray['error'])) {
        $resultsArray['error'] = NULL;
      }

      // Determine if the call failed or not
      switch ($header['http_code']) {
        case 200:
          $resultsArray['message'] = 'OK';
          if (!isset($resultsArray['success'])) {
            // success is set to an array during API validate, but not set on API create
            $resultsArray['success'] = TRUE;
          }
          break;
        case 400:
          $resultsArray['message'] = 'BAD REQUEST';
          $resultsArray['success'] = FALSE;
          break;
        case 401:
          $resultsArray['message'] = 'UNAUTHORIZED';
          $resultsArray['success'] = FALSE;
          break;
        case 404:
          $resultsArray['message'] = 'NOT FOUND';
          $resultsArray['success'] = FALSE;
          break;
        case 422:
          $resultsArray['message'] = 'UNPROCESSABLE ENTITY';
          $resultsArray['success'] = FALSE;
          break;
        case 500:
          // Sometimes we get an XML array containing an error, try and extract it.
          if (!empty($resultsArray['Data']) && !empty($resultsArray['Data'][0])) {
            $xmlData = json_decode(json_encode((array) simplexml_load_string($resultsArray['Data'][0])),1);
            if (isset($xmlData['error'])) {
              $resultsArray['message'] = $xmlData['error'];
              $resultsArray['success'] = FALSE;
              break;
            }
          }
        default:
          $resultsArray['message'] = 'Unknown Error';
          $resultsArray['success'] = FALSE;
      }
    }
    // Return the output
    return $resultsArray;
  }

  private static function post($url, $data, $username, $password, $method) {
    $options = [
      CURLOPT_RETURNTRANSFER => TRUE, // return web page
      CURLOPT_HEADER => FALSE, // don't return headers
      CURLOPT_USERPWD => $username . ':' . $password,
      CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
      CURLOPT_HTTPHEADER => ["Accept: application/xml"],
      CURLOPT_USERAGENT => "CiviCRM Smartdebit Client", // Let Smartdebit see who we are
      CURLOPT_SSL_VERIFYHOST => Civi::settings()->get('verifySSL') ? 2 : 0,
      CURLOPT_SSL_VERIFYPEER => Civi::settings()->get('verifySSL'),
    ];

    switch ($method) {
      case 'POST':
        $options[CURLOPT_POST] = TRUE;
        $options[CURLOPT_POSTFIELDS] = $data;
        // curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
        break;

      case 'PUT':
        $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        $url .= '?' . $data;
        // curl_setopt($curlSession, CURLOPT_POSTFIELDS, http_build_query($data));
        break;
    }

    $curlSession = curl_init($url);
    curl_setopt_array($curlSession, $options);

    // $output contains the output string
    $output = curl_exec($curlSession);
    $header = curl_getinfo($curlSession);

    $error['code'] = curl_errno($curlSession);
    $error['message'] = curl_error($curlSession);

    curl_close($curlSession);

    return [$header, $output, $error];
  }

  /**
   * Format error from Smartdebit for display
   *
   * @param array $response
   * @param string $url Request URL that generated the response
   * @param string $referenceNumber (Smartdebit reference number)
   */
  public static function reportError($response, $url, $referenceNumber = '') {
    if (isset($response['head']['title'])) {
      $msg = $response['head']['title'];
    }
    else {
      if (isset($response['error']) && $response['error'] == 'Database is empty.') {
        $msg = 'Transaction Ref ' . $referenceNumber . ' not found!';
      }
      else {
        $msg = $response['statuscode'] . ': ' . $response['message'];
      }
    }
    $msg .= ' Request URL: ' . $url;
    CRM_Core_Session::setStatus($msg, 'Smartdebit API', 'error');
    Civi::log()->error('Smartdebit API: ' . $msg);
  }

  /**
   * Format response error for display to user
   *
   * @param array $responseErrors Array or string of errors
   * @return string
   */
  static function formatResponseError($responseErrors) {
    if (!$responseErrors) {
      return NULL;
    }

    $message = NULL;
    if (!is_array($responseErrors)) {
      $message = $responseErrors . '.';
    }
    else {
      foreach ($responseErrors as $error) {
        $message .= $error . '. ';
      }
    }
    return $message;
  }

  /**
   * Retrieve Smartdebit System Status
   *
   * @param bool $test
   *
   * @return array
   *
   * @throws \Exception
   */
  public static function getSystemStatus($test = FALSE)
  {
    $processorParams = [
      'is_test' => $test,
    ];
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails($processorParams);

    // Send payment POST to the target URL
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, 'api/system_status');

    $response = CRM_Smartdebit_Api::requestPost($url, NULL, $userDetails['user_name'], $userDetails['password']);

    /* Expected Response:
    Array (
      [api_version] => 1.1
      [user] => Array (
        [login] => testuserapitest
        [assigned_service_users] => Array (
          [service_user] => Array (
            [pslid] => testusertest
          )
    OR
            0 => Array (1)
              pslid => "testusertest"
            1 => Array (1)
              pslid => "otherusertest"
            )
      )
      [Status] => OK
    )
    */
    // As we're just displaying this onscreen, convert pslid array to a string and return it
    if (isset($response['user']['assigned_service_users']['service_user'][0]['pslid'])) {
      $pslIds = '';
      foreach ($response['user']['assigned_service_users']['service_user'] as $key => $value) {
        $pslIds .= $value['pslid'] . '; ';
      }
      $response['user']['assigned_service_users']['service_user'] = ['pslid' => $pslIds];
    }
    return $response;
  }

  /**
   * Retrieve Audit Log from Smartdebit
   * Called during daily sync job
   *
   * @param string $referenceNumber
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function getAuditLog($referenceNumber = '') {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();

    // Send payment POST to the target URL
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/data/auditlog', "query[service_user][pslid]="
      . urlencode($userDetails['signature']) . "&query[report_format]=XML");

    // Restrict to a single payer if we have a reference
    if (!empty($referenceNumber)) {
      $url .= "&query[reference_number]=" . urlencode($referenceNumber);
    }
    $response = CRM_Smartdebit_Api::requestPost($url, NULL, $userDetails['user_name'], $userDetails['password']);

    // Take action based upon the response status
    if ($response['success']) {
      $smartDebitArray = [];
      if (isset($response['Data']['AuditDetails']['@attributes'])) {
        // Cater for a single response
        $smartDebitArray[] = $response['Data']['AuditDetails']['@attributes'];
      }
      else {
        // Multiple records
        foreach ($response['Data']['AuditDetails'] as $value) {
          $smartDebitArray[] = $value['@attributes'];
        }
      }
      return $smartDebitArray;
    }
    else {
      self::reportError($response, $url);
      return FALSE;
    }
  }

  /**
   * Retrieve Collection Report from Smart Debit
   *
   * @param $dateOfCollection
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function getCollectionReport($dateOfCollection) {
    if (empty($dateOfCollection)) {
      CRM_Core_Session::setStatus(ts('Please select the collection date'), ts('Smart Debit'), 'error');
      return FALSE;
    }

    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();

    $collections = [];
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/get_successful_collection_report', "query[service_user][pslid]=" . $userDetails['signature'] . "&query[collection_date]=$dateOfCollection");
    $response = CRM_Smartdebit_Api::requestPost($url, NULL, $userDetails['user_name'], $userDetails['password']);

    // Take action based upon the response status
    if ($response['success']) {
      if (!isset($response['Successes']) && !isset($response['Rejects'])) {
        // No collection report for this date
        $collections['error'] = $response['Summary'];
        return $collections;
      }
      else {
        // We got a collection report with one or more responses.
        CRM_Smartdebit_CollectionReports::saveReport(CRM_Utils_Array::value('Summary', $response));

        // Successful collections
        if (isset($response['Successes']['Success']['@attributes'])) {
          // Cater for a single response
          $successes[]['@attributes'] = $response['Successes']['Success']['@attributes'];
        }
        else {
          // Multiple responses
          $successes = $response['Successes']['Success'];
        }

        // Rejected collections
        if (isset($response['Rejects']['Rejected']['@attributes'])) {
          // Cater for a single response
          $rejects[]['@attributes'] = $response['Rejects']['Rejected']['@attributes'];
        }
        else {
          // Multiple responses
          $rejects = $response['Rejects']['Rejected'];
        }

        foreach ($successes as $key => $value) {
          $collection = $value['@attributes'];
          $collection['success'] = 1;
          $collections[] = $collection;
        }

        foreach ($rejects as $key => $value) {
          $collection = $value['@attributes'];
          $collection['success'] = 0;
          $collections[] = $collection;
        }

        return $collections;
      }
    }
    else {
      self::reportError($response, $url);
      $redirectUrl = CRM_Utils_System::url('civicrm/smartdebit/syncsd', 'reset=1'); // DataSource Form
      CRM_Utils_System::redirect($redirectUrl);
    }
    return FALSE;
  }

  /**
   * Get AUDDIS file from Smart Debit. $uri is retrieved using getAuddisList
   *
   * @param int $fileId
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function getAuddisFile($fileId) {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();

    if (empty($fileId)) {
      Civi::log()->debug('Smartdebit getSmartdebitAuddisFile: Must specify file ID!');
      return FALSE;
    }
    $url = CRM_Smartdebit_Api::buildUrl($userDetails, "/api/auddis/$fileId",
      "query[service_user][pslid]=" . $userDetails['signature']);
    $responseAuddis = CRM_Smartdebit_Api::requestPost($url, NULL, $userDetails['user_name'], $userDetails['password']);
    $scrambled = str_replace(" ", "+", $responseAuddis['file']);
    $outputafterencode = base64_decode($scrambled);
    $auddisArray = json_decode(json_encode((array)simplexml_load_string($outputafterencode)), 1);
    $result = [];

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
   *
   * @param int $fileId
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function getAruddFile($fileId) {
    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails();

    if (empty($fileId)) {
      Civi::log()->debug('Smartdebit getSmartdebitAruddFile: Must specify file ID!');
      return FALSE;
    }

    $url = CRM_Smartdebit_Api::buildUrl($userDetails, "/api/arudd/$fileId",
      "query[service_user][pslid]=" . $userDetails['signature']);
    $responseArudd = CRM_Smartdebit_Api::requestPost($url, NULL, $userDetails['user_name'], $userDetails['password']);
    $scrambled = str_replace(" ", "+", $responseArudd['file']);
    $outputafterencode = base64_decode($scrambled);
    $aruddArray = json_decode(json_encode((array)simplexml_load_string($outputafterencode)), 1);
    $result = [];

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
   * Encode POST params HTTP POST to Smartdebit
   * @param array $params
   *
   * @return null|string
   */
  private static function encodePostParams($params) {
    if (empty($params)) {
      return NULL;
    }

    $post = NULL;

    foreach ($params as $key => $value) {
      // We ignore empty parameters unless they are numeric (ie. 0)
      if (!empty($value) || is_numeric($value)) {
        if (!empty($post)) {
          $post .= '&';
        }
        $post .= $key . '=' . urlencode($value);
      }
    }
    return $post;
  }

}
