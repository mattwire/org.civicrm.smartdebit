{* https://civicrm.org/licensing *}

<h3>Details for Smart Debit Transaction ID: {$transactionId}</h3>
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="top"}
</div>

{foreach from=$smartDebitDetails item=detail}
  <div class="crm-section">
    <div class="label">{$detail.label}</div>
    <div class="content">{$detail.text}</div>
    <div class="clear"></div>
  </div>
{/foreach}

<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
