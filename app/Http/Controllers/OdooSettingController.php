<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\OdooSetting;
use App\Models\ImportHistory;
use App\Services\OdooSyncService;
use Illuminate\Support\Facades\Http;

class OdooSettingController extends Controller
{
    public function index()
    {
        $setting = OdooSetting::first();
        $histories = ImportHistory::orderBy('created_at', 'desc')->paginate(10);
        return view('odoo.settings', compact('setting', 'histories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'odoo_url' => 'nullable|url',
            'database' => 'nullable|string',
            'user_email' => 'nullable|email',
            'api_key' => 'nullable|string',
            'enable_auto_sync' => 'boolean',
            'sync_interval' => 'nullable|string',
        ]);
        
        $data['enable_auto_sync'] = $request->has('enable_auto_sync');

        $setting = OdooSetting::first();
        if ($setting) {
            $setting->update($data);
        } else {
            OdooSetting::create($data);
        }

        return redirect()->back()->with('success', 'Odoo configuration saved successfully.');
    }

    public function testConnection(Request $request)
    {
        $url = rtrim($request->odoo_url, '/');
        $database = $request->database;
        $username = $request->user_email;
        $password = $request->api_key;

        if (!$url || !$database || !$username || !$password) {
            return response()->json(['success' => false, 'message' => 'Please provide URL, Database, Email, and API Key to test the connection.']);
        }

        try {
            // Odoo XML-RPC authentication test endpoint equivalent via JSON-RPC
            // We'll send a basic JSON-RPC authentication request to Odoo's common endpoint
            $response = Http::when(app()->environment('local'), function ($request) {
                return $request->withoutVerifying();
            })->post("{$url}/jsonrpc", [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => 'common',
                    'method' => 'authenticate',
                    'args' => [$database, $username, $password, (object)[]],
                ],
                'id' => uniqid(),
            ]);

            $result = $response->json();

            if (isset($result['error'])) {
                $errorMsg = $result['error']['data']['message'] ?? $result['error']['message'] ?? 'Unknown Odoo Error';
                return response()->json(['success' => false, 'message' => 'Odoo Error: ' . $errorMsg]);
            }

            if (isset($result['result']) && $result['result']) {
                // If it returns a UID (integer > 0), authentication succeeded
                return response()->json(['success' => true, 'message' => 'Connection successful!']);
            } else {
                return response()->json(['success' => false, 'message' => 'Authentication failed. Please check your credentials.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    public function syncNow(Request $request)
    {
        set_time_limit(0); 
        
        try {
            $isFullSync = $request->get('isFullSync') == '1';
            $offset = (int) $request->get('offset', 0);
            $phase = $request->get('phase', 'all');
            
            if ($isFullSync && $offset === 0 && $phase === 'all') {
                $setting = \App\Models\OdooSetting::first();
                if ($setting) {
                    $setting->update(['last_sync' => null]);
                }
            }

            $service = new OdooSyncService();
            $result  = $service->sync('Manual', null, $isFullSync, $phase, $offset, 500);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'items'   => $result['items'] ?? 0,
                'hasMore' => $result['hasMore'] ?? false,
                'nextOffset' => $result['nextOffset'] ?? 0,
                'phase' => $result['phase'] ?? $phase
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSyncCounts(Request $request)
    {
        $isFullSync = $request->get('isFullSync') == '1';
        
        if ($isFullSync) {
            // Temporarily ignore last_sync for count
            $setting = \App\Models\OdooSetting::first();
            $originalLastSync = $setting->last_sync;
            $setting->update(['last_sync' => null]);
            
            $service = new OdooSyncService();
            $stats = $service->getSyncStats();
            
            // Restore last_sync (it will be nullified again when sync actually starts)
            $setting->update(['last_sync' => $originalLastSync]);
        } else {
            $service = new OdooSyncService();
            $stats = $service->getSyncStats();
        }

        return response()->json($stats);
    }


    public function syncStatus()
    {
        $latest = ImportHistory::latest()->first();
        return response()->json([
            'latest_id' => $latest?->id ?? 0,
            'status'    => $latest?->status ?? null,
            'items'     => $latest?->items ?? 0,
            'message'   => $latest?->details ?? '',
        ]);
    }

    public function syncRentalStartsNow()
    {
        set_time_limit(0);
        try {
            $service = new \App\Services\OdooSyncService();
            $result = $service->syncRentalStarts('Manual UI');
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
