<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Http\Resources\SettingResource;

class SettingsController extends Controller
{
    /**
     * Get settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\SettingResource
     */
    public function index(Request $request){
        $setting = Setting::first();
        
        if (!$setting) {
            $setting = Setting::create([
                'name' => config('app.name', 'My Website'),
                'description' => 'Aine is a content management system built with Laravel and Vue.js',
                'version' => '0.0.1'
            ]);
        }

        return new SettingResource($setting);
    }

    /**
     * Update settings
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $setting = Setting::first();
        
        if (!$setting) {
            $setting = Setting::create([
                'name' => $request->name,
                'description' => $request->description ?? '',
                'version' => $request->version ?? ''
            ]);
        } else {
            $setting->name = $request->name;
            $setting->description = $request->description ?? '';
            $setting->version = $request->version ?? '';
            $setting->save();
        }
        
        return response()->json(['success' => true]);
    }
}