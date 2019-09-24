{* https://civicrm.org/licensing *}

<div class="crm-group credit_card-group">
  <div class="header-dark">
    {ts}Direct Debit Information{/ts}
  </div>
  <div>
    <div class="display-block">
      {ts}Thank You For Setting Up a Direct Debit Payment.{/ts}
      <br />
      <strong>{ts 1=$dd_details.first_collection_date|crmDate}The first payment will be collected on or around %1.{/ts}</strong>
      <br />
      <br />
      {ts}Below is the Direct Debit Guarantee for your information.{/ts}
    </div>
  </div>
  <div class="display-block">
    {* Start of DDI *}
    <div style="border: 3px solid #000000;background-color: #ffffff;width: 95%; padding: 15px">
      <div style="text-align: center;">
        <div><span style="float: right; margin: 25px;"><img src="{crmResURL ext=org.civicrm.smartdebit file=images/direct_debit_small.png}" alt="Direct Debit Logo" border="0"></span></div>
        <div style="clear: both;"></div>
      </div>
      <div style="float: left;margin-left: 5px;margin-right: 10px;width: 305px;">
        <p>
        <div style="background-color: #ffffff; border: 1px solid #999999; padding: 0 5;">
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
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">{ts}Name(s) of Account Holder(s){/ts}</h2>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0 5;">
          {$dd_details.account_holder}<br />
        </div>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">{ts}Bank/Building Society Account Number{/ts}</h2>
        <p>
        <table style="background-color: #ffffff;border: 1px solid #000000;border-collapse: collapse;" summary="Branch Sort Code">
          <tr>
            <td style="border: 1px solid #000000;padding: 0;width: 240px;height: 30px;text-align: left;">{$dd_details.bank_account_number}</td>
          <tr>
        </table>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">{ts}Branch Sort Code{/ts}</h2>
        <p>
        <table style="background-color: #ffffff;border: 1px solid #000000;border-collapse: collapse;" summary="Branch Sort Code">
          <tr>
            <td style="border: 1px solid #000000;padding: 0;width: 180px;height: 30px;text-align: left;">{$dd_details.bank_identification_number}</td>
          <tr>
        </table>
        </p>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0 5;">
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">{ts}To the Manager{/ts}<span style="margin-left: 4em;">{ts}Bank/Building Society{/ts}</span></span></div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><br />{$dd_details.bank_name}</div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">{ts}Branch{/ts}</span><span style="margin-left: 3em;">{$dd_details.branch}</span></div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">{ts}Address{/ts}</span></div>
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
        <h1 style="font-size: 1.3em; text-align: left; margin: 0 0;">{ts}Instruction to your Bank or Building Society to pay by Direct Debit{/ts}</h1>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">{ts}Service User Number{/ts}</h2>
        <p>
        <table style="background-color: #ffffff;border: 1px solid #000000;border-collapse: collapse;" summary="Branch Sort Code">
          <tr>
            {foreach from=$dd_details.sunParts item=singleDigit}
              <td style="border: 1px solid #000000;padding: 0;width: 30px;height: 30px;text-align: center;">{$singleDigit}</td>
            {/foreach}
          <tr>
        </table>
        </p>
        <h2 style="font-size: 1em;margin-bottom: -5px; margin-top: 15px;text-align: left;font-weight: bold;">{ts}Reference:{/ts}</h2>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0 5;">
          {$dd_details.ddi_reference}
        </div>
        </p>
        <h2 style="font-size: 1em;text-align: left;font-weight: bold;margin-bottom: 3px; margin-top: 15px;">{ts}Instruction to your Bank or Building Society{/ts}</h2>
        <p>
          {ts 1=$dd_details.company_address.company_name_sd}Please pay %1 Direct Debits from the account detailed in this Instruction subject to the safeguards
            assured by the Direct Debit Guarantee. I understand that this Instruction may remain with %1 and, if so, details will be passed electronically
            to my Bank / Building Society.{/ts}
        </p>
        <p>
        <div style="background-color: #ffffff;border: 1px solid #999999;padding: 0 5;">
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"><span style="font-weight: bold;">{ts}Date{/ts}</span><span style="margin-left: 1em;">{$dd_details.today|crmDate}</span></div>
          <div style="border-bottom: 1px solid #dddddd;margin-top: 15px;"></div>
        </div>
        </p>
      </div>
      <div style="clear: both;"></div>
      <div>
        <p style="text-align: center;">
          {ts}Banks and Building Societies may not accept Direct Debit Instructions for some types of account.{/ts}
        </p>
      </div>
    </div>
    {* End of DDI *}
    <div class="clear" style="padding: 10px"></div>
    <div style="border: 3px solid #000000;background-color: #ffffff;width: 95%; padding: 15px">
      <div style="font-family: Arial,Helvetica,Monaco; font-size: 28px; line-height: 35px">
        <span style="float: right; vertical-align: top"><img src="{crmResURL ext=org.civicrm.smartdebit file=images/direct_debit_small.png}" alt="Direct Debit Logo" border="0"></span>
        <strong>{ts}The Direct Debit Guarantee{/ts}</strong>
      </div>
      <div class="clear"></div>
      <ul style="margin: 0; padding: 10px 20px 10px 20px">
        <li style="margin-bottom: 10px">{ts}This Guarantee is offered by all banks and building societies that accept instructions to pay Direct Debits.{/ts}</li>
        <li style="margin-bottom: 10px">{ts 1=$dd_details.company_address.company_name_sd 2=$dd_details.notice_period}If there are any changes to the amount, date or frequency of your
            Direct Debit %1 will notify you %2 working days in advance of your account being debited or as otherwise agreed. If you
            request %1 to collect a payment, confirmation of the amount and date will be given to you at the time of the request.{/ts}</li>
        <li style="margin-bottom: 10px">{ts 1=$dd_details.company_address.company_name_sd}If an error is made in the payment of your Direct Debit, by %1 or your bank or building society,
            you are entitled to a full and immediate refund of the amount paid from your bank or building society.{/ts}
          <ul>
            <li style="margin-bottom: 10px">{ts 1=$dd_details.company_address.company_name_sd}If you receive a refund you are not entitled to, you must pay it back when %1 asks you to.{/ts}</li>
          </ul>
        </li>
        <li>{ts 1=$dd_details.company_address.company_name_sd}You can cancel a Direct Debit at any time by simply contacting your bank or building society. Written
            confirmation may be required. Please also notify us.{/ts}</li>
      </ul>
    </div>
    <div class="clear"></div>
  </div>
</div>
