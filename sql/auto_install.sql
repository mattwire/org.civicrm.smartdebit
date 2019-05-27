  /* On install, create a table for keeping track of online direct debits */
CREATE TABLE IF NOT EXISTS `veda_smartdebit` (
        `id`                        int(10) unsigned NOT NULL auto_increment,
        `created`                   datetime NOT NULL,
        `data_type`                 varchar(16),
        `entity_type`               varchar(32),
        `entity_id`                 int(10) unsigned,
        `bank_name`                 varchar(100),
        `branch`                    varchar(100),
        `address1`                  varchar(100),
        `address2`                  varchar(100),
        `address3`                  varchar(100),
        `address4`                  varchar(100),
        `town`                      varchar(100),
        `county`                    varchar(100),
        `postcode`                  varchar(20),
        `first_collection_date`     varchar(100),
        `preferred_collection_day`  varchar(100),
        `ddi_reference`             varchar(100) NOT NULL,
        `response_status`           varchar(100),
        `response_raw`              longtext,
        `request_counter`           int(10) unsigned,
        `complete_flag`             tinyint unsigned,
        `additional_details1`       varchar(100),
        `additional_details2`       varchar(100),
        `additional_details3`       varchar(100),
        `additional_details4`       varchar(100),
        `additional_details5`       varchar(100),
        PRIMARY KEY  (`id`),
        KEY `entity_id` (`entity_id`),
        KEY `data_type` (`data_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* Create a table to store AUDDIS/ARUDD dates */
CREATE TABLE IF NOT EXISTS `veda_smartdebit_auddis` (
                   `id` int(10) unsigned NOT NULL,
                   `date` date DEFAULT NULL,
                   `type` tinyint DEFAULT NULL,
                   `processed` boolean DEFAULT FALSE,
                  PRIMARY KEY (`id`),
                  CONSTRAINT UC_id_type UNIQUE (`id`, `type`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* Create a table to store imported collections (CRM_Smartdebit_Api::getCollectionReport()) */
CREATE TABLE IF NOT EXISTS `veda_smartdebit_collections` (
                   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                   `transaction_id` varchar(255) DEFAULT NULL,
                   `contact` varchar(255) DEFAULT NULL,
                   `contact_id` varchar(255) DEFAULT NULL,
                   `amount` decimal(20,2) DEFAULT NULL,
                   `info` int(11) DEFAULT NULL,
                   `receive_date` varchar(255) DEFAULT NULL,
                   `error_message` varchar(255) DEFAULT NULL,
                   `success` tinyint unsigned NOT NULL,
                  PRIMARY KEY (`id`),
                  CONSTRAINT UC_Collection UNIQUE (transaction_id, amount, receive_date)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* Create a table to store imported collectionreport summaries */
CREATE TABLE IF NOT EXISTS `veda_smartdebit_collectionreportsummary` (
                   `collection_date` date UNIQUE NOT NULL,
                   `success_amount` decimal(20,2) DEFAULT NULL,
                   `success_number` int DEFAULT NULL,
                   `reject_amount` decimal(20,2) DEFAULT NULL,
                   `reject_number` int DEFAULT NULL,
                  PRIMARY KEY (`collection_date`)
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* This table is used to store the cached smartdebit mandates */
CREATE TABLE IF NOT EXISTS `veda_smartdebit_mandates` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `first_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `last_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `email_address` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `address_1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `address_2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `address_3` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `town` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `county` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `postcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `first_amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `default_amount` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `frequency_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `frequency_factor` int(10) unsigned DEFAULT NULL,
            `start_date` datetime NOT NULL,
            `current_state` int(10) unsigned DEFAULT NULL,
            `reference_number` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `payerReference` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
            `recur_id` int(10) unsigned COMMENT 'ID of recurring contribution',
            INDEX reference_number_idx (reference_number),
            PRIMARY KEY (`id`)
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* This table is used to store the last set of successful imports */
CREATE TABLE IF NOT EXISTS `veda_smartdebit_syncresults` (
                   `type` tinyint unsigned NOT NULL,
                   `transaction_id` varchar(255) DEFAULT NULL,
                   `contribution_id` int(10) unsigned DEFAULT NULL,
                   `contact_id` int(10) unsigned DEFAULT NULL,
                   `contact_name` varchar(128) DEFAULT NULL,
                   `amount` decimal(20,2) DEFAULT NULL,
                   `frequency` varchar(255) DEFAULT NULL,
                   `receive_date` date DEFAULT NULL
         ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
