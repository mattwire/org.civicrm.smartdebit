## API functions

#### Smartdebit.updaterecurring
Parameters: None

This API function allows to call updaterecurring function directly, instead of via sync where it is normally called as the final step.
It is mostly for debugging but can be useful for testing hooks as well.

#### Smartdebit.sync
Alias for Job.process_smartdebit

#### Job.process_smartdebit
This is run by the daily scheduled task.  It performs the daily sync from Smartdebit.