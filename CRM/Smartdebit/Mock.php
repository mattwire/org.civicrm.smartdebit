<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Mock results from smartdebit API, used when CIVICRM_UF=UnitTests
 * Class CRM_Smartdebit_Mock
 */
class CRM_Smartdebit_Mock {

  public static function post($url, $data, $username, $password, $method) {

    $baseUrl = 'https://secure.ddprocessing.co.uk/';
    $api = substr($url, strlen($baseUrl), strpos($url, '?') - strlen($baseUrl));

    switch ($api) {
      case 'api/data/dump':
        return self::mockPayerDetails($url, $data, $username, $password);
      case '/api/ get_successful_collection_report':
        return self::mockCollectionReports($url);
    }
  }

  /**
   * Get the query part of a URL, return as an array
   * @param $url
   *
   * @return array
   */
  private static function getQuery($url) {
    $query = substr($url, strpos($url, '?') + 1);
    $queryArgs = explode('&', $query);
    foreach ($queryArgs as $arg) {
      $queryParts = explode('=', $arg);
      $queryResult[CRM_Utils_Array::value(0, $queryParts)] = CRM_Utils_Array::value(1, $queryParts);
    }
    return $queryResult;
  }

  /**
   * Get response header
   * @param $url
   *
   * @return array
   */
  private static function getHeader($url) {
    return [
      'url' => $url,
      'content_type' => 'text/xml; charset=iso-8859-1',
      'http_code' => 200,
      'header_size' => 1094,
      'request_size' => 363,
      'filetime' => -1,
      'ssl_verify_result' => 0,
      'redirect_count' => 0,
      'total_time' => 1.1686879999999999,
      'namelookup_time' => 0.50938700000000003,
      'connect_time' => 0.58523400000000003,
      'pretransfer_time' => 0.75329500000000005,
      'size_upload' => 0.0,
      'size_download' => 583.0,
      'speed_download' => 499.0,
      'speed_upload' => 0.0,
      'download_content_length' => 583.0,
      'upload_content_length' => 0.0,
      'starttransfer_time' => 1.165233,
      'redirect_time' => 0.0,
      'redirect_url' => '',
      'primary_ip' => '12.13.14.15',
      'certinfo' =>
        [
        ],
      'primary_port' => 443,
      'local_ip' => '16.17.18.19',
      'local_port' => 45650,
    ];
  }

  /**
   * Return errors
   *
   * @param int $code
   * @param string $message
   *
   * @return array
   */
  private static function getError($code = 0, $message = '') {
    return [
      'code' => $code,
      'message' => $message,
    ];
  }

  private static function mockCollectionReports($url) {
    $output = '<?xml version="1.0" encoding="UTF-8"?>
<CollectionReport xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<Successes>
<Success customer_id="1668701" amount="4.99" debit_date="16/01/2015 00:00" account_name="MRS A R
PAYER" transaction_ref="" reference_number="ABC133184"/>
</Successes>
<Rejects>
<Rejected customer_id="1765852" amount="4.99" debit_date="16/01/2015 00:00" account_name="MS M
PAYER" error_message="" transaction_ref="" reference_number="ABC163640"/>
</Rejects>
<Summary>
<CollectionDate>16/01/2015</CollectionDate>
<Succesful number_submitted="1" amount_submitted="4.99"/>
<Rejected amount_rejected="4.99" number_rejected="1"/>
</Summary>
</CollectionReport>';

    $header = self::getHeader($url);
    $error = self::getError();
    return [$header, $output, $error];
  }

  /**
   * Payer Details API
   * Eg: https://secure.ddprocessing.co.uk/api/data/dump?query[service_user][pslid]=sdtest&query[report_format]=XML&query[reference_number]=TED00000128
   * @param $query
   * @param $data
   * @param $username
   * @param $password
   */
  private static function mockPayerDetails($url, $data, $username, $password) {
    $query = self::getQuery($url);
    $referenceNumber = $query['query[reference_number]'];
    $header = self::getHeader($url);
    $error = self::getError();

    // This is the output from a single response:
    $output = '<?xml version="1.0" encoding="UTF-8"?>
<DataDump xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="newbacs-advices.xsd">
  <Data>
    <PayerDetails town="bob" first_name="test" frequency_factor="1" email_address="admin@example.com" address_1="bob" county="" support_gift_aid="false" frequency_type="Y" last_name="test" postcode="ab123de" current_state="10" reference_number="' . $referenceNumber . '" address_2="" first_amount="&#163;55.83" payerReference="203" address_3="" regular_amount="&#163;55.83" start_date="2018-02-20" title=""/>
  </Data>
</DataDump>
';

    return [$header, $output, $error];
  }

}
