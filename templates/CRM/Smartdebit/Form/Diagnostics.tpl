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

<div class="crm-block crm-form-block crm-smartdebit-settings-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

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
        {$sdcounts.collectionreportsuccess}
    </td></tr>
    <tr><td>
        <label>Cached Failed Collections:</label>
        {$sdcounts.collectionreportfailed}
    </td></tr>
  </tbody></table>

  <h3>Latest Collection Reports</h3>
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

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
