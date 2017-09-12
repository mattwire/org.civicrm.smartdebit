# Smartdebit Account
You will need an account at Smartdebit with API access.

It will need at least the following parameters configured:

* Supply payer reference via API: Yes
* Support Variable DDI: Yes
* Support Frequency Factor: Yes (if you want anything other than 1 month or 1 year intervals).

# CiviCRM
Install this extension!

The following menu options will then be available:

* Administer->Smart Debit

  * Settings
  * Manual Sync
  * Run Sync Task
  * Reconcile Transactions

In addition, a scheduled job is installed.  This performs automatic sync of successful and failed payments - whilst you are testing you may wish to disable this job and run manually via the menu options above.

## Settings
Each setting has a help icon associated with it - click it for more information.
![settings](/images/smartdebit_settings.png)

## Manual Sync
This allows you to manually sync collection reports, AUDDIS and ARUDD records from Smart Debit.  Useful if you need to sync for a different period (the default is the last 3 months).

## Payment Processor
You need to configure a Smart Debit payment processor:
![civicrm payment processor setup](/images/smartdebit_paymentprocessor.png)

## Run Sync Task
This does exactly the same as the Scheduled Sync Job, but shows the results via the UI.  You will still need to look at the CiviCRM log file to see if anything went wrong!
![start sync](/images/smartdebit_startsync.png)

![sync task complete](/images/smartdebit_sync_complete.png)

## Reconcile Transactions
This is quite a powerful tool which allows you to reconcile and correct differences between Smart Debit and CiviCRM.  Very useful if your organisation have been using Smart Debit directly for a while and are now integrating into CiviCRM, or if you are migrating from an older version of the extension.
