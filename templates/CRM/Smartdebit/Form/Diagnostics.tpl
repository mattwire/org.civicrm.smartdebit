{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-smartdebit-settings-form-block">
    {if $apiStatus}
      <h3>Smart Debit API Connection</h3>
      <div>{$apiStatus}</div>
      <div class="clear"></div>
    {/if}

    {if $sdStatus}
      <h3>Smart Debit API Status</h3>
      <table class="form-layout-compressed"><tbody>
        <tr><td>
            <label>Response</label>
                {$sdStatus.statuscode} {$sdStatus.message} {$sdStatus.error}&nbsp;
          </td></tr>
        <tr><td>
            <label>API Version</label>
                {$sdStatus.api_version}
          </td></tr>

        <tr><td>
            <label>Service Users</label>
                {foreach from=$sdStatus.user.assigned_service_users.service_user item=pslid}
                    {$pslid}
                {/foreach}
          </td></tr>

        </tbody></table>
    {/if}

    {if $sdStatusTest}
      <h3>Smart Debit Test API Status</h3>
      <table class="form-layout-compressed"><tbody>
        <tr><td>
            <label>Response</label>
                {$sdStatusTest.statuscode} {$sdStatusTest.message} {$sdStatusTest.error}
          </td></tr>
        <tr><td>
            <label>API Version</label>
                {$sdStatusTest.api_version}
          </td></tr>
        <tr><td>
            <label>Service Users</label>
                {foreach from=$sdStatusTest.user.assigned_service_users.service_user item=pslid}
                    {$pslid}
                {/foreach}
          </td></tr>
        </tbody></table>
    {/if}

  <h3>Mandates</h3>
  <table class="form-layout-compressed"><tbody>
    <tr><td>
        <label>Cached Mandates linked to recurring contributions:</label>
            {$sdcounts.mandatewithrecur}
      </td></tr>
    <tr><td>
        <label>Cached Mandates with no recurring contribution:</label>
            {$sdcounts.mandatenorecur}
      </td></tr>
    </tbody></table>

  <h3>Collections</h3>
  <table class="form-layout-compressed"><tbody>
    <tr><td>
        <label>Cached Successful Collections:</label>
            {$sdcounts.collectionssuccess}
      </td></tr>
    <tr><td>
        <label>Cached Failed Collections:</label>
            {$sdcounts.collectionsrejected}
      </td></tr>
    <tr><td>
        <label>Cached Collection Reports:</label>
            {$sdcounts.collectionreports}
      </td></tr>
    </tbody></table>

  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      Cached Collection Reports
    </div>
    <div class="crm-accordion-body">
      <table class="form-layout-compressed"><tbody>
        <tr>
          <th>{ts}Collection Date{/ts}</th>
          <th>{ts}Successful Amount{/ts}</th>
          <th>{ts}Successful Number{/ts}</th>
          <th>{ts}Rejected Amount{/ts}</th>
          <th>{ts}Rejected Number{/ts}</th>
        </tr>
        {foreach from=$collectionreports item=report}
          <tr class="{cycle values="odd-row,even-row"}">
            <td>{$report.collection_date|crmDate}</td>
            <td>{$report.success_amount|crmMoney:GBP}</td>
            <td>{$report.success_number}</td>
            <td>{$report.reject_amount|crmMoney:GBP}</td>
            <td>{$report.reject_number}</td>
          </tr>
        {/foreach}
        </tbody></table>
    </div>
  </div>
</div>
