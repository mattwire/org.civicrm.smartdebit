{* https://civicrm.org/licensing *}

<h3>{ts}CiviCRM will retrieve the latest Collection Reports, AUDDIS and ARUDD records from Smartdebit and create/update related contribution records.{/ts}</h3>
<div class="crm-block crm-form-block crm-smartdebit-sync-form-block">
  <div class="help">
        <span><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> {ts}The Smartdebit scheduled job automatically synchronises and caches the latest daily collection report.{/ts}
          {ts 1=$period}Collection reports older than %1 will be removed from the local cache.{/ts}<br />
      </span>
  </div>
  <div class="description">
    <h4><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> {ts}Automatic Synchronisation is {/ts}{if $sync_active}{ts}ENABLED{/ts}{else}{ts}DISABLED{/ts}{/if}</h4>
    {if $latestReportDate}<h4><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> {ts}The latest cached collection report is{/ts}: {$latestReportDate|crmDate}</h4>{/if}
    <br />
  </div>
  <div class="crm-smartdebit-sync-options">
    {$form.retrieve_collectionreport.label}
    {$form.retrieve_collectionreport.html}
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
</div>
