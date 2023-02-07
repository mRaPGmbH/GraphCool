<?php

namespace Mrap\GraphCool\Utils;

class Sorter
{
    public static function sortNodesByIdOrder(array $nodes, array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (array_key_exists($id, $nodes)) {
                $result[$id] = $nodes[$id];
            }
        }
        return $result;
    }
}
