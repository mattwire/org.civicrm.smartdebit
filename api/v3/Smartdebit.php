<?php

/**
 * Smart Debit to CiviCRM Sync
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_smartdebit_sync($params) {
  $result = array();
  $runner = CRM_Smartdebit_Sync::getRunner(FALSE);
  if ($runner) {
    $result = $runner->runAll();
  }

  if ($result && !isset($result['is_error'])) {
    return civicrm_api3_create_success();
  }
  else {
    $msg = '';
    if (isset($result)) {
      $msg .= $result['exception']->getMessage() . '; ';
    }
    if (isset($result['last_task_title'])) {
      $msg .= $result['last_task_title'] .'; ';
    }
    return civicrm_api3_create_error($msg);
  }
}

function civicrm_api3_smartdebit_refreshsdmandatesincivi($params) {
  $mandateFetched = CRM_Smartdebit_Form_ReconciliationList::insertSmartDebitToTable();
  if (empty($mandateFetched)) {
    return civicrm_api3_create_error('No mandates fetched from smart debit');
  }
  return civicrm_api3_create_success(array('No of Records refreshed' => $mandateFetched), $params, 'Smartdebit', 'refreshsdmandatesincivi');
}
