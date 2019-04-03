## API functions

#### Job.process_smartdebit
This is run by the daily scheduled task.  It performs the daily sync from Smartdebit.

#### Smartdebit.sync
Alias for Job.process_smartdebit

#### Smartdebit.getmandates
Parameters:
* **refresh**: If true, refresh from Smartdebit, otherwise load from local cache.
* **only_withrecurid**: If true, only load mandates that have a recurring contribution ID associated with them.
* **trxn_id**: CiviCRM transaction ID / Smartdebit Reference Number of Mandate.

This will retrieve one or more mandates either from the local cache or directly from Smartdebit.

#### Smartdebit.updaterecurring
Parameters: None

This API function allows to call updaterecurring function directly, instead of via sync where it is normally called as the final step.
It is mostly for debugging but can be useful for testing hooks as well.

#### Smartdebit.processcollections
Process collection reports: get all available payments in the collection reports and import each transaction from Smart Debit (using the locally cached collection reports).

Parameters:
* trxn_id: (optional) CiviCRM transaction ID / Smartdebit Reference Number of Mandate: specify this to limit to the specified transaction ID.
* successes: Process successful collections (0 or 1): optionally limit to successful or unsuccessful collections.

*Note that we don't use the unsuccessful collections from the collection reports, as these are retrieved via ARUDD reports instead*

#### Smartdebit.clearcache
Delete mandates/collectionreports from local caches (CiviCRM database).

#### Smartdebit.getmandatescount
Get the number of mandates from the database.

#### Smartdebit.retrievemandates
Retrieve mandates from Smartdebit.

#### Smartdebit.getcollections
Get the collections from the database.

#### Smartdebit.getcollectionscount
Get the number of collections in the database.

#### Smartdebit.retrievecollectionreports
Retrieve collection reports from Smartdebit.