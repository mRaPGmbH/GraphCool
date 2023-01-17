<?php

namespace Mrap\GraphCool\DataSource\Mysql;

use Mrap\GraphCool\DataSource\FullTextIndexProvider;
use Mrap\GraphCool\Utils\ClassFinder;
use Mrap\GraphCool\Utils\ErrorHandler;
use PHPUnit\Util\Xml\Exception;
use Throwable;
use function Mrap\GraphCool\model;

class MysqlFullTextIndexProvider implements FullTextIndexProvider
{

    protected array $needIndexing = [];
    protected array $needDeletion = [];

    public function index(string $tenantId, string $model, string $id): void
    {
        if (!isset($this->needIndexing[$tenantId])) {
            $this->needIndexing[$tenantId] = [];
        }
        if (!isset($this->needIndexing[$tenantId][$model])) {
            $this->needIndexing[$tenantId][$model] = [];
        }
        $this->needIndexing[$tenantId][$model][] = $id;
    }

    public function delete(string $tenantId, string $model, string $id): void
    {
        $this->needDeletion[] = $id;
    }

    public function shutdown(): void
    {
        foreach ($this->needIndexing as $tenantId => $models) {
            $this->updateIndex((string)$tenantId, $models);
        }
        $this->needIndexing = [];
        if (count($this->needDeletion) > 0) {
            $sql = 'DELETE FROM `fulltext` WHERE `node_id` IN '. $this->quoteArray($this->needDeletion);
            Mysql::getPdo()->exec($sql);
            $this->needDeletion = [];
        }
    }

    public function search(string $tenantId, string $searchString): array
    {
        $parts = [];
        $where = ['tenant_id = ' . Mysql::getPdo()->quote($tenantId)];
        foreach ($this->split($searchString) as $part) {
            /*
            if (mb_strlen($part) > 3) {
                $parts[] = '+' . $part . '*';
            } else {
            */
                $where[] = '`text` LIKE ' . Mysql::getPdo()->quote('%' . $part . '%');
            //}
        }
        /*
        if (count($parts) > 0) {
            $where[] = 'MATCH(`text`) AGAINST(' . Mysql::getPdo()->quote(implode(' ', $parts)) . ' IN BOOLEAN MODE)';
        }
        */
        if (count($where) === 0) {
            return [];
        }

        $result = [];
        $sql = 'SELECT `node_id` FROM `fulltext` WHERE ' . implode(' AND ', $where);
        try {
            foreach (Mysql::getPdo()->query($sql)->fetchAll(\PDO::FETCH_OBJ) as $row) {
                $result[] = $row->node_id;
            }
        } catch (Throwable $e) {
            $new = new \Exception('Fulltext search SQL failed: ' . $sql, 0, $e);
            ErrorHandler::sentryCapture($new);
        }
        return $result;
    }

    protected function split(string $string): array
    {
        $forbidden = ['+', '-', '&', '%', '*', '/', ':', ';', '.', ',', '?', '!', '\'', '"', '§', '$', '€', '(', ')',
            '[', ']', '{', '}', '=', '~', '#', '<', '>', '^', '°', '`', '|', '@'];
        $string = str_replace($forbidden, ' ', $string);
        $string = trim($string);
        $string = (string)preg_replace('/\s+/', ' ', $string);
        return explode(' ', $string);
    }

    protected function updateIndex(string $tenantId, array $models) {
        foreach ($models as $name => $ids) {
            if (count($ids) === 0) {
                continue;
            }
            $model = model($name);
            $fulltextProps = $model->getPropertyNamesForFulltextIndexing();
            if (count($fulltextProps) > 0) {
                $sql = $this->getSql($tenantId, $name, $fulltextProps, $ids);
                Mysql::getPdo()->exec($sql);
            }
            $edgeProps = $model->getEdgePropertyNamesForFulltextIndexing();
            if (count($edgeProps) > 0) {
                if (count($fulltextProps) === 0) {
                    $sql = $this->getSqlEmpty($tenantId, $name, $ids);
                    Mysql::getPdo()->exec($sql);
                }
                $sql = $this->getEdgeSqlSingle($tenantId, $name, array_keys($edgeProps));
                $statement = Mysql::getPdo()->prepare($sql);
                foreach ($ids as $id) {
                    $statement->execute(['id' => $id, 'id2' => $id]);
                }
                // slower?
                //$sql = $this->getEdgeSql($tenantId, $name, $edgeProps, $ids);
                //Mysql::getPdo()->exec($sql);
            }
        }
    }

    protected function getSql(string $tenantId, string $model, array $props, array $ids): string
    {
        return 'REPLACE INTO `fulltext` (`node_id`, `tenant_id`, `model`, `text`) 
            SELECT 
                `n`.`id` AS `node_id`,
                `n`.`tenant_id`,
                `n`.`model`,
                GROUP_CONCAT(COALESCE(`p`.`value_string`, `p`.`value_int`, `p`.`value_float`, \'\') SEPARATOR \' \') AS `text`
            FROM `node` AS `n`
            LEFT JOIN `node_property` AS `p` ON `p`.`node_id` = `n`.`id`
            WHERE (`p`.`property` IS NULL OR `p`.`property` IN ' . $this->quoteArray($props) . ')
                AND `n`.`model` = ' . Mysql::getPdo()->quote($model) . '
                AND `p`.`deleted_at` IS NULL
                AND `n`.`deleted_at` IS NULL
                AND `n`.`tenant_id` = ' . Mysql::getPdo()->quote( $tenantId ) . '
                AND `n`.`id` IN ' . $this->quoteArray($ids) . '
            GROUP BY `n`.`id`
        ';
    }

    protected function getSqlEmpty(string $tenantId, string $model, array $ids): string
    {
        return 'REPLACE INTO `fulltext` (`node_id`, `tenant_id`, `model`, `text`) 
            SELECT 
                `n`.`id` AS `node_id`,
                `n`.`tenant_id`,
                `n`.`model`,
                \'\' AS `text`
            FROM `node` AS `n`
            WHERE `n`.`model` = ' . Mysql::getPdo()->quote($model) . '
                AND `n`.`deleted_at` IS NULL
                AND `n`.`tenant_id` = ' . Mysql::getPdo()->quote( $tenantId ) . '
                AND `n`.`id` IN ' . $this->quoteArray($ids) . '
        ';
    }

    protected function getEdgeSql(string $tenantId, string $model, array $props, array $ids): string
    {
        // TODO: if two different relations have identically named properties - how to discern those?
        return 'UPDATE `fulltext` AS `f`
            SET `text` = CONCAT(`text`, \' \', COALESCE((
                SELECT GROUP_CONCAT(COALESCE(p.value_string, p.value_int, p.value_float, \'\') SEPARATOR \' \')
                FROM `edge` AS `e`
                LEFT JOIN `edge_property` AS `p` ON (`e`.child_id = `p`.child_id AND `e`.parent_id = `p`.parent_id)
                WHERE `e`.`child_id` = `f`.`node_id`
                AND p.deleted_at IS NULL
                AND e.deleted_at IS NULL
                AND (`p`.`property` IS NULL OR p.property IN ' . $this->quoteArray($props) . ')
                GROUP BY `e`.`child_id`
            ), \'\'))
            WHERE `f`.`node_id` IN ' . $this->quoteArray($ids) . '
            AND `f`.tenant_id = ' . Mysql::getPdo()->quote( $tenantId ) . '
            AND `f`.model = ' . Mysql::getPdo()->quote($model) . '
        ';
    }

    protected function getEdgeSqlSingle(string $tenantId, string $model, array $props): string
    {
        // TODO: if two different relations have identically named properties - how to discern those?
        return 'UPDATE `fulltext` AS `f`
            SET `text` = CONCAT(`text`, \' \', COALESCE((
                SELECT GROUP_CONCAT(COALESCE(`p`.`value_string`, `p`.`value_int`, `p`.`value_float`, \'\') SEPARATOR \' \')
                FROM `edge` AS `e`
                LEFT JOIN `edge_property` AS `p` ON (`e`.`child_id` = `p`.`child_id` AND `e`.`parent_id` = `p`.`parent_id`)
                WHERE `e`.`child_id` = :id
                AND `p`.`deleted_at` IS NULL
                AND `e`.`deleted_at` IS NULL
                AND (`p`.`property` IS NULL OR `p`.`property` IN ' . $this->quoteArray($props) . ')
                GROUP BY `e`.`child_id`
            ), \'\'))
            WHERE `f`.`node_id` = :id2
            AND `f`.`tenant_id` = ' . Mysql::getPdo()->quote( $tenantId ) . '
            AND `f`.`model` = ' . Mysql::getPdo()->quote($model) . '
        ';
    }

    protected function quoteArray(array $array): string
    {
        $result = [];
        foreach ($array as $value) {
            $result[] = Mysql::getPdo()->quote($value);
        }
        if (count($result) === 0) {
            return '(null)';
        }
        return '(' . implode(',', $result) . ')';
    }

    public function rebuildIndex(): void
    {
        $propertySqls = $this->getPropertySqls();
        foreach ($this->getTenants() as $tenantId) {
            $sql = 'REPLACE INTO `fulltext` (`node_id`, `tenant_id`, `model`, `text`) 
                    SELECT 
                        `n`.`id` AS `node_id`,
                        `n`.`tenant_id`,
                        `n`.`model`, ';
            $where = [
                '`n`.`tenant_id` = ' . Mysql::getPdo()->quote($tenantId),
                '`n`.`deleted_at` IS NULL'
            ];
            if ($propertySqls['node'] === null) {
                $sql .= '\'\' as `text` FROM `node` AS `n` WHERE ';
            } else {
                $sql .= 'GROUP_CONCAT(COALESCE(`p`.`value_string`, `p`.`value_int`, `p`.`value_float`, \'\') SEPARATOR \' \') AS `text`
                    FROM `node` AS `n`
                    LEFT JOIN `node_property` AS `p` ON `p`.`node_id` = `n`.`id` WHERE ';
                $where[] = '`p`.`deleted_at` IS NULL';
                $where[] = $propertySqls['node'];
            }
            $sql .= implode(' AND ', $where) . ' GROUP BY `n`.`id`';
            Mysql::getPdo()->exec($sql);

            if ($propertySqls['edge'] !== null) {
                $sql = 'UPDATE `fulltext` AS `f`
                    SET `text` = CONCAT(`text`, \' \', COALESCE((
                        SELECT GROUP_CONCAT(COALESCE(p.value_string, p.value_int, p.value_float, \'\') SEPARATOR \' \')
                        FROM `edge` AS `e`
                        LEFT JOIN `edge_property` AS `p` ON (`e`.child_id = `p`.child_id AND `e`.parent_id = `p`.parent_id)
                        WHERE `e`.`child_id` = `f`.`node_id`
                        AND p.deleted_at IS NULL
                        AND e.deleted_at IS NULL
                        AND ' . $propertySqls['edge'] . '
                        GROUP BY `e`.`child_id`
                    ), \'\'))
                    WHERE `f`.tenant_id = ' . Mysql::getPdo()->quote($tenantId) . '
                ';
                Mysql::getPdo()->exec($sql);
            }
        }
    }

    protected function getTenants(): array
    {
        $sql = 'SELECT DISTINCT `tenant_id` FROM `node`';
        $result = [];
        foreach (Mysql::getPdo()->query($sql)->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $result[] = $row->tenant_id;
        }
        return $result;
    }

    protected function getPropertySqls(): array
    {
        $sqls = [
            'node' => null,
            'edge' => null
        ];
        $nodeSqls = [];
        $edgeSqls = [];
        foreach (ClassFinder::models() as $name => $classname) {
            $model = model($name);
            $nodeProps = $model->getPropertyNamesForFulltextIndexing();
            if (count($nodeProps) > 0) {
                $nodeSqls[] = '(`p`.`property` IN ' . $this->quoteArray($nodeProps)
                    . ' AND `n`.`model` = ' . Mysql::getPdo()->quote($name) . ')';
            }
            $edgeProps = $model->getEdgePropertyNamesForFulltextIndexing();
            foreach ($edgeProps as $prop => $subName) {
                $edgeSqls[] = '(`p`.`property` IN ' . $this->quoteArray(array_keys($edgeProps))
                    . ' AND `e`.`parent` = ' . Mysql::getPdo()->quote($subName) . ')';
            }
        }
        if (count($nodeSqls) > 0) {
            $sqls['node'] = '(' . implode(' OR ', $nodeSqls) . ')';
        }
        if (count($edgeSqls) > 0) {
            $sqls['edge'] = '(' . implode(' OR ', $edgeSqls) . ')';
        }
        return $sqls;
    }


}