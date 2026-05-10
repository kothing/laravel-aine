<?php

namespace App\Http\Controllers;

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
                'description' => ''
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
                'description' => $request->description ?? ''
            ]);
        } else {
            $setting->name = $request->name;
            $setting->description = $request->description ?? '';
            $setting->save();
        }
        
        return response()->json(['success' => true]);
    }
}