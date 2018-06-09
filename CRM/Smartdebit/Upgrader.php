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

  public function upgrade_4703() {
    $this->ctx->log->info('Creating table to store collection report summaries: ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    // Create a table to store imported collectionreport summaries
    $sql = "CREATE TABLE IF NOT EXISTS `" . CRM_Smartdebit_CollectionReports::TABLESUMMARY . "` (
                   `collection_date` date UNIQUE NOT NULL,
                   `success_amount` decimal(20,2) DEFAULT NULL,
                   `success_number` int DEFAULT NULL,
                   `reject_amount` decimal(20,2) DEFAULT NULL,
                   `reject_number` int DEFAULT NULL,
                  PRIMARY KEY (`collection_date`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    CRM_Core_DAO::executeQuery($sql);

    // Rename veda_smartdebit_collectionreports to veda_smartdebit_collections
    $this->ctx->log->info('Renaming table veda_smartdebit_collectionreports to ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (!CRM_Core_DAO::checkTableExists(CRM_Smartdebit_CollectionReports::TABLENAME)) {
      CRM_Core_DAO::executeQuery("RENAME TABLE veda_smartdebit_collectionreports TO " . CRM_Smartdebit_CollectionReports::TABLENAME);
    }

    // Add unique constraint to veda_smartdebit_collections
    $this->ctx->log->info('Add unique constraint to ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (!CRM_Core_DAO::checkConstraintExists(CRM_Smartdebit_CollectionReports::TABLENAME, 'UC_Collection')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE " . CRM_Smartdebit_CollectionReports::TABLENAME . " ADD CONSTRAINT UC_Collection UNIQUE (transaction_id,amount,receive_date)");
    }
  }

}
