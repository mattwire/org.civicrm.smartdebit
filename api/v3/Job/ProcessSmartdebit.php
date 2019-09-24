<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Smart Debit to CiviCRM Sync
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_job_process_smartdebit($params) {
  $runner = CRM_Smartdebit_Sync::getRunner(FALSE);
  if ($runner) {
    $result = $runner->runAll();
  }
  else {
    return civicrm_api3_create_error('No records could be matched to collection reports.  If you have collection reports in Smartdebit try a manual sync or reconcile.');
  }

  if ($result && !isset($result['is_error'])) {
    return civicrm_api3_create_success();
  }
  else {
    $msg = '';
    if (!empty($result['exception'])) {
      $msg .= $result['exception']->getMessage() . '; ';
    }
    if (!empty($result['last_task_title'])) {
      $msg .= $result['last_task_title'] .'; ';
    }
    return civicrm_api3_create_error($msg);
  }

}
