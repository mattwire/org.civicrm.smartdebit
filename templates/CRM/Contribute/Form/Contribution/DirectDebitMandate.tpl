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
  <div>
    {ts}Thank you very much for your Direct Debit Instruction details. Below is the Direct Debit Guarantee for your information.
      Please print this page for your records{/ts}
  </div>
  <div class="display-block">
    {* Start of DDI *}
    <div style="border: 3px solid #000000;background-color: #ffffff;width: 95%; padding: 15px">
      <div style="text-align: center;">
        <div><span style="float: right; margin: 25px;"><img src="{crmResURL ext=uk.co.vedaconsulting.smartdebit file=images/direct_debit_small.png}" alt="Direct Debit Logo" border="0"></span></div>
        <div style="clear: both;"></div>
      </div>
      <div style="float: left;margin-left: 5px;margin-right: 10px;width: 305px;">
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0px 5px;">
          <b>{$dd_details.company_address.company_name}</b><br>
          {if ($dd_details.company_address.address1 != '')} {$dd_details.company_address.address1}<br/> {/if}
          {if ($dd_details.company_address.address2 != '')} {$dd_details.company_address.address2}<br/> {/if}
          {if ($dd_details.company_address.address3 != '')} {$dd_details.company_address.address3}<br/> {/if}
          {if ($dd_details.company_address.address4 != '')} {$dd_details.company_address.address4}<br/> {/if}
          {if ($dd_details.company_address.town != '')    } {$dd_details.company_address.town}<br/>     {/if}
          {if ($dd_details.company_address.county != '')  } {$dd_details.company_address.county}<br/>   {/if}
          {if ($dd_details.company_address.postcode != '')} {$dd_details.company_address.postcode}      {/if}
        </div>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">Name(s) of Account Holder(s)</h2>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0px 5px;">
          {$dd_details.account_holder}<br />
        </div>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">Bank/Building Society Account Number</h2>
        <p>
        <table style="background-color: #ffffff;border: 1px solid #000000;border-collapse: collapse;" summary="Branch Sort Code">
          <tr>
            <td style="border: 1px solid #000000;padding: 0;width: 240px;height: 30px;text-align: left;">{$dd_details.bank_account_number}</td>
          <tr>
        </table>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">Branch Sort Code</h2>
        <p>
        <table style="background-color: #ffffff;border: 1px solid #000000;border-collapse: collapse;" summary="Branch Sort Code">
          <tr>
            <td style="border: 1px solid #000000;padding: 0;width: 180px;height: 30px;text-align: left;">{$dd_details.bank_identification_number}</td>
          <tr>
        </table>
        </p>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0px 5px;">
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">To the Manager<span style="margin-left: 4em;">Bank/Building Society</span></span></div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><br />{$dd_details.bank_name}</div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">Branch</span><span style="margin-left: 3em;">{$dd_details.branch}</span></div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">Address</span></div>
          {if ($dd_details.address1 != '')} <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.address1}<br/></div> {/if}
          {if ($dd_details.address2 != '')} <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.address2}<br/></div> {/if}
          {if ($dd_details.address3 != '')} <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.address3}<br/></div> {/if}
          {if ($dd_details.address4 != '')} <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.address4}<br/></div> {/if}
          {if ($dd_details.town != '')    } <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.town    }<br/></div> {/if}
          {if ($dd_details.county != '')  } <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.county  }<br/></div> {/if}
          {if ($dd_details.postcode != '')} <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;">{$dd_details.postcode}<br/></div> {/if}
        </div>
        </p>
      </div>
      <div style="float: right;margin-right: 5px;width: 305px;">
        <h1 style="font-size: 1.3em;margin-top: 0;text-align: left;margin: 0% 0%;">Instruction to your Bank or Building Society to pay by Direct Debit</h1>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">Service User Number</h2>
        <p>
        <table style="background-color: #ffffff;border: 1px solid #000000;border-collapse: collapse;" summary="Branch Sort Code">
          <tr>
            {foreach from=$dd_details.sunParts item=singleDigit}
              <td style="border: 1px solid #000000;padding: 0;width: 30px;height: 30px;text-align: center;">{$singleDigit}</td>
            {/foreach}
          <tr>
        </table>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">Reference:</h2>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0px 5px;">
          {$dd_details.ddi_reference}
        </div>
        </p>
        <h2 style="font-size: 1em;text-align: left;font-weight: bold;margin-bottom: 3px; margin-top: 15px;">Instruction to your Bank or Building Society</h2>
        <p>
          Please pay {$dd_details.company_address.company_name} Direct Debits from the account detailed in this Instruction subject to the safeguards assured by the Direct Debit Guarantee. I understand that this Instruction may remain with {$dd_details.company_address.company_name} and, if so, details will be passed electronically to my Bank / Building Society.
        </p>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0px 5px;">
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">Date</span><span style="margin-left: 1em;">{$dd_details.today|crmDate}</span></div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"></div>
        </div>
        </p>
      </div>
      <div style="clear: both;"></div>
      <div>
        <p style="text-align: center;">
          Banks and Building Societies may not accept Direct Debit Instructions from some types of account.
        </p>
      </div>
    </div>
    {* End of DDI *}
    <div class="clear" style="padding: 10px"></div>
    <div style="border: 3px solid #000000;background-color: #ffffff;width: 95%; padding: 15px">
      <div style="font-family: Arial,Helvetica,Monaco; font-size: 28px; line-height: 35px">
        <span style="float: right; width: 107px; vertical-align: top"><img src="{crmResURL ext=uk.co.vedaconsulting.smartdebit file=images/direct_debit_small.png}" alt="Direct Debit Logo" border="0"></span>
        <strong>The Direct Debit Guarantee</strong>
      </div>
      <div class="clear"></div>
      <ul style="margin: 0px; padding: 10px 20px 10px 20px">
        <li style="margin-bottom: 10px">{ts}This Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits.{/ts}</li>
        <li style="margin-bottom: 10px">{ts}If there are any changes to the amount, date or frequency of your Direct Debit {$dd_details.company_address.company_name} will notify you 10 working days in advance of your account being debited or as otherwise agreed. If you request {$dd_details.company_address.company_name} to collect a payment, confirmation of the amount and date will be given to you at the time of the request.{/ts}</li>
        <li style="margin-bottom: 10px">{ts}If an error is made in the payment of your Direct Debit, by {$dd_details.company_address.company_name} or your bank or building society, you are entitled to a full and immediate refund of the amount paid from your bank or building society.{/ts}
          - {ts}If you receive a refund you are not entitled to, you must pay it back when {$dd_details.company_address.company_name} asks you to.{/ts}</li>
        <li>{ts}You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written confirmation may be required. Please also notify {$dd_details.company_address.company_name}.{/ts}</li>
      </ul>
    </div>
    <div class="clear"></div>
  </div>
</div>
