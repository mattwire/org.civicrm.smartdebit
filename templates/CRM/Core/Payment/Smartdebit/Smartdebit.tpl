<div class="help">
<div class="direct-debit-logo"><span style="float: right;margin: 25px;"><img src="{crmResURL ext=uk.co.vedaconsulting.smartdebit file=images/direct_debit_small.png}" alt="Direct Debit Logo" border="0"></span></div>
{ts}<p>All the normal Direct Debit safeguards and guarantees apply.
  No changes in the amount, date or frequency to be debited can be made without notifying you at least 10 working days in advance of your account being debited.
  In the event of any error, you are entitled to an immediate refund from your bank or building society.
  <strong>You have the right to cancel a Direct Debit Instruction at any time simply by writing to your bank or building society, with a copy to us.</strong></p>
  <p>The details of your Direct Debit Instruction will be sent to you within 3 working days or no later than 10 working days before the first collection.</p>{/ts}
  <span><i class="crm-i fa-exclamation-circle"></i><strong> You should not continue with this form if any of the following apply:</strong></span>
  <ul>
    <li>You are not the account holder.</li>
    <li>If it is a business account and more than one person is required to authorise debits on this account.</li>
  </ul>
</div>

{literal}
  <style type="text/css">
    #multiple_block > input {
      border: 1px solid;
      min-width: inherit !important;
      width: 43px;
    }
  </style>
  <script type="text/javascript">
      CRM.$('#payment_information').ready(function() {
          if(cj('tr').attr('id') !== "multiple_block") {

              cj("#bank_identification_number").parent().prepend('<div id ="multiple_block"></div>');
              cj("#multiple_block")

                  .html('<input type = "text" size = "3" maxlength = "2" name = "block_1" id = "block_1"/>'
                      +' - <input type = "text" size = "3" maxlength = "2" name = "block_2" id ="block_2"/>'
                      +' - <input type = "text" size = "3" maxlength = "2" name = "block_3" id = "block_3"/>');

              cj('#block_1').change(function() {
                  cj.fn.myFunction();
              });

              cj('#block_2').change(function() {
                  cj.fn.myFunction();
              });

              cj('#block_3').change(function() {
                  cj.fn.myFunction();
              });

              //function to get value of new title boxes and concatenate the values and display in mailing_title
              cj.fn.myFunction = function() {
                  var field1 = cj("input#block_1").val();
                  var field2 = cj("input#block_2").val();
                  var field3 = cj("input#block_3").val();
                  var finalFieldValue = field1 + field2 + field3;

                  cj('input#bank_identification_number').val(finalFieldValue);
              };

              //hide the mailing title
              cj("#bank_identification_number").hide();

              //split the value of mailing_title
              //make it to appear on the new three title boxes
              var fieldValue = cj("#bank_identification_number").val();

              var fieldLength;
              if ( fieldValue !== undefined ) {
                  fieldLength = fieldValue.length;
              } else {
                  fieldLength = 0;
              }

              if (fieldLength !== 0) {

                  var fieldSplit = (fieldValue+'').split('');

                  cj('#block_1').val(fieldSplit[0]+fieldSplit[1]);

                  if(!(fieldSplit[0]+fieldSplit[1])) {
                      cj('#block_1').val("");
                  }

                  cj('#block_2').val(fieldSplit[2]+fieldSplit[3]);

                  if(!(fieldSplit[2]+fieldSplit[3])) {
                      cj('#block_2').val("");
                  }

                  cj('#block_3').val(fieldSplit[4]+fieldSplit[5]);

                  if(!(fieldSplit[4]+fieldSplit[5])) {
                      cj('#block_3').val("");
                  }

              }
          }
      });
  </script>
{/literal}

