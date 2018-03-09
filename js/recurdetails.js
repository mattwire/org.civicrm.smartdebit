// https://civicrm.org/licensing

CRM.$(function() {
    var smartDetails = CRM.vars.smartdebit.recurdetails;
    if (!CRM.$.isEmptyObject(smartDetails)) {
        var targetHtml = '<h3>Smart Debit Mandate</h3><table class = "crm-info-panel crm-smartdebit-mandate">';
        for (var k in smartDetails) {
            if (smartDetails.hasOwnProperty(k)) {
                targetHtml = targetHtml.concat('<tr><td class="label">'+k+'</td><td>' +smartDetails[k] + '</td></tr>');
            }
        }
        targetHtml = targetHtml.concat('</table>');
        CRM.$(targetHtml).insertBefore('div.crm-recurcontrib-view-block div.crm-submit-buttons');
    }
});