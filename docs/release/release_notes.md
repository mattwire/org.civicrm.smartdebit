## Release 1.21

* Handle situation where we already have a (not first-payment) contribution recorded for the transaction ID (update the contribution instead of trying to create a new one).
* Get collection report by backtracking 7 days, as collection report is available in Smart debit only after 3 days
* Sync/Process smart debit payments based on the collection report and not mandates, as not all mandates will have collection (quarterly, half-yearly or yearly mandates)
* Remove 60 second time limit on API calls as large collection reports can exceed this (rely on the server configuration instead).

*Thanks Rajesh Sundararajan for submitting PR#12 to improve sync of collection reports.*

## Release 1.20
* Improve error handling and debug info (don't stop the sync process if one transaction fails).
* Add missing "by reference" on params for updateRecurringContributions hook.
* Add Smartdebit.updaterecurring API function.
* Add Smartdebit.getmandates API function.

## Release 1.19

* Set default collection report cache to 1 year as this works better for annual collections.
* Always set contribution_status_id when creating a new contribution.
* Capture the API (contribution) result when using repeattransaction so we we can store the contribution ID in veda_success_contributions table and avoid a fatal error when not set.
* Report error when we could not create contribution so that processCollection does not try and record it as successful.
* Change some logging from debug to info for sync task.
* Tweaks to reconciliation and createrecurcontribution for better error handling.
* Reconciliation:
  * Allow to reconcile mandates in any status (not just live)
  * Fix start_date not being set correctly
  * Improve error handling

## Release 1.18

* Minor tweaks for drupal webform support

## Release 1.17

* Rename hook_civicrm_alterCreateVariableDDIParams to hook_civicrm_alterVariableDDIParams.
* Expand scope of hook_civicrm_alterVariableDDIParams so it works in all API create/update scenarios.
* Set start date to match date of first contribution on recurring contribution.
* Update the cached mandate when updating smartdebit mandate via API.
* Only update recurring contribution if it changed. Don't touch start_date unless we want to update it.
* Make Collection Report cache size configurable.
* Improve description for contributions so they now display Source: SDCR, SDAUDDIS, SDARUDD for collection report, ARUDD, AUDDIS respectively.
* Internal improvements to sync job.

!!! warning
    hook_civicrm_alterCreateVariableDDIParams is now renamed to hook_civirm_alterVariableDDIParams and has additional parameter $op.

## Release 1.16

* Improve display of Smartdebit mandate status.
* Update recurring contribution parameters during sync (frequency, amount, start date).
* Pass first_amount flag through to hook_civicrm_alterSmartdebitContributionParams hook.
* Improve error message when Smartdebit mandate cannot be found (when viewing recurring contribution).
* Improve "Update Subscription" so you can update frequency and start_date as well as amount.
* Add a new hook "hook_civicrm_updateRecurringContribution" which allows you to update/perform actions during the sync job.
* Bugfix: Default to completed for new contributions, not 'In Progress'!
* Rename sync API to Job.process_smartdebit for consistency (old Smartdebit.sync is still available as an alias)
* Switch to repeattransaction API for adding each new contribution (as completetransaction doesn't work if contribution is already 'Completed').
* Add setting for debug logging.
* Update recurring contribution status and clear cancel date if mandate is live at smartdebit.

!!! warning
    Some of the hooks names were changed.  If you rely on hooks ensure your update your code when upgrading.

## Release 1.15

* Fix retrieval and display of Bank Name from Smartdebit.
* Add date of first collection to Thankyou page.
* Make state/province optional in billing address as it's not required by Smartdebit.

## Release 1.14

* Simplify Direct debit instructions/mandate on contribution pages following feedback from smartdebit.
* Support membership renewal by calling Contribution.completetransaction API

## Release 1.13

* Add setting for "Advance Notice Period" as required by some clients which is displayed 
on the Direct Debit Guarantee.

## Release 1.12

* Support changing recurring amount (via "Edit Recurring" on backend).
* Set cycle_day and end_date on recurring contribution.
* Support installments.

## Release 1.11

* Add settings to customise confirm-by options (Email/Post).  Hide confirm-by and preferred collection date if there is only one available.
* Get default email address from contact if not supplied in params.
* Fix contribution status in more recent versions of CiviCRM (4.7.27).

## Release 1.10

* Fixes to reconciliation wizard.

## Release 1.9

* Add and document hooks.
* Don't cleanup old collection reports before they have been used!

## Release 1.8

* Styling changes for settings page to work better with shoreditch theme.

## Release 1.7

* Define another internal setting so we don't get a fatal error in certain circumstances.
* Code cleanup.

## Release 1.6

* Implement setting to control status of initial contribution (whether to leave in pending state 
or mark as completed immediately). This allows memberships to become active immediately. Implements [#1](https://github.com/mattwire/org.civicrm.smartdebit/issues/1).
* Replace github wiki docs with mkdocs.

## Release 1.5

* Remove overridden template - fix [#6](https://github.com/mattwire/org.civicrm.smartdebit/issues/6).
* Catch exception when payment processor not yet configured.

## Release 1.4

* Fix display of multiple pslid on setting page (system status)

## Release 1.3

* Smartdebit API is picky about double / in URL path. Update buildUrl method to ensure we only pass a single / in URL path.
* Fix reporting of failed contributions after sync (now correctly reports zero (instead of 1) if none were found).

## Release 1.2

* Refactor and improve error reporting for payment forms.
* Add system status api function and display on settings page.
* Better support for test payment processor.
* Require valid SSL certificates when using API (cURL option).
* Change defaults for some settings.

## Release 1.1
 
Rename to org.civicrm.smartdebit to reflect multiple contributions. This is the first release that should be used. 

## Release 1.0

Initial release
