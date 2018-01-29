<?php

/**
 * Class CRM_Smartdebit_CollectionReports
 * This class handles all the collection reports from Smartdebit
 */
class CRM_Smartdebit_CollectionReports {

  const TABLENAME='veda_smartdebit_collectionreports';
  const COLLECTION_REPORT_BACKTRACK_DAYS = 7;

  /**
   * Function to get the retrived collection report count
   *
   * @return int
   */
  public static function count() {

    $count = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM `" . self::TABLENAME . "`");
    return $count;
  }

  /**
   * Batch task to retrieve daily collection reports
   */
  public static function retrieveDaily() {
    // FIXME: We should probably retry if this fails, but there is not a report for every day so would need to handle that too.
    // Get collection report for today
    Civi::log()->info('Smartdebit Sync: Retrieving Daily Collection Report.');

    // Collection report is available only after 3 days
    // So we will not get any results if we check for the current date
    // Hence checking collection report for the past 7 days
    $backtrackDays = self::COLLECTION_REPORT_BACKTRACK_DAYS;
    for ($i = 0; $i < $backtrackDays; $i++) {
      $date = (new \DateTime())->modify("-{$i} day");
      $collections = CRM_Smartdebit_Api::getCollectionReport($date->format('Y-m-d'));
      if (!isset($collections['error'])) {
        self::save($collections);
      }
    }
  }

  /**
   * Function to get the all available payments in the collection reports
   *
   * @return array
   */
  public static function getallPayments() {
    $sql = "SELECT * FROM `" . self::TABLENAME . "`";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $allPayments = array();
    while ($dao->fetch()) {
      $payment = $dao->toArray();
      $payment['receive_date'] = date('Y-m-d', strtotime($payment['receive_date']));
      $allPayments[] = $payment;
    }
    return $allPayments;
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
   * @param $collection
   *
   * @return bool
   */
  private static function exists($collection) {
    $whereClause = '';
    foreach ($collection as $key => $value) {
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
   * This gets all the collection reports for the time period ending $dateOfCollection and starting 'cr_cache' days/months earlier
   *
   * @param $dateOfCollection
   * @return array
   */
  public static function getAll($dateOfCollection) {
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
    $dateStart->modify(CRM_Smartdebit_Settings::getValue('cr_cache'));
    $dateCurrent = clone $dateEnd;

    // Iterate back one day at a time requesting reports
    while ($dateCurrent > $dateStart) {
      $newCollections = CRM_Smartdebit_Api::getCollectionReport($dateCurrent->format('Y-m-d'));
      if (!isset($newCollections['error'])) {
        $collections = array_merge($collections, $newCollections);
      }
      $dateCurrent->modify('-1 day');
    }
    return $collections;
  }

}