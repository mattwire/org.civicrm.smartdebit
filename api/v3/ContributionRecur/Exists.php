<?php
// TODO: Is any of this used?
/**
 * ContributionRecur.Exists API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_contribution_recur_exists_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
}

/**
 * ContributionRecur.Exists API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contribution_recur_exists( $params ) {
  if ( array_key_exists( 'contact_id', $params )  ) {
    $aOptions = array( 'sort'    => 'id DESC'
                     , 'limit'  => 1
                     );
    $aResults = civicrm_api( "ContributionRecur"
                           , "get"
                           , array( 'version'    => '3'
                                  , 'q'          => 'civicrm/ajax/rest'
                                  , 'sequential' => '1'
                                  , 'contact_id' => $params['contact_id']
                                  , 'is_active'  => '1'
                                  , 'options'    => $aOptions
                                  )
                           );
    if ( $aResults['is_error'] == 1 ) {
      CRM_Core_Error::debug_log_message( "Error:{$aResults['error_message']}"  );
    }


    // ALTERNATIVE: $returnValues = array(); // OK, success
    // ALTERNATIVE: $returnValues = array("Some value"); // OK, return a single value

    // Spec: civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL)
    //return civicrm_api3_create_success( $returnValues, $params, 'NewEntity', 'NewAction' );
    return civicrm_api3_create_success( $aResults['values'][0] );
  } else {
    throw new API_Exception( 'Missing Contact ID' );
  }

}
