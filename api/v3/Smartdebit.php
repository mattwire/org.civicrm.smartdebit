<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * Alias for Job.process_smartdebit
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_smartdebit_sync($params) {
  return civicrm_api3('Job', 'process_smartdebit', $params);
}

/**
 * Call this function to update "Smartdebit" recurring contributions in CiviCRM.
 * This makes no changes at Smartdebit itself, but it calls the updateRecurringContribution hook
 *   which may make changes at Smartdebit depending on your implementation
 * @param $params
 *
 * @return array
 */
function civicrm_api3_smartdebit_updaterecurring($params) {
  $params['trxn_id'] = CRM_Utils_Array::value('trxn_id', $params, []);
  if (isset($params['trxn_id']['IN'])) {
    $params['trxn_id'] = $params['trxn_id']['IN'];
  }
  elseif (!is_array($params['trxn_id'])) {
    $params['trxn_id'] = [$params['trxn_id']];
  }
  try {
    $stats = CRM_Smartdebit_Sync::updateRecurringContributions($params['trxn_id']);
    return $stats;
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

function _civicrm_api3_smartdebit_updaterecurring_spec(&$spec) {
  $spec['trxn_id']['api.required'] = 0;
  $spec['trxn_id']['title'] = 'Transaction ID / Reference Number';
  $spec['trxn_id']['description'] = 'The Smartdebit "Reference Number" / CiviCRM Transaction ID (eg. WEB00000123)';
  $spec['trxn_id']['type'] = CRM_Utils_Type::T_STRING;
}

/**
 * API Smartdebit.retrievemandates
 * @param $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_smartdebit_retrievemandates($params) {
  $count = CRM_Smartdebit_Mandates::retrieveAll();
  return ['count' => $count];
}

/**
 * API Smartdebit.getmandates
 * @param $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_smartdebit_getmandates($params) {
  if (empty($params['refresh'])) {
    $params['refresh'] = FALSE;
  }
  if (!isset($params['only_withrecurid'])) {
    $params['only_withrecurid'] = FALSE;
  }
  if (!isset($params['trxn_id'])) {
    $mandates = CRM_Smartdebit_Mandates::getAll($params['refresh'], $params['only_withrecurid']);
  }
  else {
    $mandate = CRM_Smartdebit_Mandates::getbyReference($params);
    $mandates = empty($mandate) ? [] : [$mandate];
  }

  return _civicrm_api3_basic_array_get('smartdebit', $params, $mandates, 'reference_number', []);
}

function _civicrm_api3_smartdebit_getmandates_spec(&$spec) {
  $spec['refresh']['api.required'] = 0;
  $spec['refresh']['title'] = 'Refresh from smartdebit';
  $spec['refresh']['description'] = 'If True, refresh from Smartdebit, otherwise load from local cache';
  $spec['refresh']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['only_withrecurid']['api.required'] = 0;
  $spec['only_withrecurid']['title'] = 'Only load mandates which have a recurring contribution ID';
  $spec['only_withrecurid']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['trxn_id']['api.required'] = 0;
  $spec['trxn_id']['title'] = 'Transaction ID / Reference Number';
  $spec['trxn_id']['description'] = 'The Smartdebit "Reference Number" / CiviCRM Transaction ID (eg. WEB00000123)';
  $spec['trxn_id']['type'] = CRM_Utils_Type::T_STRING;
}

/**
 * API Smartdebit.getmandatescount
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_smartdebit_getmandatescount($params) {
  return ['count' => CRM_Smartdebit_Mandates::count()];
}

/**
 * API Smartdebit.getcollectionreports
 *
 * @param $params
 *
 * @return array
 * @throws \API_Exception
 */
function civicrm_api3_smartdebit_retrievecollectionreports($params) {
  if (!isset($params['daily'])) {
    $params['daily'] = TRUE;
  }
  if (!isset($params['collection_date'])) {
    $params['collection_date'] = '';
  }
  if (!isset($params['collection_period'])) {
    $params['collection_period'] = CRM_Smartdebit_Settings::getValue('cr_cache');
  }

  if ($params['daily']) {
    $count = CRM_Smartdebit_CollectionReports::retrieveDaily($params['collection_date']);
  }
  else {
    $count = CRM_Smartdebit_CollectionReports::retrieveAll($params['collection_date'], $params['collection_period']);
  }
  return ['count' => $count];
}

function _civicrm_api3_smartdebit_retrievecollectionreports_spec(&$spec) {
  $spec['daily']['api.required'] = 1;
  $spec['daily']['title'] = 'Retrieve Daily Report Only';
  $spec['daily']['description'] = 'Whether to retrieve daily collection report or all (up to cache period)';
  $spec['daily']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['collection_date']['api.required'] = 0;
  $spec['collection_date']['title'] = 'Collection date (eg. 2010-02-12)';
  $spec['collection_date']['description'] = 'This is the final date for checking for collection reports, for daily it will go back 7 days, otherwise it will go back the amount specified in collection period.';
  $spec['collection_date']['type'] = CRM_Utils_Type::T_STRING;
  $spec['collection_period']['api.required'] = 0;
  $spec['collection_period']['title'] = 'Collection period';
  $spec['collection_period']['description'] = 'The period to retrieve collection reports for. Defaults to "-1 year". Must be valid per http://www.php.net/manual/en/datetime.formats.php';
  $spec['collection_period']['type'] = CRM_Utils_Type::T_STRING;
}

/**
 * API Smartdebit.getcollectionreportscount
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_smartdebit_getcollectionscount($params) {
  return ['count' => CRM_Smartdebit_CollectionReports::count()];
}

/**
 * API Smartdebit.getcollectionreports
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_smartdebit_getcollections($params) {
  if (isset($params['options'])) {
    $params['limit'] = CRM_Utils_Array::value('limit', $params['options'], 0);
    $params['offset'] = CRM_Utils_Array::value('offset', $params['options'], 0);
  }
  return ['reports' => CRM_Smartdebit_CollectionReports::get($params)];
}

function _civicrm_api3_smartdebit_getcollections_spec(&$spec) {
  $spec['successes']['api.required'] = 0;
  $spec['successes']['title'] = 'Get successful collections';
  $spec['successes']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['rejects']['api.required'] = 0;
  $spec['rejects']['title'] = 'Get rejected collections';
  $spec['rejects']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['trxn_id']['api.required'] = 0;
  $spec['trxn_id']['title'] = 'Transaction ID / Reference Number';
  $spec['trxn_id']['description'] = 'The Smartdebit "Reference Number" / CiviCRM Transaction ID (eg. WEB00000123)';
  $spec['trxn_id']['type'] = CRM_Utils_Type::T_STRING;
}

function civicrm_api3_smartdebit_processcollections($params) {
  if (!empty($params['rejects'])) {
    return civicrm_api3_create_error('NOT IMPLEMENTED: Process rejected collections');
  }
  $smartDebitPayments = CRM_Smartdebit_CollectionReports::get($params);

  // Import each transaction from smart debit
  foreach ($smartDebitPayments as $key => $sdPayment) {
    $contributionIds[] = CRM_Smartdebit_Sync::processCollection($sdPayment['transaction_id'], $sdPayment['receive_date'], $sdPayment['amount'], CRM_Smartdebit_CollectionReports::TYPE_COLLECTION);
  }
  return ['Contribution IDs' => $contributionIds];
}

function _civicrm_api3_smartdebit_processcollections_spec(&$spec) {
  $spec['successes']['api.required'] = 0;
  $spec['successes']['title'] = 'Process successful collections';
  $spec['successes']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['rejects']['api.required'] = 0;
  $spec['rejects']['title'] = 'Process rejected collections (NOT IMPLEMENTED)';
  $spec['rejects']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['trxn_id']['api.required'] = 0;
  $spec['trxn_id']['title'] = 'Process collection with Transaction ID / Reference Number';
  $spec['trxn_id']['description'] = 'The Smartdebit "Reference Number" / CiviCRM Transaction ID (eg. WEB00000123)';
  $spec['trxn_id']['type'] = CRM_Utils_Type::T_STRING;
}

function civicrm_api3_smartdebit_clearcache($params) {
  civicrm_api3_verify_mandatory($params, NULL, [['mandates', 'collections']]);

  if (!empty($params['mandates'])) {
    CRM_Smartdebit_Mandates::delete();
  }
  if (!empty($params['collections'])) {
    CRM_Smartdebit_CollectionReports::delete();
  }

  return TRUE;
}

function _civicrm_api3_smartdebit_clearcache_spec(&$spec) {
  $spec['mandates']['api.required'] = 0;
  $spec['mandates']['title'] = 'Clear cached mandates';
  $spec['mandates']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['collections']['api.required'] = 0;
  $spec['collections']['title'] = 'Clear cached collections';
  $spec['collections']['type'] = CRM_Utils_Type::T_BOOLEAN;
}
