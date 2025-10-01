<?php

namespace App\Http\Controllers\API\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    /**epf
     * Return the list of panels assigned to the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get panels assigned to user where firm_id is not null
        $panels = $user->panels()
            ->where('is_inactive', false)
            ->where('panel_type', '1')

//>whereNotNull('deleted_at')
//in case of pivot tables like this, panel and user pivot table - panel_user,
// wherenotnull will give 500 error because deleted_at is not present on each master table
// therefore we will check  ----> wherePivotNull('deleted_at)'to check fields on pivot tables
            ->wherePivotNotNull('firm_id')
            ->wherePivotNull('deleted_at')
            ->get();


        return response()->json([
            'message_type' => 'success',
            'message_display' => 'flash',
            'message' => 'Panels Details Fetched',
            'panels' => $panels,
        ]);
    }
}
