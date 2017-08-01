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

{if $status eq 1}
    <h3>{ts}{$totalValidContribution} Valid Contribution(s) synchronised with CiviCRM{/ts}</h3>
    <div style="min-height:400px;">
        <table class="form-layout">
        <thead>
            <tr>
                <th> Transaction Id </th>
                <th> Contact Name </th>
                <th> Amount </th>
                <th> Frequency </th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$ids item=row}
                <tr class="{cycle values="odd-row,even-row"}">
                    <td>{$row.transaction_id}</td>                   
                    <td>{$row.display_name}</td>                   
                    <td align="right">{$row.amount}</td>                   
                    <td>{$row.frequency}</td>                   
                </tr>
            {/foreach}
            {if $totalAmountAdded}
                <tr style="border-bottom:1pt solid black; border-top:1pt solid black;">
                    <td colspan="2"> <strong>Total Valid amount : </strong></td>
                    <td align="right"> <strong>{$totalAmountAdded} </strong></td>
                </tr>
            {/if}
        </tbody>
        </table>
    </div>
        <br />
        <br />
    <h3>{ts}{$totalRejectedContribution} Failed Contribution(s) synchronised with CiviCRM{/ts}</h3>
    <div style="min-height:400px;">
        <table class="form-layout">
            <thead>
            <tr>
                <th> Transaction Id </th>
                <th> Contact Name </th>
                <th> Amount </th>
                <th> Status </th>
            </tr>
            </thead>
            <tbody>
            {foreach from=$rejectedids item=row}
                <tr class="{cycle values="odd-row,even-row"}">
                    <td>
                        <a href="/civicrm/contact/view/contribution?reset=1&id={$row.id}&cid={$row.cid}&action=view&context=contribution&selectedChild=contribute">{$row.trxn_id}</a>
                    </td>
                    <td>
                        <a href="/civicrm/contact/view?reset=1&cid={$row.cid}">{$row.display_name}</a>
                    </td>
                    <td align="right">{$row.total_amount}</td>
                    <td>{$row.status}</td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>

{else}
      <h3>{ts}Please confirm that you wish to synchronise all matched transactions from SmartDebit into CiviCRM?{/ts}</h3>
      <div class="crm-block crm-form-block crm-campaignmonitor-sync-form-block">
        <div class="crm-submit-buttons">
          {include file="CRM/common/formButtons.tpl"}
        </div>
      </div>
{/if}
