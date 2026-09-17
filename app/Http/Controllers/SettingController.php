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
            'company_gstin' => Setting::get('company_gstin', ''),
            'company_state' => Setting::get('company_state', ''),
            'default_low_stock_threshold' => Setting::get('default_low_stock_threshold', 10),
            'default_gst_rate' => Setting::get('default_gst_rate', 18),
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
            'company_gstin' => 'nullable|string|max:20',
            'company_state' => 'nullable|string|max:100',
            'default_low_stock_threshold' => 'nullable|integer|min:0',
            'default_gst_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $userId = auth()->id();

        // Only write keys whose value actually changed. Setting::set() always
        // stamps updated_by/updated_at, so calling it unconditionally for all
        // 8 fields on every save — regardless of which one the user actually
        // edited — used to log a spurious "updated" entry (just updated_by
        // churn, no real value change) for every untouched field, and could
        // write up to 9 Activity Log rows for a single save.
        $fields = [
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'company_phone' => $request->company_phone,
            'company_fax' => $request->company_fax,
            'company_email' => $request->company_email,
            'company_gstin' => $request->company_gstin,
            'company_state' => $request->company_state,
            'default_low_stock_threshold' => $request->default_low_stock_threshold,
            'default_gst_rate' => $request->default_gst_rate,
        ];

        foreach ($fields as $key => $value) {
            if ((string) Setting::get($key) !== (string) $value) {
                Setting::set($key, $value, $userId);
            }
        }

        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('logo', 'public');
            Setting::set('company_logo', $path, $userId);
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
