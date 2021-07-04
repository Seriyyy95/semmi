<?php

declare(strict_types=1);

namespace App\Services\Logger;

/**
 * Class GscLoadLogger
 * @package App\Services\Logger
 */
class GscLoadLogger extends LoadLogger
{

    public function __construct(){
       parent::__construct('gsc');
    }
}
