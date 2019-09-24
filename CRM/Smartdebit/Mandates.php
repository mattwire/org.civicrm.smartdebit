<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_Mandates
 * This class handles all the mandates from Smartdebit (Payer Contact Details)
 */
class CRM_Smartdebit_Mandates {

  const TABLENAME='veda_smartdebit_mandates';

  /**
   * Get total number of smartdebit mandates
   *
   * @param bool $onlyWithRecurId Only retrieve smartdebit mandates which have a recurring contribution
   *
   * @return integer
   */
  public static function count($onlyWithRecurId = FALSE) {
    $sql = "SELECT COUNT(*) FROM `" . self::TABLENAME . "`";
    if ($onlyWithRecurId) {
      $sql .= " WHERE recur_id IS NOT NULL";
    }
    $count = (int) CRM_Core_DAO::singleValueQuery($sql);
    return $count;
  }

  /**
   * Batch task to retrieve payer contact details (mandates)
   *
   * @return bool Number of mandates retrieved
   * @throws \Exception
   */
  public static function retrieveAll() {
    Civi::log()->info('Smartdebit: Retrieving all Mandates.');
    // Get list of payers from Smartdebit
    self::retrieve();
    return self::count();
  }

  /**
   * Get the smartdebit mandate from the cache by reference number
   *
   * @param array $recurParams
   *   'trxn_id' => Transaction ID (Smartdebit Mandate reference number)
   *   'refresh' => Whether to refresh mandate from smartdebit or not (Default TRUE)
   *
   * @return array $payerContactDetails
   * @throws \Exception
   */
  public static function getbyReference($recurParams) {
    $transactionId = CRM_Utils_Array::value('trxn_id', $recurParams);
    $refresh = CRM_Utils_Array::value('refresh', $recurParams, TRUE);
    if ($refresh) {
      // Retrieve the mandate from Smartdebit, update the cache and return the retrieved mandate
      self::retrieve($recurParams);
    }

    // Return the retrieved mandate from the database
    $sql = "SELECT * FROM `" . self::TABLENAME . "` WHERE reference_number=%1";
    $sqlParams = [1 => [$transactionId, 'String']];
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      $payerContactDetails['title'] = $dao->title;
      $payerContactDetails['first_name'] = $dao->first_name;
      $payerContactDetails['last_name'] = $dao->last_name;
      $payerContactDetails['email_address'] = $dao->email_address;
      $payerContactDetails['address_1'] = $dao->address_1;
      $payerContactDetails['address_2'] = $dao->address_2;
      $payerContactDetails['address_3'] = $dao->address_3;
      $payerContactDetails['town'] = $dao->town;
      $payerContactDetails['county'] = $dao->county;
      $payerContactDetails['postcode'] = $dao->postcode;
      $payerContactDetails['first_amount'] = $dao->first_amount;
      $payerContactDetails['default_amount'] = $dao->default_amount;
      $payerContactDetails['frequency_type'] = $dao->frequency_type;
      $payerContactDetails['frequency_factor'] = $dao->frequency_factor;
      $payerContactDetails['start_date'] = $dao->start_date;
      $payerContactDetails['current_state'] = $dao->current_state;
      $payerContactDetails['reference_number'] = $dao->reference_number;
      $payerContactDetails['payerReference'] = $dao->payerReference;
      $payerContactDetails['recur_id'] = $dao->recur_id;
      return $payerContactDetails;
    }
    return NULL;
  }

  /**
   * Get the smartdebit mandates from the cache by reference number
   *
   * @param bool $refresh Whether to refresh mandate from smartdebit or not
   * @param bool $onlyWithRecurId Only retrieve smartdebit mandates which have a recurring contribution
   *
   * @return array $smartDebitParams
   * @throws \Exception
   */
  public static function getAll($refresh, $onlyWithRecurId=FALSE, $params) {
    if ($refresh) {
      if ($onlyWithRecurId) {
        // If the mandate doesn't have a recur ID then it's not reconciled in CiviCRM so we don't sync it from Smartdebit.
        $sql = "SELECT reference_number FROM `" . self::TABLENAME . "`";
        if ($onlyWithRecurId) {
          $sql .= " WHERE recur_id IS NOT NULL";
        }
        $dao = CRM_Core_DAO::executeQuery($sql);
        while ($dao->fetch()) {
          self::retrieve(['trxn_id' => $dao->reference_number]);
        }
      }
      else {
        // Update the cached mandates from Smartdebit
        // WARNING: This will pull down ALL mandates from smartdebit which in some cases can be really big (>50k) - it can take a long time
        self::retrieveAll();
      }
    }

    $sql = "SELECT * FROM `" . self::TABLENAME . "`";
    if ($onlyWithRecurId) {
      $sql .= " WHERE recur_id IS NOT NULL";
    }
    $sql .= CRM_Smartdebit_Base::limitClause($params);

    $dao = CRM_Core_DAO::executeQuery($sql);
    $smartDebitPayerContacts = [];
    while ($dao->fetch()) {
      $smartDebitParams['title'] = $dao->title;
      $smartDebitParams['first_name'] = $dao->first_name;
      $smartDebitParams['last_name'] = $dao->last_name;
      $smartDebitParams['email_address'] = $dao->email_address;
      $smartDebitParams['address_1'] = $dao->address_1;
      $smartDebitParams['address_2'] = $dao->address_2;
      $smartDebitParams['address_3'] = $dao->address_3;
      $smartDebitParams['town'] = $dao->town;
      $smartDebitParams['county'] = $dao->county;
      $smartDebitParams['postcode'] = $dao->postcode;
      $smartDebitParams['first_amount'] = $dao->first_amount;
      $smartDebitParams['default_amount'] = $dao->default_amount;
      $smartDebitParams['frequency_type'] = $dao->frequency_type;
      $smartDebitParams['frequency_factor'] = $dao->frequency_factor;
      $smartDebitParams['start_date'] = $dao->start_date;
      $smartDebitParams['current_state'] = $dao->current_state;
      $smartDebitParams['reference_number'] = $dao->reference_number;
      $smartDebitParams['payerReference'] = $dao->payerReference;
      $smartDebitParams['recur_id'] = $dao->recur_id;
      $smartDebitPayerContacts[] = $smartDebitParams;
    }
    return $smartDebitPayerContacts;
  }

  /**
   * Delete mandate(s) from CiviCRM
   *
   * @param string $reference (Transaction ID)
   */
  public static function delete($reference = '') {
    if ($reference) {
      $sql = "DELETE FROM `" . self::TABLENAME . "` WHERE reference_number='" . $reference . "'";
    }
    else {
      // if the civicrm_sd table exists, then empty it
      $sql = "TRUNCATE TABLE `" . self::TABLENAME . "`";
    }
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Update Smartdebit Mandates in table veda_smartdebit_mandates for further analysis
   * This table is only used by Reconciliation functions
   *
   * @param array $smartDebitPayerContactDetails (array of smart debit contact details)
   *
   * @return bool
   */
  public static function save($smartDebitPayerContactDetails, $format) {
    // Get payer contact details
    if (empty($smartDebitPayerContactDetails)) {
      return FALSE;
    }

    // If we have retrieved a single record, an optional setting at smartdebit allows it to return account name,number and sortcode.
    // Unfortunately the headers returned by the API do not include these and we get an error.
    // So we hardcode the headers here and detect which set of headers to use (ie. if values > headers we use extended headers)
    $csvHeaders = explode(',','payer_reference,reference_number,frequency_type,frequency_factor,start_date,title,first_name,last_name,email_address,address_1,address_2,address_3,town,county,postcode,regular_amount,first_amount,current_state,support_gift_aid');
    if (($format === 'CSV') && (count($smartDebitPayerContactDetails) == 1)) {
      $csvValues = str_getcsv(CRM_Utils_Array::first($smartDebitPayerContactDetails), ',');
      if (count($csvValues) == count($csvHeaders)) {
        // Ok, use standard headers
      }
      elseif (count($csvValues) == (count($csvHeaders) + 3)) {
        $csvHeaders = explode(',', 'payer_reference,reference_number,frequency_type,frequency_factor,start_date,account_name,sort_code,account_no,title,first_name,last_name,email_address,address_1,address_2,address_3,town,county,postcode,regular_amount,first_amount,current_state,support_gift_aid');
      }
      else {
        throw new CRM_Core_Exception('Count of headers does not match count of values returned from Smartdebit API for Mandate');
      }
    }

    // Insert mandates into table
    foreach ($smartDebitPayerContactDetails as $smartDebitValues) {
      if ($format === 'CSV') {
        // We do the CSV parsing here to avoid running out of memory on really large datasets (> 50k)
        $csvValues = str_getcsv($smartDebitValues, ',');
        for ($index = 0; $index < count($csvHeaders); $index++) {
          $smartDebitRecord[$csvHeaders[$index]] = $csvValues[$index];
        }
      }
      else {
        // XML format, we don't need to make any changes
        $smartDebitRecord = $smartDebitValues;
      }

      // Get the recurring contribution for this mandate
      try {
        $recurContribution = civicrm_api3('ContributionRecur', 'getsingle', ['trxn_id' => $smartDebitRecord['reference_number']]);
        $recurId = $recurContribution['id'];
      }
      catch (CiviCRM_API3_Exception $e) {
        // Couldn't find a matching recur Id
        $recurId = NULL;
      }

      // Clean up retrieved data before saving to database
      // This is the only API that returns regular_amount, everywhere else we use "default_amount" so change it before returning
      if (isset($smartDebitRecord['regular_amount'])) {
        $smartDebitRecord['default_amount'] = $smartDebitRecord['regular_amount'];
        unset($smartDebitRecord['regular_amount']);
      }
      // Clean up first_amount/regular_amount which gets sent to us here with a currency symbol (eg. £85.00)
      if (isset($smartDebitRecord['first_amount'])) {
        $smartDebitRecord['first_amount'] = CRM_Smartdebit_Utils::getCleanSmartdebitAmount($smartDebitRecord['first_amount']);
      }
      if (isset($smartDebitRecord['default_amount'])) {
        $smartDebitRecord['default_amount'] = CRM_Smartdebit_Utils::getCleanSmartdebitAmount($smartDebitRecord['default_amount']);
      }

      $sql = "INSERT INTO `" . self::TABLENAME . "`(
            `title`,
            `first_name`,
            `last_name`, 
            `email_address`,
            `address_1`, 
            `address_2`, 
            `address_3`, 
            `town`, 
            `county`,
            `postcode`,
            `first_amount`,
            `default_amount`,
            `frequency_type`,
            `frequency_factor`,
            `start_date`,
            `current_state`,
            `reference_number`,
            `payerReference`";
      if (!empty($recurId)) {
        $sql .= ",`recur_id`
            ) 
            VALUES (%1,%2,%3,%4,%5,%6,%7,%8,%9,%10,%11,%12,%13,%14,%15,%16,%17,%18,{$recurId})
            ";
      }
      else {
        $sql .= "
            ) 
            VALUES (%1,%2,%3,%4,%5,%6,%7,%8,%9,%10,%11,%12,%13,%14,%15,%16,%17,%18)
            ";
      }
      $params = [
        1 => [CRM_Utils_Array::value('title', $smartDebitRecord, 'NULL'), 'String'],
        2 => [CRM_Utils_Array::value('first_name', $smartDebitRecord, 'NULL'), 'String'],
        3 => [CRM_Utils_Array::value('last_name', $smartDebitRecord, 'NULL'), 'String'],
        4 => [CRM_Utils_Array::value('email_address', $smartDebitRecord, 'NULL'),  'String'],
        5 => [CRM_Utils_Array::value('address_1', $smartDebitRecord, 'NULL'), 'String'],
        6 => [CRM_Utils_Array::value('address_2', $smartDebitRecord, 'NULL'), 'String'],
        7 => [CRM_Utils_Array::value('address_3', $smartDebitRecord, 'NULL'), 'String'],
        8 => [CRM_Utils_Array::value('town', $smartDebitRecord, 'NULL'), 'String'],
        9 => [CRM_Utils_Array::value('county', $smartDebitRecord, 'NULL'), 'String'],
        10 => [CRM_Utils_Array::value('postcode', $smartDebitRecord, 'NULL'), 'String'],
        11 => [CRM_Utils_Array::value('first_amount', $smartDebitRecord, 'NULL'), 'String'],
        12 => [CRM_Utils_Array::value('default_amount', $smartDebitRecord, 'NULL'), 'String'],
        13 => [CRM_Utils_Array::value('frequency_type', $smartDebitRecord, 'NULL'), 'String'],
        14 => [CRM_Utils_Array::value('frequency_factor', $smartDebitRecord, 'NULL'), 'Int'],
        15 => [CRM_Utils_Array::value('start_date', $smartDebitRecord, 'NULL'), 'String'],
        16 => [CRM_Utils_Array::value('current_state', $smartDebitRecord, 'NULL'), 'Int'],
        17 => [CRM_Utils_Array::value('reference_number', $smartDebitRecord, 'NULL'), 'String'],
        18 => [CRM_Utils_Array::value('payer_reference', $smartDebitRecord, 'NULL'), 'String'],
      ];
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    return TRUE;
  }

  /**
   * Retrieve Payer Contact Details from Smartdebit
   * Called during daily sync job
   *
   * @param array $recurParams
   *   'trxn_id' => Optional transaction ID / Smartdebit mandate reference number.
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function retrieve($recurParams = []) {
    $transactionId = CRM_Utils_Array::value('trxn_id', $recurParams, '');

    $userDetails = CRM_Core_Payment_Smartdebit::getProcessorDetails($recurParams);

    // Originally we did everything in XML format, but for sites with > 50000 mandates the report is too big
    // and we get server timeouts.  It's not possible to retrieve partial results (either one or all) so we switch
    // to CSV which takes around 60seconds for 50000 mandates.
    $format = CRM_Utils_Array::value('format', $recurParams, 'CSV');

    // Send payment POST to the target URL
    switch($format) {
      case 'XML':
        $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/data/dump', "query[service_user][pslid]="
          . $userDetails['signature'] . "&query[report_format]=XML");
        break;

      case 'CSV':
        $url = CRM_Smartdebit_Api::buildUrl($userDetails, '/api/data/dump', "query[service_user][pslid]="
          . $userDetails['signature'] . "&query[report_format]=CSV" . "&query[include_header]=false");
        break;
    }

    // Restrict to a single payer if we have a reference
    if (!empty($transactionId)) {
      $url .= "&query[reference_number]=".urlencode($transactionId);
    }
    $response = CRM_Smartdebit_Api::requestPost($url, NULL, $userDetails['user_name'], $userDetails['password'], $format);

    // Take action based upon the response status
    if ($response['success']) {
      $smartDebitArray = [];

      switch ($format) {
        case 'XML':
          // Get Payer Details from response
          if (isset($response['Data']['PayerDetails']['@attributes'])) {
            // A single response
            $smartDebitArray[] = $response['Data']['PayerDetails']['@attributes'];
          }
          else {
            // Multiple responses
            foreach ($response['Data']['PayerDetails'] as $value) {
              $smartDebitArray[] = $value['@attributes'];
            }
          }
          break;

        case 'CSV':
          $smartDebitArray = $response['Data'];
          break;
      }

      self::delete($transactionId);
      return self::save($smartDebitArray, $format);
    }
    else {
      CRM_Smartdebit_Api::reportError($response, $url, $transactionId);
      return FALSE;
    }
  }

}
