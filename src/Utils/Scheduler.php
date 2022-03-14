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
        $this->config = include(APP_ROOT_PATH.'/config/scheduler.php');
        if (isset($this->config['always']) && !is_array($this->config['always'])) {
            throw new RuntimeException('Error in ' . APP_ROOT_PATH.'/config/scheduler.php' . ': [\'always\'] is not an array.');
        }
        if (isset($this->config['hourly']) && !is_array($this->config['hourly'])) {
            throw new RuntimeException('Error in ' . APP_ROOT_PATH.'/config/scheduler.php' . ': [\'hourly\'] is not an array.');
        }
        if (isset($this->config['daily']) && !is_array($this->config['daily'])) {
            throw new RuntimeException('Error in ' . APP_ROOT_PATH.'/config/scheduler.php' . ': [\'daily\'] is not an array.');
        }
        if (isset($this->config['weekly']) && !is_array($this->config['weekly'])) {
            throw new RuntimeException('Error in ' . APP_ROOT_PATH.'/config/scheduler.php' . ': [\'weekly\'] is not an array.');
        }
    }

    public function run(): array
    {
        $this->start = time();
        while ($this->time() < 290) {
            set_time_limit(90);
            $this->loop();
            sleep(15);
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
            return;
        }
        // TODO: hourly, daily, weekly?
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
            $e = new RuntimeException('Scheduled script: "' . $script . '" ran for ' . $time . ' seconds.');
            ErrorHandler::sentryCapture($e);
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
                $result = GraphCool::runScript([$script]);
            } catch (Throwable $e) {
                $result = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
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
        echo $job->worker . PHP_EOL;
        try {
            $result = GraphCool::runScript([$job->worker, $job]);
        } catch (Throwable $e) {
            $result = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        DB::finishJob($job->id, $result, !$result['success']);

        $this->checkTime($start, $job->worker);
        return true;
    }

}