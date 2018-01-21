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

