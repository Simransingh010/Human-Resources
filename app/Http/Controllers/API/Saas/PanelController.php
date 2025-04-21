<?php

namespace App\Http\Controllers\API\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    /**
     * Return the list of panels assigned to the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Assuming the user model has a 'panels' relationship.
        $panels = $user->panels()->where('is_inactive', false)->where('panel_type','1')->get();

        return response()->json([
            'message_type' => 'success',
            'message_display' => 'flash',
            'message' => 'Panels Details Fetched',
            'panels' => $panels,
        ]);
    }
}
