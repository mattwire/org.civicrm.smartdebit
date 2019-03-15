## Initial sign-up (online or offline)
A direct debit is not confirmed for at least 10 days which means that, by default, the transactions 
will remain in _Pending_ state until the payment has actually been taken. 
But this can mean that memberships are not activated for quite a long time so there is an option to 
override this behaviour.

#### Mark initial contribution as completed: Disabled (default)

* Recurring Contribution is created with status: _In Progress_
* Contribution is created with status: _Pending_
* Membership is created with status: _Pending_

#### Mark initial contribution as completed: Enabled

* Recurring Contribution is created with status: _In Progress_
* Contribution is created with status: _Completed_
* Membership is created with status: _New_

## Reconciliation

* Recurring Contribution is created with status: _In Progress_
* Contribution is updated if found to match recurring contribution (status is not changed).

## Sync
#### If a successful collection report is found:

* Recurring contribution status is updated to _In Progress_.
* Contribution is updated to _Completed_

#### If a failed collection report is found:

* Recurring contribution status is not changed.
* Contribution status is updated to "Failed".

#### If a direct debit has been cancelled:

* Recurring contribution status is updated to _Cancelled_.
