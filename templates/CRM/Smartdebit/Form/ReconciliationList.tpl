{if $totalMandates eq 0}
  <h3>No Smart Debit records found!</h3>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
{else}
    <table>
        <tr>
            <td>
                <div class="help">
                    Select a filter from the list below to view mismatches.
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
                        <li><a href="{crmURL p='civicrm/smartdebit/reconciliation/list' q='checkPayerReference=1' h=0}">Show All Mandates where CiviCRM Contact ID and Smart Debit Payer Reference do not match</a></li>
                    </ul>
                </div>
            </td>
        </tr>
    </table>
<h3>{ts}Mis-Matched Contacts ({$totalRows} found for current filter){/ts}</h3>
<div style="min-height:400px;">
    <table  class="selector row-highlight">
        <tr style="background-color: #CDE8FE;">
            <td><b>{ts}Transaction ID{/ts}</td>
            <td><b>{ts}Contact (SD Contact ID){/ts}</td>
            <td><b>{ts}Differences{/ts}</td>
            <td><b>{ts}Frequency{/ts}</td>
            <td><b>{ts}Total{/ts}</td>
            <td><b>{ts}Status{/ts}</td>
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
                    <a href="{$contactViewURL}">{$row.contact_name}</a>
                {else}
                  {$row.contact_name} ({$row.sd_contact_id})
                {/if}
              </td>
              <td>{$row.differences}</td>
            {if $row.contribution_recur_id }
                <td>{$row.frequency}/{$row.sd_frequency}</td>
                <td>{$row.amount}/{$row.sd_amount}</td>
                <td>{$row.contribution_status_id}/{$row.sd_contribution_status_id}</td>
            {else}
                <td>{$row.sd_frequency_factor} {$row.sd_frequency_type}</td>
                <td>{$row.sd_amount}</td>
                <td>{$row.sd_contribution_status_id}</td>
            {/if}
              <td>
                {if $row.fix_me_url}
                    <a href="{$row.fix_me_url}" target="_new">Fix Me</a>
                {/if}
              </td>
          </tr>
      {/foreach}
    </table>
</div>
{/if}
