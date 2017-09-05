## Initial sign-up (online or offline)
* Recurring Contribution is created with status: _Pending_
* Contribution is created with status: _Pending_

_Note that a direct debit is not confirmed for at least 10 days meaning the payment will remain pending.  This will impact any associated membership which will also remain in the pending state until the payment is marked as Completed._

## Reconciliation
* Recurring Contribution is created with status: _Pending_
* Contribution is updated if found to match recurring contribution (status is not changed).

## Sync
If a successful collection report is found:
* Recurring contribution status is updated to _In Progress_.
* Contribution is updated to _Completed_

If a failed collection report is found:
* Recurring contribution status is not changed.
* Contribution status is updated to "Failed".