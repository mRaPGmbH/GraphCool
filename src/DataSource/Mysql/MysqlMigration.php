<?php


namespace Mrap\GraphCool\DataSource\Mysql;

/**
 * Class MysqlMigration
 * @package Mrap\GraphCool\DataSource\Mysql
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
              `tenant_id` int(11) NOT NULL,
              `model` varchar(255) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` timestamp NULL DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `tenant_id_model_deleted_at` (`tenant_id`, `model`, `deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        Mysql::executeRaw($sql);

        $sql = 'CREATE TABLE IF NOT EXISTS `node_property` (
              `node_id` char(36) NOT NULL,
              `model` varchar(255) NOT NULL,
              `property` varchar(255) NOT NULL,
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
        /*
         *               KEY `value_int_deleted_at` (`value_int`, `deleted_at`),
              KEY `value_string_deleted_at` (`value_string`, `deleted_at`),
              KEY `value_float_deleted_at` (`value_float`, `deleted_at`),

         */

        $sql = 'CREATE TABLE IF NOT EXISTS `edge` (
              `parent_id` char(36) NOT NULL COMMENT \'node.id\',
              `child_id` char(36) NOT NULL COMMENT \'node.id\',
              `tenant_id` int(11) NOT NULL,
              `parent` varchar(255) NOT NULL,
              `child` varchar(255) NOT NULL,
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
              `parent` varchar(255) NOT NULL COMMENT \'node.name\',
              `child` varchar(255) NOT NULL COMMENT \'node.name\',
              `property` varchar(255) NOT NULL,
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

        $sql = 'SET sql_notes = 1';
        Mysql::executeRaw($sql);
    }

}