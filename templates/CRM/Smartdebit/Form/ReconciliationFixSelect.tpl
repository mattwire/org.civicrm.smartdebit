{* https://civicrm.org/licensing *}

<h3>{ts}Contact Membership and Recurring Contribution Details{/ts}</h3>
<div class="crm-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  <table class="crm-info-panel">
    <tr>
      <td>
        <b>Smart Debit Details
      </td>
      <td></td>
    </tr>
    <tr>
      <td>{$form.first_name.label}</td>
      <td>{$SDMandateArray.first_name}</td>
    </tr>
    <tr>
      <td>{$form.last_name.label}</td>
      <td>{$SDMandateArray.last_name}</td>
    </tr>
    <tr>
      <td>{$form.email_address.label}</td>
      <td>{$SDMandateArray.email_address}</td>
    </tr>
    <tr>
      <td>{$form.default_amount.label}</td>
      <td>{$SDMandateArray.default_amount}</td>
    </tr>
    <tr>
      <td>{$form.start_date.label}</td>
      <td>{$SDMandateArray.start_date}</td>
    </tr>
    <tr>
      <td>{ts}Address{/ts}</td>
      <td>
        <span>{$SDMandateArray.address_1}</span><br>
        {if $SDMandateArray.address_2}<span>{$SDMandateArray.address_2}</span><br>{/if}
        {if $SDMandateArray.address_3}<span>{$SDMandateArray.address_3}</span><br>{/if}
        <span>{$SDMandateArray.town}</span><br>
        {if $SDMandateArray.county}<span>{$SDMandateArray.county}</span><br>{/if}
        <span>{$SDMandateArray.postcode}</span><br>
      </td>
    </tr>
    <tr>
      <td>
        {$form.reference_number.label} (Transaction Id)
      </td>
      <td>
        {$form.reference_number.html}
      </td>
    </tr>
    <tr>
      <td>
        {$form.contact_name.label}
      </td>
      <td>
        {$form.contact_name.html}
        {$form.cid.html}
      </td>
    </tr>
    <tr>
      <td>
        {$form.membership_record.label}
      </td>
      <td>
        {$form.membership_record.html}<br />
        <sub>( Membership Type / Membership Status / Start Date / End Date)</sub>
      </td>
    </tr>
    <tr>
      <td>
        {$form.contribution_recur_record.label}
      </td>
      <td>
        {$form.contribution_recur_record.html}<br />
        <sub>( Payment Processor / Contribution Status / Amount / Transaction Id)</sub>
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
{literal}
  <style>
    .crm-container select {
      width:500px;
    }
  </style>
{/literal}
<script type="text/javascript">
  {literal}
  var memStatusCurrent = {/literal}"{$memStatusCurrent}"{literal}; //MV assigned the membership Status Name 'Current' as constant
  var $form = cj("form.{/literal}{$form.formClass}{literal}");
  var cid = null;
  cj("#contact_name", $form).change(function() {
      var data = cj( '#contact_name' ).select2('data');
      ( data !== null) ? cid = data.id : cid = null;
      cj('input[name=cid]').val(cid);
      if (cid !== null) {
          cj('#membership_record').parents('tr').show();
          cj('#contribution_recur_record').parents('tr').show();
          cj('.crm-submit-buttons').show();
          getMembershipAndRecur(cid);
      }
      else {
          cj('#membership_record').parents('tr').hide();
          cj('#contribution_recur_record').parents('tr').hide();
          cj('.crm-submit-buttons').hide();
      }
  });

  function getMembershipAndRecur(cid) {
      var getTemplateContentUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Smartdebit_Page_AJAX&fnName=getMembershipByContactID&json=1'}";{literal}
      cj.ajax({
          url : getTemplateContentUrl,
          type: "POST",
          data: {selectedContact: cid},
          async: false,
          datatype:"json",
          success: function(data, status){
              cj('#membership_record').find('option').remove();
              cj('#contribution_recur_record').find('option').remove();
              var options = cj.parseJSON(data);
              cj.each(options, function(key, value) {
                  if(key === "membership"){
                      cj.each(value, function(memID, text) {
                          cj('#membership_record').append(cj('<option>', {
                              value: memID,
                              text : text
                          }));
                          //MV to set the current membership as default
                          var temp = text.split('/');
                          if( temp[1] === memStatusCurrent ){
                              cj('#membership_record option[value='+memID+']').attr('selected', true);
                          }
                          //end
                      });
                  }
                  if(key === "cRecur"){
                      cj.each(value, function(crID, Recurtext) {
                          cj('#contribution_recur_record').append(cj('<option>', {
                              value: crID,
                              text : Recurtext
                          }));
                      });
                  }
              });
          }
      });
  }
  cj(document).ready(function(){
      var cid = {/literal}"{$cid}"{literal};
      if (cid) {
          cj('#contact_name').parents('tr').hide();
          cj('#membership_record').parents('tr').show();
          cj('#contribution_recur_record').parents('tr').show();
          cj('.crm-submit-buttons').show();
          getMembershipAndRecur(cid);
      } else {
          cj('#membership_record').parents('tr').hide();
          cj('#contribution_recur_record').parents('tr').hide();
          cj('.crm-submit-buttons').hide();
      }
      // When membership option is changed to 'Donation', show only recurring contributions which are not linked to memberships.
      cj( "#membership_record" ).change(function() {
          var val = cj('#membership_record option:selected').text();
          var getTemplateContentUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 q='className=CRM_Smartdebit_Page_AJAX&fnName=getNotLinkedRecurringByContactID&json=1'}";{literal}
          var cid = cj('input[name=cid]').val();
          if (cid !== null) {
              cj.ajax({
                  url: getTemplateContentUrl,
                  type: "POST",
                  data: {selectedContact: cid},
                  async: false,
                  datatype: "json",
                  success: function (data, status) {
                      var options = cj.parseJSON(data);
                      if (val === 'Donation') {
                          populateRecur(options.cRecurNotLinked);
                      } else {
                          populateRecur(options.cRecur);
                      }
                  }
              });
          }
      });
      function populateRecur(opRecur) {
          cj('#contribution_recur_record').find('option').remove();
          cj.each(opRecur, function(crID, Recurtext) {
              cj('#contribution_recur_record').append(cj('<option>', {
                  value: crID,
                  text : Recurtext
              }));
          });
      }
  });
  {/literal}
</script>
