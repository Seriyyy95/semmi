<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\LaravelSettings\Settings;

/**
 * Class GoogleSettings
 * @package App\Models
 */
class GoogleSettings extends Settings
{
    public array $googleConfig;

    /**
     * @return string
     */
    public static function group(): string
    {
        return 'google';
    }

    /**
     * @return bool
     */
    public function isValid() : bool{
        if(count($this->googleConfig) === 0){
            return false;
        }
        if(!isset($this->googleConfig['type'])){
            return false;
        }
        if($this->googleConfig['type'] !== 'service_account'){
            return false;
        }

        return true;
    }
}
