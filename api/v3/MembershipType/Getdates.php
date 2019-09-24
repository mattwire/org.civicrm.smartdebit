<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * API to Create or update a Membership Type.
 *
 * @param array $params
 *   Array of name/value property values of civicrm_membership_type.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_membership_type_getdates($params) {
  if (empty($params['num_renew_terms'])) {
    $params['num_renew_terms'] = 1;
  }
  $optionalFields = ['join_date', 'start_date', 'end_date', 'change_today'];
  foreach ($optionalFields as $field) {
    if (!isset($params[$field])) {
      $params[$field] = NULL;
    }
  }
  if (empty($params['membership_id'])) {
    civicrm_api3_verify_mandatory($params, NULL, ['membershiptype_id']);
    $dates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($params['membershiptype_id'], $params['join_date'], $params['start_date'], $params['end_date'], $params['num_renew_terms']);
  }
  else {
    civicrm_api3_verify_mandatory($params, NULL, ['membership_id']);
    $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($params['membership_id'], $params['change_today'], $params['membership_type_id'], $params['num_renew_terms']);
  }

  return $dates;

}

/**
 * Adjust Metadata for Create action.
 *
 * The metadata is used for setting defaults, documentation & validation.
 *
 * @param array $params
 *   Array of parameters determined by getfields.
 */
function _civicrm_api3_membership_type_getdates_spec(&$params) {
  $params['membershiptype_id'] = [
    'title' => 'Membership Type ID',
    'api.required' => 0,
    'FKClassName' => 'CRM_Member_BAO_MembershipType',
    'FKApiName' => 'MembershipType',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['membership_id'] = [
    'title' => 'Membership ID',
    'description' => 'If specified, renewal dates will be calculated',
    'api.required' => 0,
    'FKClassName' => 'CRM_Member_BAO_Membership',
    'FKApiName' => 'Membership',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['join_date'] = [
    'title' => 'Join Date (optional)',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $params['start_date'] = [
    'title' => 'Start Date (optional)',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $params['end_date'] = [
    'title' => 'End Date (optional)',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $params['num_renew_terms'] = [
    'title' => 'Number of renewal terms',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $params['change_today'] = [
    'title' => 'Change "Today" to a different date for calculation',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
}
