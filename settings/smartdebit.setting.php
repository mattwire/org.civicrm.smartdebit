<?php

return array(

//payment_instrument_id
  'smartdebit_payment_instrument_id' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_payment_instrument_id',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Smart Debit Payment Method',
    'html_attributes' => array(),
  ),

//financial_type
  'smartdebit_financial_type' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_financial_type',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Smart Debit Financial Type',
    'html_attributes' => array(),
  ),

//activity_type
  'smartdebit_activity_type' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_activity_type',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Smart Debit Activity Type (Sign Up)',
    'html_attributes' => array(),
  ),

//activity_type_letter
  'smartdebit_activity_type_letter' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_activity_type_letter',
    'type' => 'String',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Smart Debit Activity Type (Letter)',
    'html_attributes' => array(),
  ),

//collection_interval
  'smartdebit_collection_interval' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_collection_interval',
    'type' => 'String',
    'html_type' => 'String',
    'default' => 15,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Smart Debit Collection Interval',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//collection_days
  'smartdebit_collection_days' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_collection_days',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '1,8,22',
    'description' => 'Smart Debit Collection Days',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//service_user_number
  'smartdebit_service_user_number' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_service_user_number',
    'type' => 'String',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Smart Debit Service User Number (SUN)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//company_name
  'smartdebit_company_name' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_name',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Company Name',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),
//telephone_number
  'smartdebit_telephone_number' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_telephone_number',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Telephone Number',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//email_address
  'smartdebit_email_address' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_email_address',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Email Address',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//domain_name
  'smartdebit_domain_name' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_domain_name',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Domain Name',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//company_address1
  'smartdebit_company_address1' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_address1',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company Address (Line 1)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),

  ),

//company_address2
  'smartdebit_company_address2' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_address2',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company Address (Line 2)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),

  ),

//company_address3
  'smartdebit_company_address3' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_address3',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company Address (Line 3)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),

  ),

//company_address4
  'smartdebit_company_address4' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_address4',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company Address (Line 4)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),

  ),

//company_town
  'smartdebit_company_town' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_town',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company Town',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),

  ),

//company_county
  'smartdebit_company_county' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_county',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company County',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),

  ),

//company_postcode
  'smartdebit_company_postcode' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_company_postcode',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Company Postcode',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//transaction_prefix
  'smartdebit_transaction_prefix' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_transaction_prefix',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Smart Debit Transaction Prefix',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

//api_contact_key
  'smartdebit_api_contact_key' => array(
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_api_contact_key',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => 'payerReference',
    'description' => 'Smart Debit API Contact Key',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),
);
