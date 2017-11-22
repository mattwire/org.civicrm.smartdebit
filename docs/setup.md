# Setup and Configuration
## Setup with Smartdebit
You will need an account at Smartdebit with API access.

It will need at least the following parameters configured:

* Supply payer reference via API: Yes
* Support Variable DDI: Yes
* Support Frequency Factor: Yes (if you want anything other than 1 month or 1 year intervals).
* Support Collection days: For flexibility, allow any at Smartdebit and restrict via Smartdebit Settings in CiviCRM.

Ask Smartdebit to confirm:

* What is the "Advance Notice Period" for the Direct Debit Guarantee?


## CiviCRM Setup
Install this extension!

The following menu options will then be available:

* Administer->Smart Debit

  * Settings
  * Manual Sync
  * Reconcile Transactions

In addition, a scheduled job is installed.  This performs automatic sync of successful and failed payments - whilst you are testing you may wish to disable this job and run manually via the menu options above.

## Configure Payment Processor
Before you use Smart Debit you need to configure it as a payment processor on your site.
 
!!! tip "Testing" 
    Normally you would put your test API details in the "Live" section until you are ready to go live.

Configure Live and Test Processors according to the details provided by Smartdebit.
![Payment Processor](/images/payment_processor.png)

## Settings
Each setting has a help icon associated with it - click it for more information.
![settings](/images/smartdebit_settings.png)

## Testing
When using the test Smartdebit API you can use the following account details:

  * Account Number: any 8 digit number.
  * Sort Code: 00-00-00.

## Manual Sync
This allows you to manually sync collection reports, AUDDIS and ARUDD records from Smart Debit.  Useful if you need to sync for a different period (the default is the last 3 months).

![sync task complete](/images/smartdebit_sync_complete.png)

## Reconcile Transactions
This is quite a powerful tool which allows you to reconcile and correct differences between Smart Debit and CiviCRM.  Very useful if your organisation have been using Smart Debit directly for a while and are now integrating into CiviCRM, or if you are migrating from an older version of the extension.
