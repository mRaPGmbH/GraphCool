<?php

declare(strict_types=1);

namespace Mrap\GraphCool\Utils;

use Mrap\GraphCool\DataSource\DB;
use RuntimeException;
use Mrap\GraphCool\GraphCool;
use Throwable;

class Scheduler
{

    protected array $config;
    protected int $start;

    public function __construct()
    {
        $this->config = Config::get('scheduler');
        if (isset($this->config['always']) && !is_array($this->config['always'])) {
            throw new RuntimeException('Error in ' . ClassFinder::rootPath() . '/config/scheduler.php' . ': [\'always\'] is not an array.');
        }
        if (isset($this->config['each-start']) && !is_array($this->config['each-start'])) {
            throw new RuntimeException('Error in ' . ClassFinder::rootPath() . '/config/scheduler.php' . ': [\'each-start\'] is not an array.');
        }
    }

    public function run(): array
    {
        $this->start = time();
        $this->runScripts($this->config['each-start'] ?? []);
        while ($this->time() < 290) {
            set_time_limit(90);
            $this->loop();
            if (Env::get('APP_ENV') === 'test') {
                break;
            }
            // @codeCoverageIgnoreStart
            sleep(15);
            // @codeCoverageIgnoreEnd
        }
        return [
            'success' => true
        ];
    }

    protected function loop(): void
    {
        $start = time();
        $this->runScripts($this->config['always'] ?? []);
        if ($this->time() >= 300) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }
        while (time() - $start < 15) {
            if ($this->runJob() === false) {
                break;
            }
        }
    }

    protected function checkTime(int $start, string $script): void
    {
        $time = $this->time() - $start;
        if ($time > 60) {
            // @codeCoverageIgnoreStart
            $e = new RuntimeException('Scheduled script: "' . $script . '" ran for ' . $time . ' seconds.');
            ErrorHandler::sentryCapture($e);
            // @codeCoverageIgnoreEnd
        }
    }

    protected function time(): int
    {
        return time() - $this->start;
    }

    protected function runScripts(array $scripts): void
    {
        foreach ($scripts as $script) {
            $start = $this->time();
            try {
                GraphCool::runScript([$script]);
            } catch (Throwable $e) {
                ErrorHandler::sentryCapture($e);
            }
            $this->checkTime($start, $script);
        }
    }

    protected function runJob(): bool
    {
        $job = DB::takeJob();
        if ($job === null) {
            return false;
        }
        $start = $this->time();
        echo 'running job ' . $job->id . ' with worker ' . $job->worker . '...';
        try {
            $result = GraphCool::runScript([$job->worker, $job]);
            echo ' DONE' . PHP_EOL;
        } catch (Throwable $e) {
            echo ' FAILED' . PHP_EOL;
            $result = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        DB::finishJob($job->id, $result, !($result['success'] ?? false));

        $this->checkTime($start, $job->worker);
        return true;
    }

}
