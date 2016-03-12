<?php

namespace Polyglot\Plugin\Db;

use Strata\Strata;

class Logger {

    private $executionStart = 0;

    public function logQueryStart()
    {
        $this->executionStart = microtime(true);
    }

    public function logQueryCompletion($sql)
    {
        $executionTime = microtime(true) - $this->executionStart;
        $timer = sprintf(" (Done in %s seconds)", round($executionTime, 4));
        $oneLine = preg_replace('/\s+/', ' ', trim($sql));
        $label = "<magenta>Polyglot:Query</magenta>";

        Strata::app()->log($oneLine . $timer, $label);
    }
}
