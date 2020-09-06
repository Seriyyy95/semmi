<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Hash;

class InstallController extends Controller
{
    public function index()
    {
        return view("install");
    }

    public function install(Request $request)
    {
        $rules = array(
            "user_login" => "string|required|max:255",
            "user_email" => "string|required|max:255",
            "user_password" => "string|required|max:255",
            "user_password_confirm" => "string|required|max:255"
        );

        $request->validate($rules);

        $usersCount = User::count();
        if ($usersCount > 0) {
            return back()->withFail("Программа уже установлена!");
        }
        if ($request->user_password != $request->user_password_confirm) {
            return back()->withFail("Пароли не совпадают!");
        }

        $user = new User();
        $user->name = $request->user_login;
        $user->email = $request->user_email;
        $user->password = Hash::make($request->user_password);
        $user->save();
        return redirect("home");
    }
}
