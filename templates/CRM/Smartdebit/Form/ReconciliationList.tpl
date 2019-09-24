{* https://civicrm.org/licensing *}

{if $totalMandates eq 0}
  <h3>No Smart Debit records found!</h3>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
{else}
  <div class="help">
    <span><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> Total Mandates synced from Smart Debit: {$totalMandates}</span><br/>
    <span><i class="crm-i fa fa-question-circle" aria-hidden="true"></i>  If you need to refresh mandates from Smart Debit: <a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='sync=1' h=0}">Click Here</a></span>
  </div>
{/if}
{if $totalMandates gt 0}
<h3>Filters</h3>
    <table>
        <tr>
            <td>
                <div class="help">
                    <span><i class="crm-i fa fa-question-circle" aria-hidden="true"></i> Select a filter from the list below to view mismatches.</span>
                </div>
                <div style="crm-form">
                    <h4>Show All Mandates Missing from CiviCRM:</h4>
                    <ul>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkMissingFromCivi=1&hasContact=1&hasAmount=1' h=0}">With Contact in CiviCRM and has Amount</a></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkMissingFromCivi=1&hasContact=1&hasAmount=0' h=0}">With Contact in CiviCRM and no Amount</a></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkMissingFromCivi=1&hasContact=0&hasAmount=1' h=0}">With No Contact in CiviCRM and has Amount</a></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkMissingFromCivi=1&hasContact=0&hasAmount=0' h=0}">With No Contact in CiviCRM and no Amount</a></li>
                    </ul>
                    <h4>Other Filters:</h4>
                    <ul>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkMissingFromSD=1' h=0}">Show All Mandates Missing from Smart Debit</a><br /></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkAmount=1' h=0}">Show All Mandates with Differing Amounts</a><br /></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkFrequency=1' h=0}">Show All Mandates with Differing Frequencies</a><br /></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkStatus=1' h=0}">Show All Mandates with Differing Status</a><br /></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkDate=1' h=0}">Show All Mandates with Differing Start Dates</a><br /></li>
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkPayerReference=1' h=0}">Show All Mandates where CiviCRM Contact ID and Smart Debit Payer Reference do not match</a></li>
                    </ul>
                </div>
            </td>
        </tr>
    </table>
<h3>{ts}Mis-Matched Contacts ({$totalRows} found for current filter){/ts}</h3>
{if $fixMeContact}
<div class="help">
<span><i class="crm-i fa fa-info-circle" aria-hidden="true"></i>
For "Payer Reference" errors you need to login to the Smart Debit control panel and update the Payer Reference manually.
</span>
</div>
{/if}
<div style="min-height:400px;">
    <table  class="selector row-highlight">
        <tr style="background-color: #CDE8FE;">
            <td><b>{ts}Transaction ID{/ts}</td>
            <td><b>{ts}Contact (SD Contact ID){/ts}</td>
            <td><b>{ts}Differences{/ts}</td>
            <td><b>{ts}Frequency (CiviCRM/SD){/ts}</td>
            <td><b>{ts}Start Date (CiviCRM/SD){/ts}</td>
            <td><b>{ts}Total (CiviCRM/SD){/ts}</td>
            <td><b>{ts}Status (CiviCRM/SD){/ts}</td>
            <td></td>
        </tr>
      {foreach from=$listArray item=row}
        {assign var=id value=$row.id}
        {assign var=rContactId value=$row.contact_id}
        {assign var=rContributionRecurId value=$row.contribution_recur_id}
        {capture assign=recurContributionViewURL}{crmURL p='civicrm/contact/view/contributionrecur' q="reset=1&id=$rContributionRecurId&cid=$rContactId"}{/capture}
        {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$rContactId"}{/capture}
          <tr class="{cycle values="odd-row,even-row"}">
              <td>
                {if $row.contribution_recur_id }
                    <a href="{$recurContributionViewURL}">{$row.transaction_id}</a>
                {else}
                  {$row.transaction_id}
                {/if}
              </td>
              <td>
                {if $row.contact_id gt 0}
                    <a href="{$contactViewURL}">{$row.contact_name}</a> ({$row.sd_contact_id})
                {else}
                  {$row.contact_name} ({$row.sd_contact_id})
                {/if}
              </td>
              <td>{$row.differences}</td>
            {if $row.contribution_recur_id }
                <td>{$row.frequency_interval} {$row.frequency_unit}/{$row.sd_frequency_factor} {$row.sd_frequency_type}</td>
                <td>{$row.start_date}/{$row.sd_start_date}</td>
                <td>{$row.amount}/{$row.sd_amount}</td>
                <td>{$row.contribution_status_id}/{$row.sd_contribution_status_id}</td>
            {else}
                <td>{$row.sd_frequency_factor} {$row.sd_frequency_type}</td>
                <td>{$row.sd_start_date}</td>
                <td>{$row.sd_amount}</td>
                <td>{$row.sd_contribution_status_id}</td>
            {/if}
              <td>
                {if $row.transaction_id}
                    {assign var=rTransactionId value=$row.transaction_id}
                    {capture assign=transactionViewURL}{crmURL p='civicrm/smartdebit/payerdetails' q="action=view&reference_number=$rTransactionId&context=reconciliation"}{/capture}
                    <a href="{$transactionViewURL}" class="action-item crm-hover-button" title="Transaction Details">Details</a>
                {/if}
                {if $row.fix_me_url}
                    <a href="{$row.fix_me_url}" target="_new" class="action-item crm-hover-button" title="Fix Transaction">Fix Me</a>
                {/if}
              </td>
          </tr>
      {/foreach}
    </table>
</div>
{/if}
