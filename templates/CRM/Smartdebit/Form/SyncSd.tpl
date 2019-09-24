{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-smartdebit-sync-form-block">
  <div class="description">
    <h3>{ts}Showing available dates from <strong>{$dateOfCollectionStart}</strong> to <strong>{$dateOfCollectionEnd}</strong>{/ts}</h3>
    <h3>Latest Collection Report</h3>
    <table class="form-layout-compressed"><tbody>
      <tr>
        <th>{ts}Collection Date{/ts}</th>
        <th>{ts}Successful Amount{/ts}</th>
        <th>{ts}Successful Number{/ts}</th>
        <th>{ts}Rejected Amount{/ts}</th>
        <th>{ts}Rejected Number{/ts}</th>
      </tr>
      {foreach from=$collectionreports item=report}
        <tr>
          <td>{$report.collection_date|crmDate}</td>
          <td>{$report.success_amount|crmMoney}</td>
          <td>{$report.success_number}</td>
          <td>{$report.reject_amount|crmMoney}</td>
          <td>{$report.reject_number}</td>
        </tr>
      {/foreach}
      </tbody></table>
  </div>
  <div class="help">
    <span><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> {ts}Terms:{/ts}</span>
    <ul>
      <li>AUDDIS: Automated Direct Debit Instruction Service (payment reports)</li>
      <li>ARUDD: Automated Return of Unpaid Direct Debit (failure reports)</li>
    </ul>
  </div>
  <h2>{ts}Select the AUDDIS and ARUDD dates that you wish to process now:{/ts}</h2>
  {if ($groupCount > 0)}
  <div id="id-additional" class="form-item">
    <div class="crm-accordion-wrapper">
      <div class="crm-accordion-header">
        {ts}Include Auddis Date(s){/ts}
      </div>
      <div class="crm-accordion-body">
        {strip}

          <table>
            {if $groupCount > 0}
              <tr><td class="label">{$form.includeAuddisDate.label}</td></tr>
              <tr><td>{$form.includeAuddisDate.html}</td></tr>
            {/if}

          </table>

        {/strip}
      </div>
    </div>
    {else}
    <h3>{ts}No AUDDIS dates found for selected date range.{/ts}</h3>
    {/if}
    <br /><br />
    {if ($groupCountArudd > 0)}
    <div id="id-additional" class="form-item">
      <div class="crm-accordion-wrapper">
        <div class="crm-accordion-header">
          {ts}Include Arudd Date(s){/ts}
        </div>
        <div class="crm-accordion-body">
          {strip}

            <table>
              {if $groupCountArudd > 0}
                <tr><td class="label">{$form.includeAruddDate.label}</td></tr>
                <tr><td>{$form.includeAruddDate.html}</td></tr>
              {/if}

            </table>

          {/strip}
        </div>
      </div>
      {else}
      <h3>{ts}No ARUDD dates found for selected date range.{/ts}</h3>
      {/if}
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl"}
      </div>
    </div>
    {literal}
      <script type="text/javascript">
        cj(function() {
          cj().crmAccordionToggle();
        });
      </script>
    {/literal}
  </div>
</div>
