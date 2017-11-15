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
    <p>{ts}CiviCRM will attempt to retrieve Collection Reports, AUDDIS and ARUDD records from Smartdebit for a {$period} period ending with the date you specify here.{/ts}</p>
  </div>
  <div class="help">
        <span><i class="crm-i fa-info-circle" aria-hidden="true"></i> {ts}The Smartdebit scheduled job automatically synchronises and caches the latest daily collection report.
          Collection reports older than {$period} will be removed from the local cache.{/ts}
          <ul>
            <li>{ts}If you specify a date here the local cache will be cleared and {$period} up to the specified date will be retrieved (this may take a few minutes).{/ts}</li>
            <li>{ts}If you don't specify a date the cached data will not be modified and ONLY the latest daily report will be retrieved.{/ts}</li>
            <li><strong>{ts}If you have recently installed the smartdebit extension you should specify today's date here to force an update of the local cache for the last {$period} period.{/ts}</strong></li>
          </ul>
      </span>
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
        dateFormat: 'yy-mm-dd', time: false, allowClear: true
      };
      cj('#collection_date').crmDatepicker(dateOptions);
    </script>
    {/literal}
  </div><br />
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
