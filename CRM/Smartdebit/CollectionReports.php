<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_CollectionReports
 * This class handles all the collection reports from Smartdebit
 */
class CRM_Smartdebit_CollectionReports {

  const TABLENAME='veda_smartdebit_collections';
  const TABLESUMMARY='veda_smartdebit_collectionreportsummary';
  const COLLECTION_REPORT_BACKTRACK_DAYS = 7;

  const TYPE_COLLECTION=0;
  const TYPE_AUDDIS=1;
  const TYPE_ARUDD=2;

  /**
   * Function to get the retrieved collection report count
   *
   * @return int Number of collection reports
   */
  public static function count($params = []) {
    $sql = "SELECT count(*) FROM `" . self::TABLENAME . "`";
    $sql .= self::whereClause($params);

    $count = CRM_Core_DAO::singleValueQuery($sql);
    return $count;
  }

  /**
   * Function to get all available payments in the collection reports
   *
   * @param $params (limit => 0, offset => 0)
   *
   * @return array
   */
  public static function get($params) {
    $sql = "SELECT * FROM `" . self::TABLENAME . "`";

    $sql .= self::whereClause($params);
    $sql .= CRM_Smartdebit_Base::limitClause($params);

    $dao = CRM_Core_DAO::executeQuery($sql);
    $collectionReports = [];
    while ($dao->fetch()) {
      $payment = $dao->toArray();
      $payment['receive_date'] = date('Y-m-d', strtotime($payment['receive_date']));
      $collectionReports[] = $payment;
    }
    return $collectionReports;
  }

  /**
   * Save collection report (getSmartdebitCollectionReport) to database
   *
   * @param $collections
   */
  public static function save($collections) {
    if (!empty($collections)) {
      foreach ($collections as $key => $value) {
        $collectionValues = [
          'transaction_id' => '"' . CRM_Utils_Array::value('reference_number', $value) . '"',
          'contact' => '"' . CRM_Utils_Array::value('account_name', $value) . '"',
          'contact_id' => '"' . CRM_Utils_Array::value('customer_id', $value) . '"',
          'amount' => CRM_Utils_Array::value('amount', $value),
          'receive_date' => date('YmdHis', strtotime(str_replace('/', '-', CRM_Utils_Array::value('debit_date', $value)))),
          'error_message' => '"' . CRM_Utils_Array::value('error_message', $value) . '"',
          'success' => CRM_Utils_Array::value('success', $value),
        ];

        $sql = "
INSERT INTO " . self::TABLENAME . "
  (`transaction_id`, `contact`, `contact_id`, `amount`, `receive_date`, `error_message`, `success`)
VALUES (" . implode(', ', $collectionValues) . ")
ON DUPLICATE KEY UPDATE
  transaction_id = VALUES(transaction_id),
  contact = VALUES(contact),
  contact_id = VALUES(contact_id),
  amount = VALUES(amount),
  receive_date = VALUES(receive_date),
  error_message = VALUES(error_message),
  success = VALUES(success)
  ";
        CRM_Core_DAO::executeQuery($sql);
      }
    }
  }

  /**
   * Delete collections and collection reports(s) from CiviCRM
   */
  public static function delete() {
    // if the civicrm_sd table exists, then empty it
    $sql = "TRUNCATE TABLE `" . self::TABLENAME . "`";
    CRM_Core_DAO::executeQuery($sql);
    $sql = "TRUNCATE TABLE `" . self::TABLESUMMARY . "`";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Function to get the retrieved collection report count
   *
   * @return int Number of collection reports
   */
  public static function countReports() {
    $sql = "SELECT count(*) FROM `" . self::TABLESUMMARY . "`";

    $count = CRM_Core_DAO::singleValueQuery($sql);
    return $count;
  }

  /**
   * Function to get all available payments in the collection reports
   *
   * @param $params (limit => 0, offset => 0)
   *
   * @return array
   */
  public static function getReports($params) {
    $sql = "SELECT * FROM `" . self::TABLESUMMARY . "`";
    $sql .= " ORDER BY collection_date DESC";
    $sql .= CRM_Smartdebit_Base::limitClause($params);

    $dao = CRM_Core_DAO::executeQuery($sql);
    $collectionReports = [];
    while ($dao->fetch()) {
      $payment = $dao->toArray();
      $payment['collection_date'] = date('Y-m-d', strtotime($payment['collection_date']));
      $collectionReports[] = $payment;
    }
    return $collectionReports;
  }

  /**
   * Save details of collection report
   *
   * @param $summary
   *
   * @return bool
   */
  public static function saveReport($summary) {
    if (empty($summary)) {
      return FALSE;
    }
    /*array (
      'CollectionDate' => '01/06/2018',
      'Succesful' =>
        array (
          '@attributes' =>
            array (
              'amount_submitted' => '69371.29',
              'number_submitted' => '11751',
            ),
        ),
      'Rejected' =>
        array (
          '@attributes' =>
            array (
              'amount_rejected' => '536.00',
              'number_rejected' => '73',
            ),
        ),
    )*/

    $summaryValues = [
      1 => date('YmdHis', strtotime(str_replace('/', '-', CRM_Utils_Array::value('CollectionDate', $summary)))),
      2 => CRM_Utils_Array::value('amount_submitted', $summary['Succesful']['@attributes']),
      3 => CRM_Utils_Array::value('number_submitted', $summary['Succesful']['@attributes']),
      4 => CRM_Utils_Array::value('amount_rejected', $summary['Rejected']['@attributes']),
      5 => CRM_Utils_Array::value('number_rejected', $summary['Rejected']['@attributes']),
    ];

    $sql = "
INSERT INTO " . self::TABLESUMMARY . "
  (collection_date, success_amount, success_number, reject_amount, reject_number)
VALUES (" . implode(', ', $summaryValues) . ")
ON DUPLICATE KEY UPDATE
  collection_date = VALUES(collection_date),
  success_amount = VALUES(success_amount),
  success_number = VALUES(success_number),
  reject_amount = VALUES(reject_amount),
  reject_number = VALUES(reject_number)
  ";

    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Remove all collection report date from veda_smartdebit_collections that is older than cache time
   *
   * @return bool
   */
  public static function removeOld() {
    $date = new DateTime();
    $date->modify(CRM_Smartdebit_Settings::getValue('cr_cache'));
    $dateString = $date->format('Ymd') . '000000';
    $query = "DELETE FROM `" . self::TABLENAME . "` WHERE receive_date < %1";
    $params = [1 => [$dateString, 'String']];
    CRM_Core_DAO::executeQuery($query, $params);
    return TRUE;
  }

  /**
   * Function to retrieve daily collection reports between two dates
   *
   * @param DateTime $dateStart
   * @param DateTime $dateEnd
   *
   * @return int
   * @throws \Exception
   */
  private static function retrieve($dateStart, $dateEnd) {
    $dateCurrent = clone $dateEnd;
    $count = 0;
    while ($dateCurrent > $dateStart) {
      $collections = CRM_Smartdebit_Api::getCollectionReport($dateCurrent->format('Y-m-d'));
      if (!isset($collections['error'])) {
        // Save the retrieved collection reports
        CRM_Smartdebit_CollectionReports::save($collections);
        $newCount = count($collections);
        CRM_Smartdebit_Utils::log('Smartdebit: Retrieved collection report for ' . $dateCurrent->format('Y-m-d') . ' with ' . $newCount . ' collections.', TRUE);
        $count += $newCount;
      }
      $dateCurrent->modify("-1 day");
    }
    return $count;
  }

  /**
   * Function to retrieve daily collection reports
   *  This will retrieve all collection reports for the past (COLLECTION_REPORT_BACKTRACK_DAYS default=7) days.
   *  This allows us to handle server outages and collection reports only available after certain number of days.
   *
   * @param string $collectionDate - if empty it will be set to todays date.
   *
   * @return int $count Number of retrieved collection reports
   * @throws \Exception
   */
  public static function retrieveDaily($collectionDate = NULL) {
    // Get collection report for today

    $dateEnd = new \DateTime($collectionDate);
    $dateStart = clone $dateEnd;

    Civi::log()->info('Smartdebit Sync: Retrieving Daily Collection Report for ' . SELF::COLLECTION_REPORT_BACKTRACK_DAYS . ' days to '. $dateEnd->format('Y-m-d'));

    // Collection report is available only after 3 days
    // So we will not get any results if we check for the current date
    // Hence checking collection report for the past 7 days
    $dateStart->modify('-' . self::COLLECTION_REPORT_BACKTRACK_DAYS . ' day');

    return self::retrieve($dateStart, $dateEnd);
  }

  /**
   * This gets all the collection reports for the time period ending $dateOfCollection and starting 'cr_cache' days/months earlier
   *
   * @param string $collectionDate - if empty it will be set to todays date.
   * @param string $period
   *   Period to retrieve reports for (eg. "-1 year"). Must be a valid format per: http://www.php.net/manual/en/datetime.formats.php Default value is '-1 year'
   *
   * @return int $count Number of retrieved collection reports
   * @throws \Exception
   */
  public static function retrieveAll($collectionDate, $period) {
    Civi::log()->info('Smartdebit Sync: Retrieving ALL Collection Reports up to ' . $collectionDate . ' for period: ' . $period);

    // Empty the collection reports table
    $emptySql = "TRUNCATE TABLE " . self::TABLENAME;
    CRM_Core_DAO::executeQuery($emptySql);

    // Get a collection report for every day of the month
    $dateEnd = new \DateTime($collectionDate);
    $dateStart = clone $dateEnd;
    $dateStart->modify($period);

    // Iterate back one day at a time requesting reports
    return self::retrieve($dateStart, $dateEnd);
  }

  /**
   * Add optional where clause
   *
   * @param $params
   *
   * @return string
   */
  private static function whereClause($params) {
    $params['successes'] = CRM_Utils_Array::value('successes', $params, TRUE);
    $params['rejects'] = CRM_Utils_Array::value('rejects', $params, FALSE);
    $whereClause = '';
    if ($params['successes'] && !$params['rejects']) {
      $whereClauses[] = "success = 1";
    }
    elseif (!$params['successes'] && $params['rejects']) {
      $whereClauses[] = "success = 0";
    }

    if (!empty($params['trxn_id'])) {
      $whereClauses[] = 'transaction_id="' . $params['trxn_id'] . '"';
    }

    if (!empty($whereClauses)) {
      $whereClause = 'WHERE ' . implode(' AND ', $whereClauses);
    }

    return $whereClause;
  }

}
