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
  // payment_instrument_id
  'smartdebit_payment_instrument_id' => array(
    'admin_group' => 'smartdebit_general',
    'admin_grouptitle' => 'General Settings',
    'admin_groupdescription' => 'Check the description for each setting and make sure it is configured correctly.',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_payment_instrument_id',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Payment Method',
    'html_attributes' => array(),
  ),

  // financial_type
  'smartdebit_financial_type' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_financial_type',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Default Financial Type',
    'html_attributes' => array(),
  ),

  // activity_type
  'smartdebit_activity_type' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_activity_type',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Activity Type (Sign Up)',
    'html_attributes' => array(),
  ),

  // activity_type_letter
  'smartdebit_activity_type_letter' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_activity_type_letter',
    'type' => 'String',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Activity Type (Letter)',
    'html_attributes' => array(),
  ),

  // collection_interval
  'smartdebit_collection_interval' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_collection_interval',
    'type' => 'String',
    'default' => 10,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Collection Interval',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

  // collection_days
  'smartdebit_collection_days' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_collection_days',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '1,20',
    'description' => 'Collection Days',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

  // Advance notice period
  'smartdebit_notice_period' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_notice_period',
    'type' => 'String',
    'default' => 10,
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Advance Notice Period of changes (days)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

  // service_user_number
  'smartdebit_service_user_number' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_service_user_number',
    'type' => 'String',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Service User Number (SUN)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),


  // service_user_number
  'smartdebit_service_user_name' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_service_user_name',
    'type' => 'String',
    'default' => '',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Service User (Organisation) Name (Optional)',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

  // transaction_prefix
  'smartdebit_transaction_prefix' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_transaction_prefix',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Transaction Prefix',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),

  // initial_completed
  'smartdebit_initial_completed' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_initial_completed',
    'type' => 'Boolean',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '',
    'description' => 'Mark initial contribution as completed (instead of pending)',
    'html_type' => 'Checkbox',
    'html_attributes' => array(
    ),
  ),

  // Confirm by post
  'smartdebit_confirmby_post' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_confirmby_post',
    'type' => 'Boolean',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => 1,
    'description' => 'Allow payer to receive direct debit confirmation by post',
    'html_type' => 'Checkbox',
    'html_attributes' => array(
    ),
  ),

  // Confirm by email
  'smartdebit_confirmby_email' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_confirmby_email',
    'type' => 'Boolean',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => 1,
    'description' => 'Allow payer to receive direct debit confirmation by email',
    'html_type' => 'Checkbox',
    'html_attributes' => array(
    ),
  ),

  // Debug mode
  'smartdebit_debug' => array(
    'admin_group' => 'smartdebit_debug',
    'admin_grouptitle' => 'Debug Settings',
    'admin_groupdescription' => 'Settings that can be used for debugging.',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_debug',
    'type' => 'Boolean',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => 0,
    'description' => 'Enable debugging to log',
    'html_type' => 'Checkbox',
    'html_attributes' => array(
    ),
  ),

  // Collection report cache retention period
  'smartdebit_cr_cache' => array(
    'admin_group' => 'smartdebit_general',
    'group_name' => 'Smart Debit Settings',
    'group' => 'smartdebit',
    'name' => 'smartdebit_cr_cache',
    'type' => 'String',
    'add' => '4.7',
    'is_domain' => 1,
    'is_contact' => 0,
    'default' => '-1 year',
    'description' => 'Collection Report Cache Retention Period',
    'html_type' => 'Text',
    'html_attributes' => array(
      'size' => 50,
    ),
  ),
);
