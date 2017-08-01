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

/**
 * The record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 */
return array(
  0 => array(
    'name' => 'SmartDebit',
    'entity' => 'payment_processor_type',
    'params' => array(
      'version' => 3,
      'title' => 'Smart Debit',
      'name' => 'Smart_Debit',
      'description' => 'Smart Debit Payment Processor',
      'user_name_label' => 'API Username',
      'password_label' => 'API Password',
      'signature_label' => 'PSL ID',
      'class_name' => 'Payment_Smartdebit',
      'url_site_default' => 'https://secure.ddprocessing.co.uk/',
      'url_api_default' => 'https://secure.ddprocessing.co.uk/',
      'url_recur_default' => 'https://secure.ddprocessing.co.uk/',
      'billing_mode' => 1, // 1=form
      'payment_type' => 1, // 1=Credit Card
      'is_recur' => 1,
    ),
  ),
  1 => array (
    'name' => 'Cron:SmartDebit.syncFromSmartDebit',
    'entity' => 'Job',
    'params' => array (
      'version' => 3,
      'name' => 'Sync from Smart Debit',
      'description' => 'Sync mandates, payments, AUDDIS reports, ARUDD reports from SmartDebit to CiviCRM.',
      'run_frequency' => 'Daily',
      'api_entity' => 'Smartdebit',
      'api_action' => 'sync',
      'parameters' => '',
    ),
  ),
);
