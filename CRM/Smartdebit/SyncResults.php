<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Class CRM_Smartdebit_CollectionReports
 * This class handles all the collection reports from Smartdebit
 */
class CRM_Smartdebit_SyncResults {

  const TABLENAME='veda_smartdebit_syncresults';

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
    $sql .= self::limitClause($params);

    $dao = CRM_Core_DAO::executeQuery($sql);
    $syncResults = [];
    while ($dao->fetch()) {
      $payment = $dao->toArray();
      $payment['receive_date'] = date('Y-m-d', strtotime($payment['receive_date']));
      $syncResults[] = $payment;
    }
    return $syncResults;
  }

  /**
   * Save results of sync
   *
   * @param array $values
   * @param int $type
   */
  public static function save($values, $type) {
    if (empty($values) || $type < 0 || $type > 2) {
      return;
    }

    $resultValues = [
      'type' => $type,
      'transaction_id' => '"' . CRM_Utils_Array::value('transaction_id', $values) . '"',
      'contribution_id' => CRM_Utils_Array::value('contribution_id', $values),
      'contact_id' => CRM_Utils_Array::value('contact_id', $values),
      'contact_name' => '"' . CRM_Utils_Array::value('contact_name', $values) . '"',
      'amount' => CRM_Utils_Array::value('amount', $values),
      'frequency' => '"' . CRM_Utils_Array::value('frequency', $values) . '"',
      'receive_date' => CRM_Utils_Array::value('receive_date', $values),
    ];
    $sql = "
INSERT INTO `" . self::TABLENAME . "`(
  `type`,
  `transaction_id`,
  `contribution_id`,
  `contact_id`,
  `contact_name`,
  `amount`,
  `frequency`,
  `receive_date`
)
VALUES (" . implode(', ', $resultValues) . ")
    ";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Delete results
   */
  public static function delete() {
    // if the civicrm_sd table exists, then empty it
    $sql = "TRUNCATE TABLE `" . self::TABLENAME . "`";
    CRM_Core_DAO::executeQuery($sql);
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
   * Add optional where clause
   *
   * @param $params
   *
   * @return string
   */
  private static function whereClause($params) {
    $params['arudd'] = CRM_Utils_Array::value('arudd', $params, FALSE);
    $params['auddis'] = CRM_Utils_Array::value('auddis', $params, FALSE);
    $params['collections'] = CRM_Utils_Array::value('collections', $params, FALSE);
    $whereClause = '';
    if (!$params['arudd'] && !$params['auddis'] && !$params['collections']) {
      // Return all results if none specified
      return $whereClause;
    }
    if ($params['arudd']) {
      $whereClauses[] = 'type=' . CRM_Smartdebit_CollectionReports::TYPE_ARUDD;
    }
    if ($params['auddis']) {
      $whereClauses[] = 'type=' . CRM_Smartdebit_CollectionReports::TYPE_AUDDIS;
    }
    if ($params['collections']) {
      $whereClauses[] = 'type=' . CRM_Smartdebit_CollectionReports::TYPE_COLLECTION;
    }
    $whereClause = 'WHERE ' . implode(" OR ", $whereClauses);
    return $whereClause;
  }

}
