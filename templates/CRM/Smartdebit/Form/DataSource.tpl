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

<h3>{ts}CiviCRM will retrieve the latest Collection Reports, AUDDIS and ARUDD records from Smartdebit and create/update related contribution records.{/ts}</h3>
<div class="crm-block crm-form-block crm-smartdebit-sync-form-block">
  <div class="description">
    <h3>{ts}Automatic Synchronisation is {/ts}{if $sync_active}{ts}ENABLED{/ts}{else}{ts}DISABLED{/ts}{/if}</h3>
  </div>
  <div class="help">
        <span><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> {ts}The Smartdebit scheduled job automatically synchronises and caches the latest daily collection report.{/ts}
          {ts 1=$period}Collection reports older than %1 will be removed from the local cache.{/ts}<br />
      </span>
  </div>
  {$form.retrieve_collectionreport.label}
  {$form.retrieve_collectionreport.html}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl"}
  </div>
</div>
