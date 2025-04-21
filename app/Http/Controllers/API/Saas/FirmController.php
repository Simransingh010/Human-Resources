<?php

namespace App\Http\Controllers\API\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FirmController extends Controller
{
    /**
     * Return the list of firms associated with the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Assuming the user model has a 'firms' relationship.
        $firms = $user->firms()->get();

        return response()->json([
            'message_type' => 'success',
            'message_display' => 'flash',
            'message' => 'firms list fetched',
            'firms' => $firms,
        ]);
    }
}
