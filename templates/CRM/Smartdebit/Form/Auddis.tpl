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

<h3>{ts}Select the AUDDIS and ARUDD dates that you wish to process now:{/ts}</h3>
<div class="help">
    <span><i class="crm-i fa-info-circle" aria-hidden="true"></i> {ts}The below tables summarise the data available from SmartDebit in the selected AUDDIS/ARUDD files and collection reports.{/ts}</span><br />
    <strong>{ts}To synchronise CiviCRM from Smartdebit click continue.{/ts}</strong>
</div>
<div class="crm-block crm-form-block crm-export-form-block">
    <div class="crm-block crm-form-block crm-campaignmonitor-sync-form-block">
        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="top"}
        </div>
    </div>
    <h3>{ts}Summary{/ts}</h3>
    <table class="form-layout">
        <tr>
            <th><b>{ts}Description{/ts}</th>
            <th style="text-align: right"><b>{ts}Number{/ts}</th>
            <th style="text-align: right"><b>{ts}Amount{/ts}</th>
        </tr>
        {foreach from=$summary key=description item=sum}
            <tr>
                <td>{$description}</td>
                <td style="text-align: right">{$sum.count}</td>
                <td style="text-align: right">{$sum.total}</td>
            </tr>
        {/foreach}
        <tr>
            <td><strong>{ts}Total{/ts}</strong></td>
            <td style="text-align: right"><strong>{$summaryNumber}</strong></td>
            <td style="text-align: right"><strong>{$totalSummaryAmount}</strong></td>
        </tr>
    </table>
    <br>
    <h3>{ts}Rejected Contributions in the AUDDIS{/ts}</h3>
    <table class="form-layout">
        <tr>
            <th><b>{ts}Reference{/ts}</th>
            <th><b>{ts}Contact (SD Contact ID){/ts}</th>
            <th><b>{ts}Frequency{/ts}</th>
            <th><b>{ts}Reason code{/ts}</th>
            <th><b>{ts}Effective Date{/ts}</th>
            <th style="text-align: right"><b>{ts}Total{/ts}</th>
        </tr>
        {foreach from=$newAuddisRecords item=auddis}
            <tr>
                <td>{$auddis.reference}</td>
                <td>
                    {if $auddis.contribution_recur_id gt 0}
                        {assign var=contactId value=$auddis.contact_id}
                        {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                        <a href="{$contactViewURL}">{$auddis.contact_name}</a>
                    {else}
                        {$auddis.contact_name} ({$auddis.contact_id})
                    {/if}
                </td>
                <td>{$auddis.frequency}</td>
                <td>{$auddis.reason}</td>
                <td>{$auddis.start_date|crmDate}</td>
                <td style="text-align: right">{$auddis.amount|crmMoney}</td>
            </tr>
        {/foreach}
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><b>{ts}Total Rejected Contributions in AUDDIS{/ts}</td>
            <td style="text-align: right"><b>{ts}{$totalRejected|crmMoney}{/ts}</td>
        </tr>
    </table>
    <br>
    <h3>{ts}Rejected Contributions in the ARUDD{/ts}</h3>
    <table class="form-layout">
        {foreach from=$newAruddRecords item=arudd}
            {if $arudd.arudd_date}
                <tr><th colspan="5"><strong>ARUDD Report: {$arudd.arudd_date|crmDate}</strong></th></tr>
                        <tr>
            <th><b>{ts}Reference{/ts}</th>
            <th><b>{ts}Contact{/ts}</th>
            <th><b>{ts}Reason code{/ts}</th>
            <th><b>{ts}Original Processing Date{/ts}</th>
            <th style="text-align: right"><b>{ts}Total{/ts}</th>
        </tr>
            {/if}
            <tr>
                <td>{$arudd.reference}</td>
                <td>
                    {if $arudd.contact_name}
                        {assign var=contactId value=$arudd.contact_id}
                        {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                        <a href="{$contactViewURL}">{$arudd.contact_name}</a>
                    {else}
                        {$arudd.contact_id}
                    {/if}
                </td>
                <td>{$arudd.reason}</td>
                <td>{$arudd.date|crmDate}</td>
                <td style="text-align: right">{$arudd.amount|crmMoney}</td>
            </tr>
        {/foreach}
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><b>{ts}Total Rejected Contributions in ARUDD{/ts}</td>
            <td style ="text-align: right"><b>{ts}{$totalRejectedArudd|crmMoney}{/ts}</td>
        </tr>
    </table>
    <br>
    <h3>{ts}Contributions already processed{/ts}</h3>
    <table class="form-layout">
        <tr>
            <th><b>{ts}Transaction ID{/ts}</th>
            <th><b>{ts}Contact{/ts}</th>
            <th><b>{ts}Frequency{/ts}</th>
            <th><b>{ts}Receive Date{/ts}</th>
            <th style="text-align: right"><b>{ts}Total{/ts}</th>
        </tr>
        {foreach from=$existArray item=row}
            {assign var=id value=$row.id}
            <tr>
                <td>{$row.transaction_id}</td>
                <td>
                    {if $row.contact_id gt 0}
                        {assign var=contactId value=$row.contact_id}
                        {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                        <a href="{$contactViewURL}">{$row.contact_name}</a>
                    {else}
                        {$row.contact_name}
                    {/if}
                </td>
                <td>{$row.frequency}</td>
                <td>{$row.start_date|crmDate}</td>
                <td style="text-align: right">{$row.amount|crmMoney}</td>
            </tr>
        {/foreach}
        <br/>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><b>{ts}Total Processed Contributions{/ts}</td>
            <td style="text-align: right"><b>{ts}{$totalExist}{/ts}</td>
        </tr>
    </table>
    <br>
    <h3>{ts}Contributions matched to contacts{/ts}</h3>
    <table class="form-layout">
        <tr>
            <th><b>{ts}Transaction ID{/ts}</th>
            <th><b>{ts}Contact{/ts}</th>
            <th><b>{ts}Frequency{/ts}</th>
            <th><b>{ts}Receive Date{/ts}</th>
            <th style="text-align: right"><b>{ts}Total{/ts}</th>
        </tr>
        {foreach from=$listArray item=row}
            {assign var=id value=$row.id}
            <tr>
                <td>{$row.transaction_id}</td>
                <td>
                    {if $row.contact_id gt 0}
                        {assign var=contactId value=$row.contact_id}
                        {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                        <a href="{$contactViewURL}">{$row.contact_name}</a>
                    {else}
                        {$row.contact_name}
                    {/if}
                </td>
                <td>{$row.frequency}</td>
                <td>{$row.receive_date|crmDate}</td>
                <td style ="text-align: right">{$row.amount|crmMoney}</td>
            </tr>
        {/foreach}
        <br/>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><b>{ts}Total Matched Contributions{/ts}</td>
            <td style ="text-align: right"><b>{ts}{$totalMatched}{/ts}</td>
        </tr>
    </table>
    <h3>{ts}Contributions not matched to contacts{/ts}</h3>
    <table class="form-layout">
        <tr>
            <th><b>{ts}Reference{/ts}</th>
            <th><b>{ts}Contact{/ts}</th>
            <th><b>{ts}Smart Debit Contact ID{/ts}</th>
            <th><b>{ts}Receive Date{/ts}</th>
            <th style ="text-align: right"><b>{ts}Total{/ts}</th>
        </tr>
        {foreach from=$missingArray item=row}
            {assign var=id value=$row.id}
            <tr>
                <td>{$row.transaction_id}</td>
                <td>
                    {$row.contact_name}
                </td>
                <td>{$row.contact_id}</td>
                <td>{$row.receive_date|crmDate}</td>
                <td style ="text-align: right">{$row.amount|crmMoney}</td>
            </tr>
        {/foreach}
        <br/>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td><b>{ts}Total Not Matched Contributions{/ts}</td>
            <td style ="text-align: right"><b>{ts}{$totalMissing}{/ts}</td>
        </tr>
    </table>
    <br>
    <div class="crm-block crm-form-block crm-campaignmonitor-sync-form-block">
        <div class="crm-submit-buttons">
            {include file="CRM/common/formButtons.tpl" location="bottom"}
        </div>
    </div>
</div>
