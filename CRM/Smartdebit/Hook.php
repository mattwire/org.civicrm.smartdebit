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
 * Class CRM_Smartdebit_Hook
 *
 * This class implements hooks for SmartDebit
 */
class CRM_Smartdebit_Hook {

  /**
   * This hook allows to alter params before submitting to SmartDebit.
   *
   * @param array $params Raw params
   * @param array $smartDebitParams Params formatted for smartdebit
   * @param string $op One of validate|create|update|updatebilling|cancel
   *
   * @access public
   *
   * @return mixed
   */
  static function alterVariableDDIParams(&$params, &$smartDebitParams, $op) {
    return CRM_Utils_Hook::singleton()
      ->invoke(3, $params, $smartDebitParams, $op, CRM_Utils_Hook::$_nullObject,
        CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_smartdebit_alterVariableDDIParams');
  }

  /**
   * This hook allows to alter contribution params when processing collection (before contribution is created).
   *
   * @param array $params Contribution params
   * @param bool $firstPayment True if this is the first payment
   *
   * @access public
   *
   * @return mixed
   */
  static function alterContributionParams(&$params, $firstPayment) {
    return CRM_Utils_Hook::singleton()
      ->invoke(2, $params, $firstPayment, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject,
        CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_smartdebit_alterContributionParams');
  }

  /**
   * This hook allows to handle AUDDIS rejected contributions
   *
   * @param integer $contributionId Contribution ID of the failed/rejected contribution
   *
   * @access public
   *
   * @return mixed
   */
  static function handleAuddisRejectedContribution($contributionId) {
    return CRM_Utils_Hook::singleton()
      ->invoke(1, $contributionId, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject,
        CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_smartdebit_handleAuddisRejectedContribution');
  }

  /**
   * This hook allows modifying recurring contribution parameters during sync task
   *
   * @param array $recurContributionParams Recurring contribution params (ContributionRecur.create API parameters)
   *
   * @access public
   *
   * @return mixed
   */
  static function updateRecurringContribution(&$recurContributionParams) {
    return CRM_Utils_Hook::singleton()
      ->invoke(1, $recurContributionParams, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject,
        CRM_Utils_Hook::$_nullObject, CRM_Utils_Hook::$_nullObject, 'civicrm_smartdebit_updateRecurringContribution');
  }

}
