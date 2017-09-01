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
    <div class="crm-block crm-smartdebit-apistatus-block">
      <div class="crm-section">
        <h3>Smart Debit API Connection</h3>
        <div>{$apiStatus}</div>
      </div>
    </div>
    <div class="clear"></div>
  {/if}

  {if $sdStatus}
    <div class="crm-block crm-smartdebit-apistatus-block">
      <div class="crm-section">
        <h3>Smart Debit API Status</h3>
        <div class="label">Response</div>
        <div class="content">{$sdStatus.statuscode} {$sdStatus.message} {$sdStatus.error}&nbsp;</div>
      </div>
      <div class="crm-section">
        <div class="label">API Version</div>
        <div class="content">{$sdStatus.api_version}&nbsp;</div>
      </div>
      <div class="crm-section">
        <div class="label">Service Users</div>
        <div class="content">
          {foreach from=$sdStatus.user.assigned_service_users.service_user item=pslid}
            {$pslid}
          {/foreach}
        </div>
      </div>
    </div>
    <div class="clear"></div>
  {/if}

  {if $sdStatusTest}
    <div class="crm-block crm-smartdebit-apistatustest-block">
      <div class="crm-section">
        <h3>Smart Debit Test API Status</h3>
        <div class="label">Response</div>
        <div class="content">{$sdStatusTest.statuscode} {$sdStatusTest.message} {$sdStatusTest.error}&nbsp;</div>
      </div>
      <div class="crm-section">
        <div class="label">API Version</div>
        <div class="content">{$sdStatusTest.api_version}&nbsp;</div>
      </div>
      <div class="crm-section">
        <div class="label">Service Users</div>
        <div class="content">
          {foreach from=$sdStatusTest.user.assigned_service_users.service_user item=pslid}
            {$pslid}
          {/foreach}
        </div>
      </div>
    </div>
    <div class="clear"></div>
  {/if}

  <h3>Configuration</h3>
  {foreach from=$elementNames item=elementName}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label} {help id=$elementName title=$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}</div>
      <div class="clear"></div>
    </div>
  {/foreach}

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
