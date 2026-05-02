<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PwaManifestController extends Controller
{
    public function superAdminManifest()
    {
        $globalSettings = \App\Models\GlobalSetting::first();
        $churchName = $globalSettings?->church_name ?? 'Doxa Commission Global';
        $iconUrl = $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : asset('Img/doxa.PNG');

        return response()->json([
            'name' => $churchName . ' - Super Admin',
            'short_name' => $churchName . ' Admin',
            'description' => 'Super Admin dashboard for ' . $churchName,
            'start_url' => '/super-admin',
            'scope' => '/super-admin',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#2563eb',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }

    public function adminManifest()
    {
        $globalSettings = \App\Models\GlobalSetting::first();
        $churchName = $globalSettings?->church_name ?? 'Doxa Commission Global';
        $iconUrl = $globalSettings?->logo ? asset('storage/' . $globalSettings->logo) : asset('Img/doxa.PNG');

        return response()->json([
            'name' => $churchName . ' - Admin',
            'short_name' => $churchName . ' Portal',
            'description' => 'Admin and Team Lead dashboard for ' . $churchName,
            'start_url' => '/admin/dashboard',
            'scope' => '/admin',
            'display' => 'standalone',
            'background_color' => '#0f172a',
            'theme_color' => '#2563eb',
            'orientation' => 'portrait-primary',
            'icons' => [
                [
                    'src' => $iconUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $iconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'subscription' => 'required|array',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        \App\Models\PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $request->input('subscription.endpoint'),
            ],
            [
                'keys' => json_encode($request->input('subscription.keys')),
            ]
        );

        return response()->json(['success' => true]);
    }
}
