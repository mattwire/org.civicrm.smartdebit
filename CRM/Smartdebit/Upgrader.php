<?php
/**
 * https://civicrm.org/licensing
 */

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
    // Rename civicrm_direct_debit to veda_smartdebit
    $this->ctx->log->info('Renaming table civicrm_direct_debit to ' . CRM_Smartdebit_Base::TABLENAME);
    if (!CRM_Core_DAO::checkTableExists(CRM_Smartdebit_Base::TABLENAME)) {
      CRM_Core_DAO::executeQuery("RENAME TABLE civicrm_direct_debit TO " . CRM_Smartdebit_Base::TABLENAME);
    }

    // Rename veda_smartdebit_collectionreports to veda_smartdebit_collections
    $this->ctx->log->info('Renaming table veda_smartdebit_collectionreports to ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (!CRM_Core_DAO::checkTableExists(CRM_Smartdebit_CollectionReports::TABLENAME)) {
      CRM_Core_DAO::executeQuery("RENAME TABLE veda_smartdebit_collectionreports TO " . CRM_Smartdebit_CollectionReports::TABLENAME);
    }

    // Clearing cached collection reports
    $this->ctx->log->info('Clearing cached collection reports');
    if (CRM_Core_DAO::checkTableExists(CRM_Smartdebit_CollectionReports::TABLENAME)) {
      CRM_Core_DAO::executeQuery("TRUNCATE TABLE " . CRM_Smartdebit_CollectionReports::TABLENAME);
    }

    // Modify veda_smartdebit_collections to remove column info, add columns error_message and success
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

    // Add unique constraint to veda_smartdebit_collections
    $this->ctx->log->info('Add unique constraint to ' . CRM_Smartdebit_CollectionReports::TABLENAME);
    if (!CRM_Core_DAO::checkConstraintExists(CRM_Smartdebit_CollectionReports::TABLENAME, 'UC_Collection')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE " . CRM_Smartdebit_CollectionReports::TABLENAME . " ADD CONSTRAINT UC_Collection UNIQUE (transaction_id,amount,receive_date)");
    }

    // Create a table to store imported collectionreport summaries
    $this->ctx->log->info('Creating table to store collection report summaries: ' . CRM_Smartdebit_CollectionReports::TABLESUMMARY);
    $sql = "CREATE TABLE IF NOT EXISTS `" . CRM_Smartdebit_CollectionReports::TABLESUMMARY . "` (
                   `collection_date` date UNIQUE NOT NULL,
                   `success_amount` decimal(20,2) DEFAULT NULL,
                   `success_number` int DEFAULT NULL,
                   `reject_amount` decimal(20,2) DEFAULT NULL,
                   `reject_number` int DEFAULT NULL,
                  PRIMARY KEY (`collection_date`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    CRM_Core_DAO::executeQuery($sql);

    // Drop table veda_smartdebit_success_contributions
    $this->ctx->log->info('Removing table veda_smartdebit_success_contributions');
    if (CRM_Core_DAO::checkTableExists('veda_smartdebit_success_contributions')) {
      CRM_Core_DAO::executeQuery('DROP TABLE veda_smartdebit_success_contributions');
    }

    // Create a table to store store the last set of successful imports
    $this->ctx->log->info('Creating table to store the last set of successful imports: ' . CRM_Smartdebit_SyncResults::TABLENAME);
    $sql = "CREATE TABLE IF NOT EXISTS `veda_smartdebit_syncresults` (
                   `type` tinyint unsigned NOT NULL,
                   `transaction_id` varchar(255) DEFAULT NULL,
                   `contribution_id` int(10) unsigned DEFAULT NULL,
                   `contact_id` int(10) unsigned DEFAULT NULL,
                   `contact_name` varchar(128) DEFAULT NULL,
                   `amount` decimal(20,2) DEFAULT NULL,
                   `frequency` varchar(255) DEFAULT NULL,
                   `receive_date` date DEFAULT NULL
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    CRM_Core_DAO::executeQuery($sql);
    return TRUE;
  }

}
