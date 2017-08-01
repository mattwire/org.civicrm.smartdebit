{*--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2017                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+-------------------------------------------------------------------*}

<div class="crm-group credit_card-group">
  <div class="header-dark">
    {ts}Direct Debit Information{/ts}
  </div>
  <div class="display-block">
    <div><span style="float: right;margin: 25px;"><img src="{crmResURL ext=org.civicrm.smartdebit file=images/direct_debit_small.png}" alt="Direct Debit Logo" border="0"></span></div>
    <div class="clear"></div>
    <table>
      <tr><td>{ts}Account Holder{/ts}:</td><td>{$dd_details.account_holder}</td></tr>
      <tr><td>{ts}Bank Account Number{/ts}:</td><td>{$dd_details.bank_account_number}</td></tr>
      <tr><td>{ts}Bank Identification Number{/ts}:</td><td>{$dd_details.bank_identification_number}</td></tr>
      <tr><td>{ts}Bank Name{/ts}:</td><td>{$dd_details.bank_name}</td></tr>
    </table>
  </div>
  <div class="crm-group debit_agreement-group">
    <div class="header-dark">
      {ts}Agreement{/ts}
    </div>
    <div class="display-block">
      <strong>{ts}Your account data will be used to charge your bank account via direct debit. When submitting this form you agree to the charging of your bank account via direct debit.{/ts}</strong>
    </div>
  </div>
</div>
