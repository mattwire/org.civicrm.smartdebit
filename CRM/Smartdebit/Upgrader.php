<?php
use CRM_Smartdebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Smartdebit_Upgrader extends CRM_Smartdebit_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function upgrade_4700() {
    $this->ctx->log->info('Adding column recur_id to veda_smartdebit_mandates');
    if (!CRM_Core_DAO::checkFieldExists('veda_smartdebit_mandates', 'recur_id')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `veda_smartdebit_mandates` ADD COLUMN `recur_id` int(10) unsigned COMMENT 'ID of recurring contribution'");
    }
    return TRUE;
  }

  public function upgrade_4701() {
    $this->ctx->log->info('Renaming field regular_amount to default_amount in veda_smartdebit_mandates');
    if (CRM_Core_DAO::checkFieldExists('veda_smartdebit_mandates', 'regular_amount')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `veda_smartdebit_mandates` CHANGE `regular_amount` `default_amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL");
    }
    return TRUE;
  }

}
