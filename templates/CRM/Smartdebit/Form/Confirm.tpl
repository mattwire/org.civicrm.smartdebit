{* https://civicrm.org/licensing *}

{if $status eq 1}

<div class="help">
  <span>
    <i class="crm-i fa fa-info-circle" aria-hidden="true"></i>
    {ts}The below tables summarise what was updated in CiviCRM during the last (Scheduled/Manual) sync with SmartDebit.{/ts}
  </span>
</div>

<div class="crm-block crm-form-block crm-smartdebit-sync-form-block">
    <h3>{ts}Summary{/ts}</h3>
  <table class="form-layout">
    <tr>
      <th><b>{ts}Description{/ts}</th>
      <th style="text-align: right"><b>{ts}Number{/ts}</th>
      <th style="text-align: right"><b>{ts}Amount{/ts}</th>
    </tr>
    {foreach from=$summary key=linkrel item=sum}
      <tr>
          <td><a href="#{$linkrel}">{$sum.description}</a></td>
          <td style="text-align: right">{$sum.count}</td>
          <td style="text-align: right">{$sum.amount|crmMoney}</td>
      </tr>
    {/foreach}
  </table>

  <a name="success"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.success.description} ({$summary.success.count})
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Contact</th>
          <th>Amount</th>
          <th>Frequency</th>
          <th>Receive Date</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$successes item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>
              {if $row.contribution_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {assign var=contributionId value=$row.contribution_id}
                {capture assign=contributionViewURL}{crmURL p='civicrm/contact/view/contribution' q="action=view&reset=1&id=$contributionId&cid=$contactId"}{/capture}
                <a href="{$contributionViewURL}" target="_blank">{$row.transaction_id}</a>
              {/if}
            </td>
            <td>
              {if $row.contact_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                <a href="{$contactViewURL}" target="_blank">{$row.contact_name}</a>
              {/if}
            </td>
            <td align="right">{$row.amount|crmMoney}</td>
            <td>{$row.frequency}</td>
            <td>{$row.receive_date|crmDate}</td>
          </tr>
        {/foreach}
        {if $summary.success.amount}
          <tr style="border-bottom:1pt solid black; border-top:1pt solid black;">
            <td colspan="2"><strong>Total Successful amount:</strong></td>
            <td align="right"><strong>{$summary.success.amount|crmMoney}</strong></td>
          </tr>
        {/if}
        </tbody>
      </table>
    </div>
  </div>

  <a name="reject"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.reject.description} ({$summary.reject.count})
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout">
        <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Contact</th>
          <th>Amount</th>
          <th>Frequency</th>
          <th>Receive Date</th>
        </tr>
        </thead>
        <tbody>
        {foreach from=$rejects item=row}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>
              {if $row.contribution_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {assign var=contributionId value=$row.contribution_id}
                {capture assign=contributionViewURL}{crmURL p='civicrm/contact/view/contribution' q="action=view&reset=1&id=$contributionId&cid=$contactId"}{/capture}
                <a href="{$contributionViewURL}" target="_blank">{$row.transaction_id}</a>
              {/if}
            </td>
            <td>
              {if $row.contact_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                <a href="{$contactViewURL}" target="_blank">{$row.contact_name}</a>
              {/if}
            </td>
            <td align="right">{$row.amount|crmMoney}</td>
            <td>{$row.frequency}</td>
            <td>{$row.receive_date|crmDate}</td>
          </tr>
        {/foreach}
        {if $summary.reject.amount}
          <tr style="border-bottom:1pt solid black; border-top:1pt solid black;">
            <td colspan="2"><strong>Total Rejected amount:</strong></td>
            <td align="right"><strong>{$summary.reject.amount|crmMoney}</strong></td>
          </tr>
        {/if}
        </tbody>
      </table>
    </div>
  </div>
</div>

{else}
  <h3>{ts}Please confirm that you wish to synchronise all matched transactions from SmartDebit into CiviCRM?{/ts}</h3>
  <div class="crm-block crm-form-block crm-smartdebit-sync-form-block">
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>
{/if}
