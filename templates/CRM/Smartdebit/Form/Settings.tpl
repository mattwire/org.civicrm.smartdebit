{* https://civicrm.org/licensing *}

<div class="crm-block crm-form-block crm-admin-options-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <h2>Configuration</h2>

  {foreach from=$elementGroups item=elementGroup}
    <div class="clear">
      <br />
      <h3>{$elementGroup.title}</h3>
      <div class="help">{$elementGroup.description}</div>
      <table class="form-layout-compressed">
        {foreach from=$elementGroup.elementNames item=elementName}
          <tr><td>
            {$form.$elementName.html}
            <label for="{$elementName}">{$form.$elementName.label} {help id=$elementName title=$form.$elementName.label}</label>
            </td></tr>
        {/foreach}
      </table>
    </div>
  {/foreach}

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
