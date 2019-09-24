{* https://civicrm.org/licensing *}

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
        <td>
          <strong>Address:</strong>
        </td>
        <td>
          <span>{$SDMandateArray.address_1}</span><br>
          {if $SDMandateArray.address_2}<span>{$SDMandateArray.address_2}</span><br>{/if}
          {if $SDMandateArray.address_3}<span>{$SDMandateArray.address_3}</span><br>{/if}
          <span>{$SDMandateArray.town}</span><br>
          {if $SDMandateArray.county}<span>{$SDMandateArray.county}</span><br>{/if}
          <span>{$SDMandateArray.postcode}</span><br>
        </td>
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
