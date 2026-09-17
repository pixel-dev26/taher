<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    private const KEYS = [
        'company_name', 'company_address', 'company_phone', 'company_fax',
        'company_email', 'company_gstin', 'company_state',
        'default_low_stock_threshold', 'default_gst_rate',
    ];

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
            // Raster formats only: an SVG can carry <script>, and it would be
            // served from this origin. mimes: sniffs the content, not the name.
            'company_logo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:100',
            'company_fax' => 'nullable|string|max:100',
            'company_email' => 'nullable|email|max:255',
            'company_gstin' => 'nullable|string|max:20',
            'company_state' => 'nullable|string|max:100',
            // Required: a blank here used to be stored as NULL, and every
            // challan then printed 0% GST.
            'default_low_stock_threshold' => 'required|integer|min:0',
            'default_gst_rate' => 'required|numeric|min:0|max:100',
        ]);

        $userId = auth()->id();

        // Only keys actually submitted, and only when the value changed:
        // Setting::set() stamps updated_by/updated_at, so writing untouched
        // fields would log a spurious "updated" entry for each of them, and
        // a key missing from the request must not wipe the stored value.
        foreach (self::KEYS as $key) {
            if (! $request->has($key)) {
                continue;
            }

            $value = $request->input($key);

            if ((string) Setting::get($key) !== (string) $value) {
                Setting::set($key, $value, $userId);
            }
        }

        if ($request->hasFile('company_logo')) {
            $old = Setting::get('company_logo');
            $path = $request->file('company_logo')->store('logo', 'public');
            Setting::set('company_logo', $path, $userId);

            if ($old && $old !== $path) {
                Storage::disk('public')->delete($old);
            }
        }

        return back()->with('success', 'Settings updated successfully.');
    }
}
