If some of the data held at Smart Debit does not match what is in CiviCRM you may need to perform manual reconciliation.

## Getting started
Click on Administer->Smart Debit->Reconcile Transactions

Now you will see a page like this:![reconciliation overview](/images/reconciliation_overview.png)

_If you have no mandates synchronised to CiviCRM you will be prompted to do so, otherwise check that the number shown is realistic._

## Reconciling

3. Select the appropriate options and click confirm:
4. If you get any errors at this point you should record the error and contact your support team.


### Filter: _With Contact in CiviCRM and has Amount_

1. Click "Fix Me" next to the record ([Screenshot 1](#screenshot-1)).

2. Follow the steps in the wizard to select a membership and recurring transaction ([Screenshot 2](#screenshot-2)).  If there is no membership you can either:

      - Create one and run the wizard again.

      - Select Donation to create a direct debit without a membership.

3. Once everything has been reconciled, run the Manual Sync (Administer->SmartDebit->Manual Sync), specifying a date to sync from.  This will sync all records that you just reconciled for that time period (the same sync can be run as many times as you like).



### Filter: _With No Contact in CiviCRM and has Amount_
### Filter: _With No Contact in CiviCRM and no Amount_
### Filter: _With Contact in CiviCRM and no Amount_

1. Find the contact record in CiviCRM (try searching by surname).

2. Note the "Contact ID" from CiviCRM.

3. In the Smartdebit admin dashboard:

    a) Search for the "Transaction ID".

    b) Replace the Customer ID with the CiviCRM "Contact ID" by editing "Payer Details".

    c) Update/Amend "Payment Details" to make sure the correct amounts are set.

5. In CiviCRM Smartdebit Reconciliation: Click "Refresh Mandates" at the top of the page (You can update multiple records via steps 1-3 before doing this to save time).

6. Follow steps for "With Contact in CiviCRM and has Amount".

## Screenshots
#### Screenshot 1
![reconciliation select](/images/reconciliation_select.png)

Select Membership/Recurring transactions to use for reconciliation.

#### Screenshot 2
![reconciliation confirm](/images/reconciliation_confirm.png)

Confirm transactions to use for reconciliation.


