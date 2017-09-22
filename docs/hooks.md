## Hooks
!!! warning "Warning - Hooks have not been tested."

#### handleAuddisRejectedContribution($contributionId)
*Works with both AUDDIS and ARUDD records.*

* This hook allows to handle AUDDIS rejected contributions.
* @param integer $contributionId Contribution ID of the failed/rejected contribution.

#### alterSmartdebitContributionParams(&$params)

* This hook allows to alter contribution params when processing collection (before contribution is created).
* @param array $params Contribution params.