<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        $settings = [
            'company_name' => Setting::get('company_name', ''),
            'company_logo' => Setting::get('company_logo', ''),
            'company_address' => Setting::get('company_address', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'company_fax' => Setting::get('company_fax', ''),
            'company_email' => Setting::get('company_email', ''),
            'default_low_stock_threshold' => Setting::get('default_low_stock_threshold', 10),
        ];

        return view('settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_logo' => 'nullable|image|max:2048',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:100',
            'company_fax' => 'nullable|string|max:100',
            'company_email' => 'nullable|email|max:255',
            'default_low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $userId = auth()->id();

        Setting::set('company_name', $request->company_name, $userId);
        Setting::set('company_address', $request->company_address, $userId);
        Setting::set('company_phone', $request->company_phone, $userId);
        Setting::set('company_fax', $request->company_fax, $userId);
        Setting::set('company_email', $request->company_email, $userId);
        Setting::set('default_low_stock_threshold', $request->default_low_stock_threshold, $userId);

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logo', 'public');
            Setting::set('company_logo', $path, $userId);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
