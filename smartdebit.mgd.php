<?php
/**
 * https://civicrm.org/licensing
 */

/**
 * The record will be automatically inserted, updated, or deleted from the
 * database as appropriate. For more details, see "hook_civicrm_managed" at:
 * http://wiki.civicrm.org/confluence/display/CRMDOC/Hook+Reference
 */
return [
  0 => [
    'name' => 'SmartDebit',
    'entity' => 'payment_processor_type',
    'params' => [
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
      'url_site_test_default' => 'https://sandbox.ddprocessing.co.uk/',
      'url_api_test_default' => 'https://sandbox.ddprocessing.co.uk/',
      'url_recur_test_default' => 'https://sandbox.ddprocessing.co.uk/',
      'billing_mode' => 1, // 1=form
      'payment_type' => 1, // 1=Credit Card
      'is_recur' => 1,
    ],
  ],
  1 => [
    'name' => 'Cron:SmartDebit.syncFromSmartDebit',
    'entity' => 'Job',
    'update' => 'never',
    'params' => [
      'version' => 3,
      'name' => 'Sync from Smart Debit',
      'description' => 'Sync mandates, payments, AUDDIS reports, ARUDD reports from SmartDebit to CiviCRM.',
      'run_frequency' => 'Daily',
      'api_entity' => 'Job',
      'api_action' => 'process_smartdebit',
      'parameters' => '',
    ],
  ],
];
