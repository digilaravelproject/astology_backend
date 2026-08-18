<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminFcmSetting;
use App\Services\Notification\FcmChannelDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Exception;

class AdminFcmSettingController extends Controller
{
    /**
     * Show Firebase FCM Settings page.
     */
    public function index(FcmChannelDriver $fcmDriver): View
    {
        $setting = AdminFcmSetting::current();
        $isConfigured = $fcmDriver->isConfigured();
        $serviceAccountPath = $fcmDriver->getServiceAccountPath();
        $projectId = $fcmDriver->getProjectId();

        $fileDetails = null;
        if ($serviceAccountPath && file_exists($serviceAccountPath)) {
            $fileDetails = [
                'filename'    => basename($serviceAccountPath),
                'size_kb'     => round(filesize($serviceAccountPath) / 1024, 2),
                'modified_at' => date('d M Y, h:i A', filemtime($serviceAccountPath)),
            ];
        }

        return view('admin.fcm_settings.index', compact(
            'setting',
            'isConfigured',
            'serviceAccountPath',
            'projectId',
            'fileDetails'
        ));
    }

    /**
     * Update FCM general configuration options.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'project_id'         => 'nullable|string|max:191',
            'is_active'          => 'nullable|boolean',
            'default_sound'      => 'nullable|string|max:100',
            'call_channel_id'    => 'nullable|string|max:100',
            'chat_channel_id'    => 'nullable|string|max:100',
            'default_channel_id' => 'nullable|string|max:100',
        ]);

        $setting = AdminFcmSetting::current();
        $setting->update([
            'project_id'         => $request->input('project_id'),
            'is_active'          => $request->boolean('is_active', true),
            'default_sound'      => $request->input('default_sound', 'default'),
            'call_channel_id'    => $request->input('call_channel_id', 'call_channel'),
            'chat_channel_id'    => $request->input('chat_channel_id', 'chat_channel'),
            'default_channel_id' => $request->input('default_channel_id', 'astology_notifications'),
        ]);

        return redirect()->back()->with('success', 'Firebase settings updated successfully.');
    }

    /**
     * Upload and securely store Firebase Service Account JSON file.
     */
    public function uploadServiceAccount(Request $request): RedirectResponse
    {
        $request->validate([
            'service_account_file' => 'required|file|mimes:json,txt|max:2048',
        ]);

        try {
            $file = $request->file('service_account_file');
            $content = file_get_contents($file->getRealPath());
            $json = json_decode($content, true);

            if (!is_array($json) || empty($json['project_id']) || empty($json['private_key']) || empty($json['client_email'])) {
                return redirect()->back()->with('error', 'Invalid Service Account JSON. File must contain project_id, client_email, and private_key.');
            }

            $targetDirectory = storage_path('app/firebase');
            if (!File::isDirectory($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0750, true, true);
            }

            $targetPath = $targetDirectory . '/service-account.json';
            file_put_contents($targetPath, $content);
            @chmod($targetPath, 0600); // Restrict permissions

            $setting = AdminFcmSetting::current();
            $setting->update([
                'service_account_json_path' => 'app/firebase/service-account.json',
                'project_id'                => $json['project_id'],
            ]);

            return redirect()->back()->with('success', "Service Account for project '{$json['project_id']}' uploaded and verified successfully.");

        } catch (Exception $e) {
            Log::error('Service Account Upload Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to upload service account file: ' . $e->getMessage());
        }
    }

    /**
     * Live AJAX Test Connection button.
     */
    public function testConnection(FcmChannelDriver $fcmDriver): JsonResponse
    {
        try {
            $result = $fcmDriver->testConnection();
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception during test connection: ' . $e->getMessage(),
            ], 500);
        }
    }
}
