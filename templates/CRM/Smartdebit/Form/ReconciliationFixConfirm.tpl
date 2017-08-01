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

<h3>{ts}Contact Membership and Recurring Contribution Details{/ts}</h3>
<div class="crm-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  {$form.reference_number.html}
  {$form.cid.html}
  {$form.mid.html}
  {$form.cr_id.html}
  <table class="crm-info-panel">
    <tr>
      <td width="30%">
        <strong>Smart Debit Reference:</strong>
      </td>
      <td>
        {$reference_number}
      </td>
    </tr>
    <tr>
      {if !empty($aContact) }
        <td>
          <strong>Name:</strong>
        </td>
        <td>
          {$aContact.display_name}
        </td>
      {/if}
    </tr>
    <tr>
      {if !empty($aAddress) }
        <td>
          <strong>Address:</strong>
        </td>
        <td>
          {$aAddress.street_address} <br />
          {$aAddress.city} <br />
          {$aAddress.country_id} <br />
          {$aAddress.postal_code} <br />
        </td>
      {/if}
    </tr>
    {if !empty($aMembership)}
      <tr>
        <td>
          <strong>Membership:</strong>
        </td>
        <td></td>
      </tr>
      <tr>
        <td>
          Type
        </td>
        <td>
          {$aMembership.type}
        </td>
      </tr>
      <tr>
        <td>
          Status
        </td>
        <td>
          {$aMembership.status}
        </td>
      </tr>
      <tr>
        <td>
          Start Date
        </td>
        <td>
          {$aMembership.start_date}
        </td>
      </tr>
      <tr>
        <td>
          End Date
        </td>
        <td>
          {$aMembership.end_date}
        </td>
      </tr>
    {/if}
    {if !empty($aContributionRecur)}
      <tr>
        <td>
          <strong>Recurring Contribution:</strong>
        </td>
        <td></td>
      </tr>
      <tr>
        <td>
          Contribution Status
        </td>
        <td>
          {$aContributionRecur.status}
        </td>
      </tr>
      <tr>
        <td>
          Amount
        </td>
        <td>
          {$aContributionRecur.amount}
        </td>
      </tr>
      <tr>
        <td>
          Payment Processor
        </td>
        <td>
          {$aContributionRecur.payment_processor} <br />
        </td>
      </tr>
    {else}
      <tr>
        <td>
          <strong>Recurring Contribution:</strong>
        </td>
        <td><strong>A new recurring contribution will be created</strong></td>
      </tr>
    {/if}
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>