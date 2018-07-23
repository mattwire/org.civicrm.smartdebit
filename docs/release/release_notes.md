## Release 1.24.1
Fix missing comma in install SQL. This only affects users installing 1.24 for the first time and there is no need to upgrade to this version otherwise.


## Release 1.24
** THIS IS A MAJOR UPDATE **

It has significant performance improvements to the synchronisation process for organisations with many direct debits.  The manual sync UI has been significantly improved. There is a suite of new API functions to facilitate advanced functionality/debugging. 

### Upgrading:
Multiple tables have been renamed and some tables have been added/removed.
 
**BACKUP your database before upgrading!**

You will need to run the extension upgrader immediately after updating to this version.

### Fixes
* Allow the synchronisation scheduled job to be disabled
* Resolve #17 Do not try and display mandate information on non-smartdebit recurring contribution view.
* Don't limit the number of contributions we can return when checking for first payment. There could be more than 25.
* Use name instead of label for payment_instrument when retrieving records during reconciliation.
* Properly handle test transactions.

### Features
* Save ARUDD/AUDDIS reason on contribution source text if possible.
* Create a new "Diagnostics" page with lot's of information about cached reports etc.
* Add a direct link to the navigation menu to view results of the last sync (whether triggered manually or automatically) (uses the new veda_smartdebit_syncresults database table).
* New API function: processcollections.
* New API function: clearcache to allow for deleting mandates/collectionreports from caches.
* New API function: getmandatescount.
* New API function: retrievemandates.
* New API function: getcollections.
* New API function: getcollectionscount.
* New API function: retrievecollectionreports.
* Improve API updaterecurring to support specifying transaction IDs.

### Changes
* Retry 3 times when we get an empty response from the Smartdebit server.
* Formatting/Comments/Function visibility.
* Set 'number of installments' to 1 when making a single payment.
* Don't send out receipts when creating/updating contributions.
* Set the next scheduled contribution date when we receive a contribution.
* When viewing a recurring contribution and the smartdebit mandate has changed, update the recurring contribution.

### Debugging:
* Additional debug messages for "updaterecurring" when you enable debug.
* Include request URL in error log when we get an API error.

### Manual Sync UI:
The manual sync UI has had significant improvements. You must now use the API retrievecollectionreports if you want to sync specific reports. The manual sync UI allows you to sync existing cached reports or retrieve the latest daily report for sync.
It also significantly improves the summary and results pages.

* Make retrieving collection report optional (eg. if you already have it cached, or you have synced a specific one via the API first).
* Add details of latest collection report to manual sync UI.
* Summary page before synchronisation:
  * Add link to recurring contribution for contributions matched to contacts on manual sync summary.
  * Use accordions to group results.
  * Add link to contribution for collections that have already been processed.
  * Open links to records in new window/tab.
  * Fix display of receive date for contributions already processed.
  * Add counts and relative links.
  * Fix display of contact name on ARUDD report.
* Fix Selection of ARUDD/AUDDIS records.

### Performance Improvements
For organisations that have a large number of mandates (eg. 50,000) and a large number of collections in a report (eg. 12,000) this release has made significant performance improvements to cope with numbers of this size.

  * Don't resync all mandates every time we sync. Just sync the ones that are present in the collection report being processed.
  * Refactor get mandates to resolve timeouts with large collections.
  * Add new table to store collection report summaries.
  * Split common retrieve collection reports code.
  * Add batching to CRM_Smartdebit_Mandates::getAll so we can run updateRecurringContributions task without loading all mandates into memory.
  * Switch to retrieving Mandates from Smartdebit using CSV format instead of XML as it is a more compact format and is quicker to retrieve.
  * Use SQL LIMIT and OFFSET to get collection reports from database instead of retrieving all of them and performing an array_slice in PHP.

## Release 1.23

* Minor tweak to wording of mandate as requested by bank - *"Please also notify us".*
* Close curl session after use (reduce resource usage in some cases).
* Move Smartdebit menu to Administer->CiviContribute.
* Settings Form addElement -> addSelect.
* Fix PHP notice.
* Add unit testing framework and support for mocking Smartdebit responses.
* Change alterVariableDDIParams 'update' to 'edit' for consistency with CiviCRM core. **Note: This may break extensions that rely on this hook!**

## Release 1.22

* Validate smartdebit params after hooks but before sending to smartdebit - add a checkSmartDebitParams function. Don't set regular_amount, use default_amount only. **Smartdebit requires amounts in pence (eg. £54.11=5411) but you should always set amounts with a decimal point (eg. 54.11) - it will be formatted for submission AFTER the hook.**
* Improve error handling when submitting a new direct debit instruction and we get a failure response from Smartdebit.
* Use CiviCRM setting for SSL certificate validation
* Don't hardcode billing address ID (get ID automatically from address types)
* Populate next_sched_contribution_date instead of legacy next_sched_contribution
* Fix PHP notice when amount is not set (eg. during payment validation)

* Updating subscriptions:
  * Don't pass through empty parameters to Smartdebit as they can be interpreted incorrectly (eg. empty end_date sets a date in the past).
  * Fix crash when updating existing subscription.

* UI changes:
  * Change contribution description flags to wrap in [] (eg. [SDCR]).
  * Make sure we only display smartdebit mandate once.
  * Format mandate details a little bit more nicely on recurring contribution detail.

* Sync fixes:
  * Make sure we assign contribution ID when we have found a previous contribution (previously failed to sync in some cases).
  * Call completetransaction on first payments. Fix parameters for repeattransaction (previously failed to renew membership in some cases).
  * When retrieving mandates (payerDetails) from smartdebit, validate data.
  * Return the retrieved mandate directly instead of querying the database as caching sometimes returns older details.

* Non functional changes:
  * Improve commenting / code style fixes.

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
