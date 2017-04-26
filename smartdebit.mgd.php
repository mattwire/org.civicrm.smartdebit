<?php
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
