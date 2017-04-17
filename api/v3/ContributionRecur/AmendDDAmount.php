<?php
// TODO: Is any of this used?
/**
 * ContributionRecur.AmendDDAmount API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_contribution_recur_amendddamount_spec(&$spec) {
  $spec['contact_id']['api.required']            = 1;
  $spec['contribution_recur_id']['api.required'] = 1;
  $spec['amount']['api.required']                = 1;
}

/**
 * ContributionRecur.AmendDDAmount API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_contribution_recur_amendddamount($params) {
  if (  array_key_exists( 'contact_id'           , $params ) &&
        array_key_exists( 'amount'               , $params ) &&
        array_key_exists( 'contribution_recur_id', $params )
     ) {
    $iContactId           = $params['contact_id'];
    $iContributionRecurId = $params['contribution_recur_id'];
    $iAmount              = $params['amount'];

    $iContributionPageId  = null;
    $iRelatedContactId    = null;
    $iOnBehalfDupeAlert   = null;

    $aContribParam[ 'contactID'           ] = $iContactId;
    $aContribParam[ 'contributionRecurID' ] = $iContributionRecurId;
    $aContribParam[ 'contributionPageID'  ] = $iContributionPageId;
    $aContribParam[ 'relatedContactID'    ] = $iRelatedContactID;
    $aContribParam[ 'onBehalfDupeAlert'   ] = $iOnBehalfDupeAlert;

    $aParams = array( 'version'    => '3'
                    , 'sequential' => '1'
                    , 'contact_id' => $iContactId
                    );
    $aResult = civicrm_api( 'Membership'
                          , 'getsingle'
                          , $aParams
                          );
    if ( civicrm_error( $aResult ) ) {
      $sMsg = "Error locating Membership record for contact id {$iContactId}" ;
      CRM_Core_Error::debug_log_message( $sMsg );
      return civicrm_api3_create_error( $sMsg );
    }
    $aContribParam[ 'membershipID' ] = $aResult['membership_id'];

    $aParams = array( 'version'    => '3'
                    , 'sequential' => '1'
                    , 'contribution_recur_id' => $iContributionRecurId
                    );
    $aResult = civicrm_api( 'Contribution'
                          , 'getsingle'
                          , $aParams
                          );
    if ( civicrm_error( $aResult ) ) {
      $sMsg = "Error locating Contribution record for contribution_recur_id {$iContributionRecurId}" ;
      CRM_Core_Error::debug_log_message( $sMsg );
      return civicrm_api3_create_error( $sMsg );
    }
    $aContribParam[ 'contributionID' ] = $aResult['id'];


    $SmartDebitIPN = new CRM_Core_Payment_SmartDebitIPN();
    $oResult       = $SmartDebitIPN->main( 'contribute', $aContribParam );

    if ( $oResult === false ) {
      $sMsg = "Error when changing DD Amount using Smart Debit." ;
      CRM_Core_Error::debug_log_message( $sMsg );
      return civicrm_api3_create_error( $sMsg );
    }

    $aParams = array( 'version'    => '3'
                    , 'sequential' => '1'
                    , 'id'         => $iContributionRecurId
                    , 'amount'     => $iAmount
                    );
    $aResult = civicrm_api( 'ContributionRecur'
                          , 'update'
                          , $aParams
                          );
    if ( civicrm_error( $aResult ) ) {
      $sMsg = "Error when updating the Amount in ContributionRecur." ;
      CRM_Core_Error::debug_log_message( $sMsg );
      return civicrm_api3_create_error( $sMsg );
    }

    return civicrm_api3_create_success();
  } else {
    throw new API_Exception( 'Missing Mandatory parameters: contact_id, contribution_id, and contribution_recur_id' );
  }
}
