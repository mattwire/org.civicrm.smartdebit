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

}
