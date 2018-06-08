<?php
use CRM_Smartdebit_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Smartdebit_Upgrader extends CRM_Smartdebit_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  public function upgrade_4700() {
    $this->ctx->log->info('Adding column recur_id to ' . CRM_Smartdebit_Mandates::TABLENAME);
    if (!CRM_Core_DAO::checkFieldExists(CRM_Smartdebit_Mandates::TABLENAME, 'recur_id')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `" . CRM_Smartdebit_Mandates::TABLENAME . "` ADD COLUMN `recur_id` int(10) unsigned COMMENT 'ID of recurring contribution'");
    }
    return TRUE;
  }

  public function upgrade_4701() {
    $this->ctx->log->info('Renaming field regular_amount to default_amount in ' . CRM_Smartdebit_Mandates::TABLENAME);
    if (CRM_Core_DAO::checkFieldExists(CRM_Smartdebit_Mandates::TABLENAME, 'regular_amount')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `" . CRM_Smartdebit_Mandates::TABLENAME . "` CHANGE `regular_amount` `default_amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL");
    }
    return TRUE;
  }

  public function upgrade_4702() {
    $this->ctx->log->info('Adding column "error_message" to ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (!CRM_Core_DAO::checkFieldExists(CRM_Smartdebit_CollectionReports::TABLENAME, 'error_message')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `".CRM_Smartdebit_CollectionReports::TABLENAME."` ADD COLUMN `error_message` varchar(255) DEFAULT NULL");
    }
    $this->ctx->log->info('Adding column "success" to ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (!CRM_Core_DAO::checkFieldExists(CRM_Smartdebit_CollectionReports::TABLENAME, 'success')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `".CRM_Smartdebit_CollectionReports::TABLENAME."` ADD COLUMN `success` tinyint unsigned NOT NULL");
      CRM_Core_DAO::executeQuery("UPDATE `".CRM_Smartdebit_CollectionReports::TABLENAME."` SET success=1");
    }
    $this->ctx->log->info('Removing column "info" from ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (CRM_Core_DAO::checkFieldExists(CRM_Smartdebit_CollectionReports::TABLENAME, 'info')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `".CRM_Smartdebit_CollectionReports::TABLENAME."` DROP COLUMN `info`");
    }
    return TRUE;
  }
}
