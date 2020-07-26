<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use Storage;
use App\OptionsManager;
use App\GoogleDataLoader;
use App\GoogleNeedConfigFileException;
use App\GoogleAPI;

class GscSettingsController extends Controller
{

    public function __construct(){
        $this->middleware('auth');
    }

    public function index(){
        $user = Auth::user();
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user->id);
        try {
            $gloader = new GoogleDataLoader($optionsManager);
            $gloader->setMode(GoogleAPI::MODE_SERVICE);
            $gloader->setUser($user->id);
        } catch (GoogleNeedConfigFileException $e) {
            Session::flash("fail", "Необходимо загрузить файл настроек");
        }

        $hasFile = $gloader->hasConfigFile();
        return view('gscsettings.index')
            ->with("hasFile", $hasFile);
    }

    public function apply(Request $request){
        $user = Auth::user();
        $optionsManager = new OptionsManager();
        $optionsManager->setUser($user->id);
 
        $request->validate([
            'config_file'=> ['required','max:200'] 
        ]);

        try {
            $gloader = new GoogleDataLoader($optionsManager);
            $gloader->setUser($user->id);
            $gloader->setMode(GoogleAPI::MODE_SERVICE);
        } catch (GoogleNeedConfigFileException $e) {
            Session::flash("fail", "Необходимо загрузить файл настроек");
        }

        if ($request->hasFile('config_file')) {
            $storagePath = Storage::disk('data')->path('/');

            $filenameWithExt = $request->file('config_file')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('config_file')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            $storagePath = Storage::disk('data')->path('');
            $request->file('config_file')->move($storagePath, $fileNameToStore);
            $gloader->setConfigFile($storagePath . $fileNameToStore);
            Session::flash("success", "Файл настроек успешно загружен");
        }
        
        return back();
    }
}
