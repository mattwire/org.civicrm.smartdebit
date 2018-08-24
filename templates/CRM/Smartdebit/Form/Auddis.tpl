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

<div class="help">
  <span><i class="crm-i fa fa-info-circle" aria-hidden="true"></i> {ts}The below tables summarise the data available from SmartDebit in the selected AUDDIS/ARUDD files and collection reports.{/ts}</span><br />
  <strong>{ts}To synchronise CiviCRM from Smartdebit click continue.{/ts}</strong>
</div>

<div class="crm-block crm-form-block crm-smartdebit-sync-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <h3>{ts}Summary{/ts}</h3>
  <table class="form-layout">
    <tr>
      <th><b>{ts}Description{/ts}</th>
      <th style="text-align: right"><b>{ts}Number{/ts}</th>
      <th style="text-align: right"><b>{ts}Amount{/ts}</th>
    </tr>
    {foreach from=$summary key=linkrel item=sum}
      <tr>
        {if $linkrel eq 'total'}
          <td><strong>{$sum.description}</strong></td>
          <td style="text-align: right"><strong>{$sum.count}</strong></td>
          <td style="text-align: right"><strong>{$sum.total|crmMoney}</strong></td>
        {else}
          <td><a href="#{$linkrel}">{$sum.description}</a></td>
          <td style="text-align: right">{$sum.count}</td>
          <td style="text-align: right">{$sum.total|crmMoney}</td>
        {/if}
      </tr>
    {/foreach}
  </table>

  <a name="rejected_auddis"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.rejected_auddis.description} ({$summary.rejected_auddis.count})
    </div>
    <div class="crm-accordion-body">

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
                <a href="{$contactViewURL}" target="_blank">{$auddis.contact_name}</a>
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
          <td><b>{ts}Total{/ts} {$summary.rejected_auddis.description}</td>
          <td style="text-align: right"><b>{ts}{$summary.rejected_auddis.total|crmMoney}{/ts}</td>
        </tr>
      </table>
    </div>
  </div>

  <a name="rejected_arudd"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.rejected_arudd.description} ({$summary.rejected_arudd.count})
    </div>
    <div class="crm-accordion-body">

      <table class="form-layout">
        {foreach from=$newAruddRecords item=arudd}
          {if $arudd.arudd_date}
            <tr>
              <th colspan="5"><strong>ARUDD Report: {$arudd.arudd_date|crmDate}</strong></th>
            </tr>
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
                <a href="{$contactViewURL}" target="_blank">{$arudd.contact_name}</a>
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
          <td><b>{ts}Total{/ts} {$summary.rejected_arudd.description}</td>
          <td style ="text-align: right"><b>{ts}{$summary.rejected_arudd.total|crmMoney}{/ts}</td>
        </tr>
      </table>
    </div>
  </div>

  <a name="contributions_already_processed"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.contributions_already_processed.description} ({$summary.contributions_already_processed.count})
    </div>
    <div class="crm-accordion-body">
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
            <td>
              {if $row.contribution_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {assign var=contributionId value=$row.contribution_id}
                {capture assign=contributionViewURL}{crmURL p='civicrm/contact/view/contribution' q="reset=1&id=$contributionId&cid=$contactId&action=view&context=contribution"}{/capture}
                <a href="{$contributionViewURL}" target="_blank">{$row.transaction_id}</a>
              {else}
                {$row.transaction_id}
              {/if}
            </td>
            <td>
              {if $row.contact_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                <a href="{$contactViewURL}" target="_blank">{$row.contact_name}</a>
              {else}
                {$row.contact_name}
              {/if}
            </td>
            <td>{$row.frequency}</td>
            <td>{$row.receive_date|crmDate}</td>
            <td style="text-align: right">{$row.amount|crmMoney}</td>
          </tr>
        {/foreach}
        <br/>
        <tr>
          <td></td>
          <td></td>
          <td></td>
          <td><b>{ts}Total{/ts} {$summary.contributions_already_processed.description}</td>
          <td style="text-align: right"><b>{ts}{$summary.contributions_already_processed.total|crmMoney}{/ts}</td>
        </tr>
      </table>
    </div>
  </div>

  <a name="contributions_matched"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.contributions_matched.description} ({$summary.contributions_matched.count})
    </div>
    <div class="crm-accordion-body">
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
            <td>
              {if $row.contribution_recur_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {assign var=contributionRecurId value=$row.contribution_recur_id}
                {capture assign=contributionRecurViewURL}{crmURL p='civicrm/contact/view/contributionrecur' q="reset=1&id=$contributionRecurId&cid=$contactId&context=contribution"}{/capture}
                <a href="{$contributionRecurViewURL}" target="_blank">{$row.transaction_id}</a>
              {else}
                {$row.transaction_id}
              {/if}
            </td>
            <td>
              {if $row.contact_id gt 0}
                {assign var=contactId value=$row.contact_id}
                {capture assign=contactViewURL}{crmURL p='civicrm/contact/view' q="reset=1&cid=$contactId"}{/capture}
                <a href="{$contactViewURL}" target="_blank">{$row.contact_name}</a>
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
          <td><b>{ts}Total{/ts} {$summary.contributions_matched.description}</td>
          <td style ="text-align: right"><b>{ts}{$summary.contributions_matched.total|crmMoney}{/ts}</td>
        </tr>
      </table>
    </div>
  </div>

  <a name="contributions_not_matched"></a>
  <div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
      {$summary.contributions_not_matched.description} ({$summary.contributions_not_matched.count})
    </div>
    <div class="crm-accordion-body">

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
          <td><b>{ts}Total{/ts} {$summary.contributions_not_matched.description}</td>
          <td style ="text-align: right"><b>{ts}{$summary.contributions_not_matched.total|crmMoney}{/ts}</td>
        </tr>
      </table>
    </div>
  </div>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
