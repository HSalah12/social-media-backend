<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class PrivacySettingsController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'setting_name' => 'required|string',
            'value' => 'required|string|in:true,false',
        ]);

        $user = $request->user();
        $settings = json_decode($user->getPrivacySettings(), true);

        // Initialize settings as an empty array if it's null
        if ($settings === null) {
            $settings = [];
        }

        $settings[$request->input('setting_name')] = $request->input('value') === 'true';

        $user->setPrivacySettings(json_encode($settings));

        return response()->json(['message' => 'Privacy settings updated successfully']);
    }
}
