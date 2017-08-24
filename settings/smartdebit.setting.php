<?php
/*--------------------------------------------------------------------+
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
 +-------------------------------------------------------------------*/

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
    'default' => 10,
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
    'default' => '1,20',
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

  // Internal settings for stats
  //transaction_prefix
  'smartdebit_rejected_auddis' => array(
  'group_name' => 'Smart Debit Settings',
  'group' => 'smartdebit',
  'name' => 'smartdebit_rejected_auddis',
  'type' => 'Array',
  'add' => '4.7',
  'is_domain' => 1,
  'is_contact' => 0,
  'default' => '',
  'html_type' => 'Hidden',
  'html_attributes' => array(),
  ),
  'smartdebit_rejected_arudd' => array(
  'group_name' => 'Smart Debit Settings',
  'group' => 'smartdebit',
  'name' => 'smartdebit_rejected_arudd',
  'type' => 'Array',
  'add' => '4.7',
  'is_domain' => 1,
  'is_contact' => 0,
  'default' => '',
  'html_type' => 'Hidden',
  'html_attributes' => array(),
  )
);
