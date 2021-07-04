<?php

namespace App\Http\Controllers;

use App\Models\GoogleSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GscSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('demo');
    }

    public function index(GoogleSettings $settings)
    {
        $configData = $settings->googleConfig;
        $hasConfig = isset($configData["type"]) && $configData['type'] === 'service_account';
        $accountMail = $configData['client_email'] ?? null;
        return view('gscsettings.index')
            ->with("hasConfig", $hasConfig)
            ->with("accountMail", $accountMail);
    }

    public function apply(Request $request, GoogleSettings $settings)
    {
        $request->validate([
            'config_data'=> ['required','string']
        ]);

        $configData = @json_decode($request->get('config_data'), true);
        if(null === $configData){
            Session::flash('fail', 'Не удалось прочитать конфигурацию, проверьте верность данных');
            return back();
        }
        if(!isset($configData['type']) || (isset($configData['type'])
                && $configData['type'] !== 'service_account')){
            Session::flash('fail', 'Неизвестный тип конфигурации, для приложения нужена кофигурация сервис аккаунта');
            return back();
        }
        $settings->googleConfig = $configData;
        $settings->save();

        Session::flash("success", "Конфигурация аккаунта успешно сохранена!");

        return back();
    }

    public function delete(GoogleSettings $settings){
       $settings->googleConfig = [];
       $settings->save();

       Session::flash("success", "Конфигурация аккаунта успешно удалена!");

       return back();
    }
}
