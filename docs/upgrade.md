## Important notes
**The old ukdirectdebit extension MUST be uninstalled before installing this new extension.  However, all the payment information will be retained.**

If you follow the instructions below the payment processor configuration will be retained, but the extension settings will NOT be migrated and will need to be done manually (take a screenshot of the settings page before uninstalling).

## Steps to upgrade from the older uk.co.vedaconsulting.payment.ukdirectdebit extension

* Change smart debit payment processor type to paypal express in civicrm (so you don't lose the payment processor settings)
* Disable/Uninstall smart_debit_reconciliation via CiviCRM UI!
* Disable/uninstall SmartDebit extension

`drush civicrm-ext-disable uk.co.vedaconsulting.payment.smartdebitdd`
`drush civicrm-ext-uninstall uk.co.vedaconsulting.payment.smartdebitdd`

* Disable/uninstall ukdirectdebit extension

`drush civicrm-ext-disable uk.co.vedaconsulting.payment.ukdirectdebit`
`drush civicrm-ext-uninstall uk.co.vedaconsulting.payment.ukdirectdebit`

* Remove extensions:

`rm uk.co.vedaconsulting.module.smartdebit_reconciliation/ -rf`
`rm uk.co.vedaconsulting.payment.* -rf`

* Delete existing:
  * Smartdebit scheduled jobs

* Download/install new extensions (smartdebit)

`git clone https://github.com/mattwire/org.civicrm.smartdebit.git`
`drush civicrm-ext-install org.civicrm.smartdebit`

* Change Payment processor type back to Smart_Debit in CiviCRM UI
* Configure Smart debit extension (civicrm/smartdebit/settings)

!!! tip "Cached collection reports"
    If upgrading from a previous version it is likely that you will need to rebuild the collection reports 
    cache in CiviCRM.  This can be done by running a manual sync and specifying todays date - see 
    [Manual Sync](/sync_manual.md)
