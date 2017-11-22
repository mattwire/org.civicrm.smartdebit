## Release 1.13

* Add setting for "Advance Notice Period" as required by some clients which is displayed 
on the Direct Debit Guarantee.

## Release 1.12

* Support changing recurring amount (via "Edit Recurring" on backend).
* Set cycle_day and end_date on recurring contribution.
* Support installments.

## Release 1.11

* Add settings to customise confirm-by options (Email/Post).  Hide confirm-by and preferred collection date if there is only one available.
* Get default email address from contact if not supplied in params.
* Fix contribution status in more recent versions of CiviCRM (4.7.27).

## Release 1.10

* Fixes to reconciliation wizard.

## Release 1.9

* Add and document hooks.
* Don't cleanup old collection reports before they have been used!

## Release 1.8

* Styling changes for settings page to work better with shoreditch theme.

## Release 1.7

* Define another internal setting so we don't get a fatal error in certain circumstances.
* Code cleanup.

## Release 1.6

* Implement setting to control status of initial contribution (whether to leave in pending state 
or mark as completed immediately). This allows memberships to become active immediately. Implements [#1](https://github.com/mattwire/org.civicrm.smartdebit/issues/1).
* Replace github wiki docs with mkdocs.

## Release 1.5

* Remove overridden template - fix [#6](https://github.com/mattwire/org.civicrm.smartdebit/issues/6).
* Catch exception when payment processor not yet configured.

## Release 1.4

* Fix display of multiple pslid on setting page (system status)

## Release 1.3

* Smartdebit API is picky about double / in URL path. Update buildUrl method to ensure we only pass a single / in URL path.
* Fix reporting of failed contributions after sync (now correctly reports zero (instead of 1) if none were found).

## Release 1.2

* Refactor and improve error reporting for payment forms.
* Add system status api function and display on settings page.
* Better support for test payment processor.
* Require valid SSL certificates when using API (cURL option).
* Change defaults for some settings.

## Release 1.1
 
Rename to org.civicrm.smartdebit to reflect multiple contributions. This is the first release that should be used. 

## Release 1.0

Initial release
