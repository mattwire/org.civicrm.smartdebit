<?php
/**
 * https://civicrm.org/licensing
 */

class CRM_Smartdebit_Utils {

  public static $url = 'civicrm/smartdebit';
  public static $reconcileUrl = 'civicrm/smartdebit/reconciliation';

  /**
   * Get all memberships for a contact (or membership specified by membershipID)
   * @param $contactId
   * @param null $membershipId
   * @return null
   */
  static function getContactMemberships($contactId, $membershipId = NULL) {
    // Get memberships for contact
    $memberParams['contact_id'] = $contactId;
    if (!empty($membershipId)) {
      $memberParams['id'] = $membershipId;
    }

    $memberships = civicrm_api3('Membership', 'get', [
      'contact_id' => $contactId,
    ]);

    $membershipOptions = [];

    // If we want a list of memberships, add the donation (no membership) option
    if (empty($membershipId)) {
      $membershipOptions['donation'] = 'Donation';
    }

    // If no memberships for contact...
    if (empty($memberships['count'])) {
      if (!empty($membershipId)) {
        // We wanted a specific membership but couldn't find it
        return NULL;
      }
      // Return the donation option.
      return $membershipOptions;
    }

    $membershipDetails = $memberships['values'];

    // Build membershipOptions array
    foreach ($membershipDetails as $mId => $detail) {
      if(!empty( $detail['start_date'] )) {
        $start_date = date( 'Y-m-d', strtotime($detail['start_date']));
      } else {
        $start_date = "Null";
      }
      if (!empty($detail['end_date'])) {
        $end_date = date( 'Y-m-d', strtotime($detail['end_date']));
      } else {
        $end_date = "Null";
      }
      $type = CRM_Member_PseudoConstant::membershipType($detail['membership_type_id']);
      $status = CRM_Member_PseudoConstant::membershipStatus($detail['status_id']);

      if (!empty($membershipId)) {
        if ($mId == $membershipId) {
          // Found our membership, set the details and return
          $membershipOptions['id'] = $detail['id'];
          $membershipOptions['start_date'] = $start_date;
          $membershipOptions['end_date'] = $end_date;
          $membershipOptions['type'] = $type;
          $membershipOptions['status'] = $status;
          return $membershipOptions;
        }
      }
      else {
        // We just return a description of the membership for selection
        // Add description to list of memberships
        $membershipOptions[$detail['id']] = $type.'/'.$status.'/'.$start_date.'/'.$end_date;
      }
    }
    return $membershipOptions;
  }

  /**
   * Return the first contribution record for recurring contribution with given ID
   * @param $cRecurID
   * @return mixed
   */
  static function getContributionRecordForRecurringContribution($cRecurID) {
    $contributionParams = [
      'version'               => 3,
      'sequential'            => 1,
      'contribution_recur_id' => $cRecurID,
      'options' => ['sort' => "receive_date DESC"],
    ];
    $contributionRecords = civicrm_api('Contribution', 'get', $contributionParams);
    if (!empty($contributionRecords['is_error']) && $contributionRecords['count'] > 0) {
      // This will always return the most recent contribution
      return $contributionRecords['values'][0];
    }
    return NULL;
  }

  /**
   * Get list of recurring contribution records for contact
   * @param $contactID
   * @return mixed
   */
  static function getContactRecurringContributions($contactID) {
    // Get recurring contributions by contact Id
    $contributionRecurRecords = civicrm_api3('ContributionRecur', 'get', [
      'sequential' => 1,
      'contact_id' => $contactID,
      'options' => ['limit' => 0],
      'return' => ["payment_processor_id", "contribution_status_id", "amount", "trxn_id"],
    ]);
    // Get contribution Status options
    $contributionStatusOptions = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');

    foreach ($contributionRecurRecords['values'] as $contributionRecur) {
      // Get payment processor name used for recurring contribution
      $paymentProcessorName = CRM_Core_Payment_Smartdebit::getSmartDebitPaymentProcessorName($contributionRecur['payment_processor_id']);
      $contributionStatus = $contributionStatusOptions[$contributionRecur['contribution_status_id']];
      // Create display name for recurring contribution
      $cRecur[$contributionRecur['id']] = $paymentProcessorName.'/'.$contributionStatus.'/'.$contributionRecur['amount'].'/'.$contributionRecur['trxn_id'];
    }
    $cRecur['new_recur'] = 'Create New Recurring';
    return $cRecur;
  }

  /**
   * Get recurring contribution record by recur ID
   * @param $cRecurID
   * @return array
   */
  static function getRecurringContributionRecord($cRecurID) {
    $cRecurParams = [
      'version'     => 3,
      'sequential'  => 1,
      'id'          => $cRecurID
    ];
    $aContributionRecur = civicrm_api('ContributionRecur', 'get', $cRecurParams);
    if(!$aContributionRecur['is_error']){
      $cRecur = $aContributionRecur['values'][0];
    }

    // Get contribution Status label
    $contributionStatusOptions = CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'validate');
    $contributionStatus = $contributionStatusOptions[$cRecur['contribution_status_id']];

    //get payment processor name
    $paymentProcessorName = CRM_Core_Payment_Smartdebit::getSmartDebitPaymentProcessorName($cRecur['payment_processor_id']);

    $contributionRecur = [];
    if(!empty($cRecur)){
      $contributionRecur = [
        'id'                => $cRecur['id'],
        'status'            => $contributionStatus,
        'amount'            => $cRecur['amount'],
        'payment_processor' => $paymentProcessorName,
      ];
    }
    return $contributionRecur;
  }

  /**
   * Is this recurring contribution of type Smartdebit?
   *
   * @param $recurId
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function isSmartdebitPaymentProcessor($recurId) {
    $paymentProcessors = civicrm_api3('PaymentProcessor', 'get', [
      'return' => ["id"],
      'payment_processor_type_id' => "Smart_Debit",
    ]);

    $paymentProcessorIds = array_keys($paymentProcessors['values']);

    $recurParams = [
      'payment_processor_id' => ['IN' => $paymentProcessorIds],
      'id' => $recurId,
    ];
    try {
      civicrm_api3('ContributionRecur', 'getsingle', $recurParams);
    }
    catch (Exception $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get contact details
   *
   * @param $cid
   * @return mixed
   */
  static function getContactDetails($cid) {
    $Params = [
      'version'     => 3,
      'sequential'  => 1,
      'id'          => $cid
    ];
    $aContact = civicrm_api('Contact', 'get', $Params);
    if (empty($aContact['is_error'])) {
      if ($aContact['count'] > 0) {
        return $aContact['values'][0];
      }
      else {
        return NULL;
      }
    }
    else {
      return $aContact['error_message'];
    }
  }

  /**
   * Get contact Address
   *
   * @param $cid
   */
  static function getContactAddress($cid) {
    $Params = [
      'version'     => 3,
      'sequential'  => 1,
      'contact_id'  => $cid
    ];
    $aAddress = civicrm_api('Address', 'get', $Params);
    if (empty($aAddress['is_error'])) {
      if ($aAddress['count'] > 0){
        return $aAddress['values'][0];
      }
      else {
        return NULL;
      }
    }
    else {
      return $aAddress['error_message'];
    }
  }

  /**
   * @param $amount
   * @return mixed
   */
  static function getCleanSmartdebitAmount($amount) {
    $numeric_filtered = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    return($numeric_filtered);
  }

  /**
   * Output log messsages
   *
   * @param $logMessage
   * @param $debug
   */
  public static function log($logMessage, $debug) {
    if (!$debug) {
      Civi::log()->info($logMessage);
    }
    elseif ($debug && (CRM_Smartdebit_Settings::getValue('debug'))) {
      Civi::log()->debug($logMessage);
    }
  }

}
