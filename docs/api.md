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