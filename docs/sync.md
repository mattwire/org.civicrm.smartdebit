# Synchronisation from Smart Debit

A scheduled job will automatically synchronise the following information from Smart Debit:

* Successful collections (contributions).
* Failed collections (contributions) via the AUDDIS/ARUDD reports.

Once an AUDDIS/ARUDD report has been processed it is recorded in the SQL table veda_smartdebit_auddis with processed=1.  If you need to re-run sync for an AUDDIS/ARUDD record you need to update the record and set processed=0 (there is no harm in running the sync multiple times).

## Collection Reports
Every time the sync job runs it will try and download the latest daily collection reports from Smartdebit.
Up to a year (configurable) of reports will be retained.
