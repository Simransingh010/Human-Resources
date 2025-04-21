<?php

namespace App\Http\Controllers\API\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Saas\SystemUsage;

class SystemInfoController extends Controller
{
    /**
     * Get firm versions, maintenance modes, and system settings for the logged-in user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSystemInfo(Request $request)
    {
        // Validate that firm_id is provided and exists
        $data = $request->validate([
            'firm_id' => 'required|exists:firms,id',
        ]);

        $firmId = $data['firm_id'];

        // Check if the authenticated user is associated with the specified firm.
        if (!$request->user()->firms()->where('firms.id', $firmId)->exists()) {
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Unauthorized firm access.'
            ], 403);
        }

        // Get the firm versions assigned to this firm.
        // Assuming firm_versions table has a "type" field handled as a string (e.g., "mandatory" or "latest")

        $firmVersions = DB::table('firm_versions')
            ->join('versions', 'firm_versions.version_id', '=', 'versions.id')
            ->where('firm_versions.firm_id', $firmId)
            ->select('firm_versions.*', 'versions.*') // Adjust the columns as needed
            ->get();


//        dd($firmVersions);
        // Extract mandatory and latest versions.
        $mandatoryVersion = $firmVersions->where('type', 'mandatory')->where('device_type','android')->first();
        $latestVersion    = $firmVersions->where('type', 'latest')->where('device_type','android')->first();

        $mandatoryVersion_ios = $firmVersions->where('type', 'mandatory')->where('device_type','ios')->first();
        $latestVersion_ios    = $firmVersions->where('type', 'latest')->where('device_type','ios')->first();


        // Get maintenance modes for the firm (by platform)
        $maintenanceModes = DB::table('maintenance_modes')
            ->where('firm_id', $firmId)
            ->get();

        // Get system settings for the firm.
        // This fetches settings specific to the firm and global settings (where firm_id is null)
        $systemSettings = DB::table('system_settings')
            ->where('firm_id', $firmId)
            ->orWhereNull('firm_id')
            ->get();

        return response()->json([
            'firm_versions' => [
                'mandatory' => $mandatoryVersion,
                'latest'    => $latestVersion,
                'mandatory_ios' => $mandatoryVersion_ios,
                'latest_ios'    => $latestVersion_ios,
            ],
            'maintenance_modes' => $maintenanceModes,
            'system_settings'   => $systemSettings,
        ]);
    }

    /**
     * Save or update system usage for the authenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSystemUsage(Request $request)
    {
        // Validate that version_id is provided
        $data = $request->validate([
            'version_id' => 'required|exists:versions,id',
            'firm_id' => 'required|exists:firms,id',
        ]);

        // Update or create a usage record for the user and version.
        $usage = SystemUsage::updateOrCreate(
            [
                'user_id'    => $request->user()->id,
                'version_id' => $data['version_id'],
                'firm_id' => $data['firm_id'],
            ],
            [
                'last_accessed_at' => now(),
            ]
        );

        return response()->json([
            'message_type' => 'success',
            'message_display' => 'flash',
            'message'       => 'System usage saved successfully.',
            'system_usage'  => $usage,
        ]);
    }
}
