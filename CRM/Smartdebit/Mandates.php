<?php

/**
 * Class CRM_Smartdebit_Mandates
 * This class handles all the mandates from Smartdebit (Payer Contact Details)
 */
class CRM_Smartdebit_Mandates {

  /**
   * Get total number of smartdebit mandates
   *
   * @param bool $onlyWithRecurId Only retrieve smartdebit mandates which have a recurring contribution
   *
   * @return integer
   */
  public static function count($onlyWithRecurId = FALSE) {
    $sql = "SELECT COUNT(*) FROM veda_smartdebit_mandates";
    if ($onlyWithRecurId) {
      $sql .= " WHERE recur_id IS NOT NULL";
    }
    $count = (int) CRM_Core_DAO::singleValueQuery($sql);
    return $count;
  }

  /**
   * Batch task to retrieve payer contact details (mandates)
   *
   * @return array|bool
   * @throws \Exception
   */
  public static function getFromSmartdebit() {
    Civi::log()->info('Smartdebit Sync: Retrieving Smart Debit Payer Contact Details.');
    // Get list of payers from Smartdebit
    $smartDebitPayerContacts = CRM_Smartdebit_Api::getPayerContactDetails();

    // Update mandates table for reconciliation functions
    self::updateCache($smartDebitPayerContacts, TRUE);
    return $smartDebitPayerContacts;
  }

  /**
   * Get the smartdebit mandate from the cache by reference number
   *
   * @param string $transactionId
   * @param bool $refresh Whether to refresh mandate from smartdebit or not
   *
   * @return array $payerContactDetails
   * @throws \Exception
   */
  public static function getbyReference($transactionId, $refresh) {
    if ($refresh) {
      // Retrieve the mandate from Smartdebit, update the cache and return the retrieved mandate
      $payerContactDetails = CRM_Smartdebit_Api::getPayerContactDetails($transactionId);
      if ($payerContactDetails) {
        CRM_Smartdebit_Mandates::updateCache($payerContactDetails);
        return reset($payerContactDetails);
      }
    }

    // Return the retrieved mandate from the database
    $sql = "SELECT * FROM `veda_smartdebit_mandates` WHERE reference_number=%1";
    $params = array(1 => array($transactionId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
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
   * Get the smartdebit mandate from the cache by reference number
   * @param string $transactionId
   * @param bool $refresh Whether to refresh mandate from smartdebit or not
   * @param bool $onlyWithRecurId Only retrieve smartdebit mandates which have a recurring contribution
   *
   * @return array $smartDebitParams
   * @throws \Exception
   */
  public static function getAll($refresh, $onlyWithRecurId=FALSE) {
    if ($refresh) {
      // Update the cached mandate
      self::getFromSmartdebit();
    }

    $sql = "SELECT * FROM `veda_smartdebit_mandates`";
    if ($onlyWithRecurId) {
      $sql .= " WHERE recur_id IS NOT NULL";
    }
    $dao = CRM_Core_DAO::executeQuery($sql);
    $smartDebitPayerContacts = array();
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
   * Update Smartdebit Mandates in table veda_smartdebit_mandates for further analysis
   * This table is only used by Reconciliation functions
   *
   * @param array $smartDebitPayerContactDetails (array of smart debit contact details : call CRM_Smartdebit_Api::getPayerContactDetails())
   * @param bool $truncate If true, truncate the table before inserting new records.
   *
   * @return bool|int
   */
  public static function updateCache($smartDebitPayerContactDetails, $truncate = FALSE) {
    if ($truncate) {
      // if the civicrm_sd table exists, then empty it
      $emptySql = "TRUNCATE TABLE `veda_smartdebit_mandates`";
      CRM_Core_DAO::executeQuery($emptySql);
    }

    // Get payer contact details
    if (empty($smartDebitPayerContactDetails)) {
      return FALSE;
    }
    // Insert mandates into table
    foreach ($smartDebitPayerContactDetails as $smartDebitRecord) {
      if (!$truncate) {
        $deleteSql = "DELETE FROM `veda_smartdebit_mandates` WHERE reference_number='%1'";
        $deleteParams = array(1 => $smartDebitRecord['reference_number']);
        CRM_Core_DAO::executeQuery($deleteSql);
      }

      // Get the recurring contribution for this mandate
      try {
        $recurContribution = civicrm_api3('ContributionRecur', 'getsingle', array('trxn_id' => $smartDebitRecord['reference_number']));
        $recurId = $recurContribution['id'];
      }
      catch (CiviCRM_API3_Exception $e) {
        // Couldn't find a matching recur Id
        $recurId = NULL;
      }

      $sql = "INSERT INTO `veda_smartdebit_mandates`(
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
      $params = array(
        1 => array(CRM_Utils_Array::value('title', $smartDebitRecord, 'NULL'), 'String'),
        2 => array(CRM_Utils_Array::value('first_name', $smartDebitRecord, 'NULL'), 'String'),
        3 => array(CRM_Utils_Array::value('last_name', $smartDebitRecord, 'NULL'), 'String'),
        4 => array(CRM_Utils_Array::value('email_address', $smartDebitRecord, 'NULL'),  'String'),
        5 => array(CRM_Utils_Array::value('address_1', $smartDebitRecord, 'NULL'), 'String'),
        6 => array(CRM_Utils_Array::value('address_2', $smartDebitRecord, 'NULL'), 'String') ,
        7 => array(CRM_Utils_Array::value('address_3', $smartDebitRecord, 'NULL'), 'String'),
        8 => array(CRM_Utils_Array::value('town', $smartDebitRecord, 'NULL'), 'String'),
        9 => array(CRM_Utils_Array::value('county', $smartDebitRecord, 'NULL'), 'String'),
        10 => array(CRM_Utils_Array::value('postcode', $smartDebitRecord, 'NULL'), 'String'),
        11 => array(CRM_Utils_Array::value('first_amount', $smartDebitRecord, 'NULL'), 'String'),
        12 => array(CRM_Utils_Array::value('default_amount', $smartDebitRecord, 'NULL'), 'String'),
        13 => array(CRM_Utils_Array::value('frequency_type', $smartDebitRecord, 'NULL'), 'String'),
        14 => array(CRM_Utils_Array::value('frequency_factor', $smartDebitRecord, 'NULL'), 'Int'),
        15 => array(CRM_Utils_Array::value('start_date', $smartDebitRecord, 'NULL'), 'String'),
        16 => array(CRM_Utils_Array::value('current_state', $smartDebitRecord, 'NULL'), 'Int'),
        17 => array(CRM_Utils_Array::value('reference_number', $smartDebitRecord, 'NULL'), 'String'),
        18 => array(CRM_Utils_Array::value('payerReference', $smartDebitRecord, 'NULL'), 'String'),
      );
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    $mandateFetchedCount = count($smartDebitPayerContactDetails);
    return $mandateFetchedCount;
  }

}
