<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateGoogleSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('google.googleConfig', []);
    }
}
