<?php

/**
 * Class CRM_Smartdebit_CollectionReports
 * This class handles all the collection reports from Smartdebit
 */
class CRM_Smartdebit_CollectionReports {

  const TABLENAME='veda_smartdebit_collectionreports';
  const COLLECTION_REPORT_BACKTRACK_DAYS = 7;

  /**
   * Function to get the retrieved collection report count
   *
   * @return int
   */
  public static function count($params = array()) {
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

    $sql .= self::limitClause($params);
    $sql .= self::whereClause($params);

    $dao = CRM_Core_DAO::executeQuery($sql);
    $collectionReports = array();
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
    if(!empty($collections)){
      foreach ($collections as $key => $value) {
        $resultCollection = array(
          'transaction_id' => $value['reference_number'],
          'contact'        => $value['account_name'],
          'contact_id'     => $value['customer_id'],
          'amount'         => $value['amount'],
          'receive_date'   => !empty($value['debit_date']) ? date('YmdHis', strtotime(str_replace('/', '-', $value['debit_date']))) : NULL,
        );
        // Don't add collection report to cache more than once
        if (!self::exists($resultCollection)) {
          $insertValue[] = " ( \"" . implode('", "', $resultCollection) . "\" )";
        }
      }

      if (isset($insertValue)) {
        $sql = " INSERT INTO `" . self::TABLENAME . "`
              (`transaction_id`, `contact`, `contact_id`, `amount`, `receive_date`)
              VALUES " . implode(', ', $insertValue) . "
              ";
        CRM_Core_DAO::executeQuery($sql);
      }
    }
  }

  /**
   * Check if we already have a saved collection report
   *
   * @param $collectionReport
   *
   * @return bool
   */
  private static function exists($collectionReport) {
    $whereClause = '';
    foreach ($collectionReport as $key => $value) {
      if (!empty($value)) {
        $where[] = "{$key}='{$value}'";
      }
    }
    if (isset($where)) {
      $whereClause = 'WHERE ' . implode(' AND ', $where);
    }

    $sql = "SELECT id FROM `" . self::TABLENAME . "` {$whereClause}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Remove all collection report date from veda_smartdebit_collectionreports that is older than cache time
   *
   * @return bool
   */
  public static function removeOld() {
    $date = new DateTime();
    $date->modify(CRM_Smartdebit_Settings::getValue('cr_cache'));
    $dateString = $date->format('Ymd') . '000000';
    $query = "DELETE FROM `veda_smartdebit_collectionreports` WHERE receive_date < %1";
    $params = array(1 => array($dateString, 'String'));
    CRM_Core_DAO::executeQuery($query, $params);
    return TRUE;
  }

  /**
   * Batch task to retrieve daily collection reports
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
    Civi::log()->info('Smartdebit Sync: Retrieving Daily Collection Report.');

    $dateCurrent = new \DateTime($collectionDate);

    // Collection report is available only after 3 days
    // So we will not get any results if we check for the current date
    // Hence checking collection report for the past 7 days
    $backtrackDays = self::COLLECTION_REPORT_BACKTRACK_DAYS;
    $count = 0;
    for ($i = 0; $i < $backtrackDays; $i++) {
      $dateCurrent->modify("-1 day");
      $collectionReports = CRM_Smartdebit_Api::getCollectionReport($dateCurrent->format('Y-m-d'));
      if (!isset($collectionReports['error'])) {
        // Save the retrieved collection reports
        CRM_Smartdebit_CollectionReports::save($collectionReports);
        $count += count($collectionReports);
      }
    }
    return $count;
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
    // Empty the collection reports table
    $emptySql = "TRUNCATE TABLE veda_smartdebit_collectionreports";
    CRM_Core_DAO::executeQuery($emptySql);

    // Get a collection report for every day of the month
    $dateEnd = new DateTime($collectionDate);
    $dateStart = clone $dateEnd;
    $dateStart->modify($period);
    $dateCurrent = clone $dateEnd;

    // Iterate back one day at a time requesting reports
    $count = 0;
    while ($dateCurrent > $dateStart) {
      $collectionReports = CRM_Smartdebit_Api::getCollectionReport($dateCurrent->format('Y-m-d'));
      if (!isset($collectionReports['error'])) {
        // Save the retrieved collection reports
        CRM_Smartdebit_CollectionReports::save($collectionReports);
        $count += count($collectionReports);
      }
      $dateCurrent->modify('-1 day');
    }
    return $count;
  }

  /**
   * Add optional limits
   *
   * @param $params
   *
   * @return string
   */
  private static function limitClause($params) {
    $limitClause = '';

    if (!empty($params['limit'])) {
      $limitClause .= ' LIMIT ' . $params['limit'];
    }
    if (!empty($params['offset'])) {
      $limitClause .= ' OFFSET ' . $params['offset'];
    }
    return $limitClause;
  }

  /**
   * Add option where clause
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
      $whereClause .= " WHERE success = 1";
    }
    elseif (!$params['successes'] && $params['rejects']) {
      $whereClause .= " WHERE success = 0";
    }
    return $whereClause;
  }

}