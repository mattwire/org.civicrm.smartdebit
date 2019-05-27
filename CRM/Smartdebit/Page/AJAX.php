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

class CRM_Smartdebit_Page_AJAX
{

  /**
   * Get list of memberships and recurring contributions
   * Pass in "selectedContact" contact ID as POST parameter.
   */
  static function getMembershipByContactID() {
    $selectedContact = CRM_Utils_Array::value('selectedContact', $_POST);
    if (empty($selectedContact)) {
      return;
    }
    $membershipList = CRM_Smartdebit_Utils::getContactMemberships($selectedContact);
    $cRecur = CRM_Smartdebit_Utils::getContactRecurringContributions($selectedContact);
    $nullMembership = [0 => 'No Membership Found'];
    $options['membership'] = $membershipList ? $membershipList : $nullMembership;
    $options['cRecur'] = $cRecur;
    echo json_encode($options);
    exit;
  }

  /**
   * Update the list of recurring contributions (cRecurNotLinked is used when membership type "Donation" is selected)
   */
  static function getNotLinkedRecurringByContactID() {
    $selectedContact = CRM_Utils_Array::value('selectedContact', $_POST);

    // Get contact memberships
    $mParams = [
      'version'     => 3,
      'sequential'  => 1,
      'contact_id' => $selectedContact
    ];
    $aMembership = civicrm_api('Membership', 'get', $mParams);
    $membershipWithRecur = [];
    // Filter memberships that have linked recurring contributions
    foreach ($aMembership['values'] as $membership ) {
      if (!empty($membership['contribution_recur_id'])) {
        $membershipWithRecur [] = $membership['contribution_recur_id'];
      }
    }
    // Get all recurring contributions for contact
    $allRecurringRecords = $originalAllRecurringRecords = CRM_Smartdebit_Utils::getContactRecurringContributions($selectedContact);
    // Now filter and remove recurring contributions that are linked to a membership for this contact
    foreach ($membershipWithRecur as $linkedRecur) {
      if(array_key_exists($linkedRecur, $allRecurringRecords)) {
        unset($allRecurringRecords[$linkedRecur]);
      }
    }
    $options['cRecurNotLinked'] = $allRecurringRecords;
    $options['cRecur'] = $originalAllRecurringRecords;
    echo json_encode($options);
    exit;
  }
}
