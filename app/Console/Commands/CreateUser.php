<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use Hash;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create {username} {--password=?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $username = $this->argument('username');
        $email = $this->ask('Enter user email');
        $pass = $this->secret('Enter user password');
        $password = Hash::make($pass);

        User::insert(array(
            'name' => $username,
            'email' => $email,
            'password' => $password,
        ));
        $this->info("User created successfuly!");
    }
}
