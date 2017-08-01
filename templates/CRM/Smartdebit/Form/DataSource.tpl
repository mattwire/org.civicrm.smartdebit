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

<h3>{ts}Select the date you wish to import data for:{/ts}</h3>
<div class="crm-block crm-form-block crm-export-form-block">
  <div class="description">
    <p>{ts}CiviCRM will attempt to retrieve AUDDIS and ARUDD records for a {$period} period ending with the date you specify here.{/ts}</p>
  </div>
  <div class="help">
        <span><i class="crm-i fa-info-circle" aria-hidden="true"></i> {ts}This should not normally be necessary as the collection reports are retrieved daily by the SmartDebit scheduled job.
          <strong>If you specify a date here the collection report data will be refreshed and {$period} of data re-synced from SmartDebit (this will take a few minutes when you submit).</strong>
        If you don't specify a date the cached data will not be modified.{/ts}<br/>
      <strong>{ts}If you are importing latest payments (up to {$period} old) you should not need to enter a date here.{/ts}</strong></span>
  </div>
  <div class="crm-block crm-form-block crm-campaignmonitor-sync-form-block">
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>

  <div class="crm-block crm-form-block" >
    <div class="label">Collection Date: </div>
    <div class="content">
      <input id="collection_date" name="collection_date" type="text" value="{$collection_date}"/>
    </div>

    <script type="text/javascript">
      {literal}
      // Date picker
      var dateOptions = {
          dateFormat: 'yy-mm-dd', time: false, allowClear: true,
      };
      cj('#collection_date').crmDatepicker(dateOptions);
    </script>
    {/literal}
    </table>
  </div><br />
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
