<?php

use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $pass = $this->randomPassword();
        $password = Hash::make($pass);
        $this->command->getOutput()->writeln("Username: Master");
        $this->command->getOutput()->writeln("Email: master@semmi.ru");
        $this->command->getOutput()->writeln("Password: $pass");

        User::insert(array(
                'name' => 'Master',
                'email' => 'master@semmi.ru',
                'password' => $password,
        ));

    }

    private function randomPassword()
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, strlen($alphabet)-1);
            $pass[$i] = $alphabet[$n];
        }
        return implode("", $pass);
    }
}
