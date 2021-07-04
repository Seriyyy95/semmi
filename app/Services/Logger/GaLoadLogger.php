<?php

declare(strict_types=1);

namespace App\Services\Logger;

/**
 * Class GaLoadLogger
 * @package App\Services\Logger
 */
class GaLoadLogger extends LoadLogger
{

    public function __construct(){
       parent::__construct('ga');
    }
}
