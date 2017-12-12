## Hooks

#### hook_civicrm_smartdebit_alterVariableDDIParams(&$params, &$smartDebitParams, $op)
This hook allows to alter params before submitting to SmartDebit.

* @param array $params Raw params
* @param array $smartDebitParams Params formatted for smartdebit
* @param string $op One of validate|create|update|updatebilling|cancel

#### hook_civicrm_smartdebit_alterContributionParams(&$params)
This hook allows to alter contribution params when processing collection (before contribution is created).

* @param array $params Contribution params.

#### hook_civicrm_smartdebit_handleAuddisRejectedContribution($contributionId)
*Works with both AUDDIS and ARUDD records.*

This hook allows to handle AUDDIS rejected contributions.

* @param integer $contributionId Contribution ID of the failed/rejected contribution.

#### hook_civicrm_smartdebit_updateRecurringContribution(&$recurContributionParams)
This hook allows modifying recurring contribution parameters during sync task.

* @param array $recurContributionParams Recurring contribution params (ContributionRecur.create API parameters).
