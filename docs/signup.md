# Signup for Direct Debit payments
## Configure sign-up page
Create a contribution page with the smart debit processor enabled.  Make sure the following is set

* Recurring contribution **OR** Membership is configured for auto-renew.

## Test the contribution page
It should look something like this:

![signup page](/images/smartdebit_online_contribution_full.png)

## Confirmation
Confirm details.

![Confirmation page](/images/smartdebit_online_contribution_confirm_full.png)

## Completion of signup
Here you see the confirmation and, importantly, the direct debit mandate that the customer may print if they wish.

![signup thankyou](/images/smartdebit_online_contribution_thankyou_full.png)
![thankyou mandate](/images/smartdebit_online_contribution_thankyou_mandate.png)


## Administration
The following entities will have been created during sign-up and may need attention:

* New contact (if the contact didn't already exist)
* Recurring contribution.
* Contribution.
![contact contributions](/images/wiki/smartdebit_contributions.png)

* Membership (if contribution form was for membership) - this will be in _Pending_ state until the payment is confirmed during a sync.  **Note:** This may take up to a month as the direct debit payment will not be taken until the specified date.
![contact membership](/images/contact_membership_view_options.png)

* Recurring contribution activity.
* Direct Debit sign-up activity (showing Smart Debit payer Reference).
* Direct Debit confirmation letter (If _Post_ was selected as confirmation method).  This will be in _Scheduled_ state and will need to be picked up by the admin team.
![contact activities](/images/smartdebit_activities.png)

