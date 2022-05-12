<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

class ConfigProvider
{

    protected $cache = [];

    public function get(string $config, string $key = null): mixed
    {
        if (!isset($this->cache[$config])) {
            $this->cache[$config] = $this->load($config);
        }
        if ($key === null) {
            return $this->cache[$config];
        }
        return $this->cache[$config][$key] ?? null;
    }

    protected function load(string $config): array
    {
        $filename = ClassFinder::rootPath().'/config/' . $config . '.php';
        if (!file_exists($filename)) {
            return [];
        }
        return include($filename);
    }

}
