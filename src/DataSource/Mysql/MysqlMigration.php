<?php

declare(strict_types=1);

namespace Mrap\GraphCool\DataSource\Mysql;

/**
 * @codeCoverageIgnore
 */
class MysqlMigration
{

    public function migrate(): void
    {
        Mysql::waitForConnection(); // migrate happens in docker entrypoint, when mysql might still be booting up

        $sql = 'SET sql_notes = 0';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node` (
              `id` char(36) NOT NULL COMMENT \'uuid\',
              `tenant_id` char(255) NOT NULL,
              `model` char(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `tenant_id_model_deleted_at` (`tenant_id`, `model`, `deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node_property` (
              `node_id` char(36) NOT NULL,
              `model` char(255) NOT NULL,
              `property` char(255) NOT NULL,
              `value_int` bigint(20) DEFAULT NULL,
              `value_string` longtext,
              `value_float` double DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`node_id`,`property`),
              KEY `node_id_property_deleted_at` (`node_id`, `property`, `deleted_at`),
              CONSTRAINT `node_property_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `edge` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `tenant_id` char(255) NOT NULL,
              `parent` char(255) NOT NULL,
              `child` char(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`parent_id`,`child_id`),
              KEY `child_id_parent` (`child_id`, `parent`),
              KEY `parent_id_child` (`parent_id`, `child`),
              KEY `tenant_id` (`tenant_id`),
              KEY `deleted_at` (`deleted_at`),
              CONSTRAINT `edge_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `node` (`id`) ON DELETE CASCADE,
              CONSTRAINT `edge_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `edge_property` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `parent` char(255) NOT NULL COMMENT \'node.name\',
              `child` char(255) NOT NULL COMMENT \'node.name\',
              `property` char(255) NOT NULL,
              `value_int` bigint(20) DEFAULT NULL,
              `value_string` longtext,
              `value_float` double DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`parent_id`,`child_id`,`property`),
              KEY `child_id_parent_property` (`child_id`, `parent`, `property`),
              KEY `parent_id_child_property` (`parent_id`, `child`, `property`),
              CONSTRAINT `edge_property_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `node` (`id`) ON DELETE CASCADE,
              CONSTRAINT `edge_property_ibfk_2` FOREIGN KEY (`child_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `increment` (
              `tenant_id` char(255) NOT NULL,
              `key` char(255) NOT NULL,
              `value` bigint(20) NOT NULL DEFAULT 0,
              PRIMARY KEY (`tenant_id`,`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `fulltext` (
            `node_id` char(36) NOT NULL,
            `tenant_id` char(255) NOT NULL,
            `model` char(255) NOT NULL,
            `text` longtext NOT NULL,
            PRIMARY KEY (`node_id`),
            INDEX `tenant_id_model` (`tenant_id`, `model`),
            FULLTEXT KEY `text` (`text`),
            CONSTRAINT `fulltext_ibfk_1` FOREIGN KEY (`node_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `history` (
            `id` char(36) NOT NULL,
            `tenant_id` varchar(255) NOT NULL,
            `number` bigint(20) NOT NULL DEFAULT \'1\',
            `node_id` char(36) NOT NULL,
            `model` varchar(255) NOT NULL,
            `sub` varchar(255) DEFAULT NULL,
            `ip` tinytext,
            `user_agent` varchar(255) DEFAULT NULL,
            `change_type` varchar(255) DEFAULT NULL,
            `changes` longtext NOT NULL,
            `preceding_hash` varchar(255) DEFAULT NULL,
            `hash` varchar(255) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `node_id` (`node_id`),
            KEY `tenant_id_model_number` (`tenant_id`,`model`,`number`),
            KEY `tenant_id_sub_number` (`tenant_id`,`sub`,`number`),
            KEY `tenant_id_number` (`tenant_id`,`number`),
            CONSTRAINT `history_ibfk_1` FOREIGN KEY (`node_id`) REFERENCES `node` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;';
        Mysql::executeRaw($sql);

        $sql = 'SET sql_notes = 1';
        Mysql::executeRaw($sql);
    }

}