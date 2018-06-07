<?php
/*--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +-------------------------------------------------------------------*/

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
  return civicrm_api3_job_process_smartdebit($params);
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
  try {
    CRM_Smartdebit_Sync::updateRecurringContributions();
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
  return civicrm_api3_create_success();
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
  return array('count' => $count);
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
    $mandates = array(CRM_Smartdebit_Mandates::getbyReference($params['trxn_id'], $params['refresh']));
  }

  return _civicrm_api3_basic_array_get('smartdebit', $params, $mandates, 'reference_number', array());
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
  return array('count' => CRM_Smartdebit_Mandates::count());
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
  return array('count' => $count);
}

function _civicrm_api3_smartdebit_retrievecollectionreports_spec(&$spec) {
  $spec['daily']['api.required'] = 1;
  $spec['daily']['title'] = 'Retrieve Daily Report Only';
  $spec['daily']['description'] = 'Whether to retrieve daily collection report or all (up to cache period)';
  $spec['daily']['type'] = CRM_Utils_Type::T_BOOLEAN;
  $spec['collection_date']['api.required'] = 0;
  $spec['collection_date']['title'] = 'Collection date (eg. 2010-02-12)';
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
function civicrm_api3_smartdebit_getcollectionreportscount($params) {
  return array('count' => CRM_Smartdebit_CollectionReports::count());
}

/**
 * API Smartdebit.getcollectionreports
 *
 * @param $params
 *
 * @return array
 */
function civicrm_api3_smartdebit_getcollectionreports($params) {
  if (isset($params['options'])) {
    $params['limit'] = CRM_Utils_Array::value('limit', $params['options'], 0);
    $params['offset'] = CRM_Utils_Array::value('offset', $params['options'], 0);
  }
  return array('reports' => CRM_Smartdebit_CollectionReports::get($params));
}

