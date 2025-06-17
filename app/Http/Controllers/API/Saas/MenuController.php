<?php

namespace App\Http\Controllers\API\Saas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Saas\Action;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class MenuController extends Controller
{
    /**
     * Return the menu items based on the selected firm and panel,
     * filtering permissions according to the modules assigned to that panel,
     * and grouping them hierarchically (App → Modules → Permissions).
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            // Validate the request: firm_id and panel_id are required.
            $request->validate([
                'firm_id'  => 'required|integer|exists:firms,id',
                'panel_id' => 'required|integer|exists:panels,id',
            ]);

            $firmId = $request->input('firm_id');
            $panelId = $request->input('panel_id');

            // Ensure that the user is associated with the selected firm.
            try {
                $isFirmAssociated = $user->firms()->where('firms.id', $firmId)->exists();
                if (!$isFirmAssociated) {
                    return response()->json([
                        'message_type' => 'error',
                        'message_display' => 'flash',
                        'message' => 'Unauthorized firm access.'
                    ], 403);
                }
            } catch (Exception $e) {
                Log::error('Firm association check failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error checking firm association: ' . $e->getMessage()
                ], 500);
            }

            // Ensure that the user is associated with the selected panel.
            try {
                $isPanelAssociated = $user->panels()->where('panels.id', $panelId)->exists();
                if (!$isPanelAssociated) {
                    return response()->json([
                        'message_type' => 'error',
                        'message_display' => 'flash',
                        'message' => 'Unauthorized panel access.'
                    ], 403);
                }
            } catch (Exception $e) {
                Log::error('Panel association check failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error checking panel association: ' . $e->getMessage()
                ], 500);
            }

            // Retrieve roles assigned to the user.
            try {
                $roleIds = DB::table('role_user')
                    ->where('user_id', $user->id)
                    ->pluck('role_id')
                    ->toArray();
            } catch (Exception $e) {
                Log::error('Role retrieval failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error retrieving user roles: ' . $e->getMessage()
                ], 500);
            }

            // this need to be fixed later, as some case user may assigned with actions wihout assiging roles,
            // so we have to merge direct actions and actions against assigned role than after merging if no action found than the eror should come
            if (empty($roleIds)) {
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'No Role assigned for the user.'
                ], 403);
            }

            // Get permission IDs associated with these permission groups.
            try {
                $actionIds = DB::table('action_role')
                    ->whereIn('role_id', $roleIds)
                    ->pluck('action_id')
                    ->unique()
                    ->toArray();
            } catch (Exception $e) {
                Log::error('Action IDs retrieval failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error retrieving action IDs: ' . $e->getMessage()
                ], 500);
            }

            // Get list of app_module IDs that are assigned to the selected panel.
            try {
                $componentPanelIds = DB::table('component_panel')
                    ->where('panel_id', $panelId)
                    ->pluck('component_id')
                    ->toArray();
            } catch (Exception $e) {
                Log::error('Component panel IDs retrieval failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error retrieving component panel IDs: ' . $e->getMessage()
                ], 500);
            }

            // Retrieve permission details along with the related app_module and app.
            try {
                $actions = Action::with(['component.modules.apps'])
                    ->whereIn('id', $actionIds)
                    ->whereIn('component_id', $componentPanelIds)
                    ->where('is_inactive', false)
                    ->get();
            } catch (Exception $e) {
                Log::error('Actions retrieval failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error retrieving actions: ' . $e->getMessage()
                ], 500);
            }

            // Build hierarchical structure: App → Modules → Permissions.
            try {
                $menuHierarchy = [];

                foreach ($actions as $action) {
                    $module = $action->component->modules->first();
                    if (!$module) {
                        continue; // Skip if module relation is missing.
                    }
                    $app = $module->apps->first();
                    if (!$app) {
                        continue; // Skip if app relation is missing.
                    }

                    $appId = $app->id;
                    $moduleId = $module->id;
                    // We have skipped ComponentID as it has not much role in case of App, especially in Attendance it is My Attendance - which is obviously known so we directly showing Mark My Attendance & View My Attendance

                    // Initialize app level if not exists.
                    if (!isset($menuHierarchy[$appId])) {
                        $menuHierarchy[$appId] = [
                            'id'         => $app->id,
                            'name'       => $app->name,
                            'code'       => $app->code,
                            'description'=> $app->description,
                            'icon'       => $app->icon,
                            'route'      => $app->route,
                            'color'      => $app->color,
                            'tooltip'    => $app->tooltip,
                            'order'      => $app->order,
                            'badge'      => $app->badge,
                            'custom_css' => $app->custom_css,
                            'modules'    => [],
                        ];
                    }

                    // Initialize module level if not exists.
                    if (!isset($menuHierarchy[$appId]['modules'][$moduleId])) {
                        $menuHierarchy[$appId]['modules'][$moduleId] = [
                            'id'         => $module->id,
                            'name'       => $module->name,
                            'code'       => $module->code,
                            'description'=> $module->description,
                            'icon'       => $module->icon,
                            'route'      => $module->route,
                            'color'      => $module->color,
                            'tooltip'    => $module->tooltip,
                            'order'      => $module->order,
                            'badge'      => $module->badge,
                            'custom_css' => $module->custom_css,
                            'permissions'=> [],
                        ];
                    }

                    // Append permission to the module.
                    $menuHierarchy[$appId]['modules'][$moduleId]['permissions'][] = [
                        'id'         => $action->id,
                        'name'       => $action->name,
                        'code'       => $action->code,
                        'description'=> $action->description,
                        'icon'       => $action->icon,
                        'route'      => '',
                        'color'      => $action->color,
                        'tooltip'    => $action->tooltip,
                        'order'      => $action->order,
                        'badge'      => $action->badge,
                        'custom_css' => $action->custom_css,
                    ];
                }

                // Convert hierarchical array keys to sequential arrays.
                $result = array_map(function ($app) {
                    $app['modules'] = @array_values($app['modules']);
                    return $app;
                }, array_values($menuHierarchy));

                return response()->json([
                    'menus' => $result,
                ]);
            } catch (Exception $e) {
                Log::error('Menu hierarchy building failed: ' . $e->getMessage());
                return response()->json([
                    'message_type' => 'error',
                    'message_display' => 'flash',
                    'message' => 'Error building menu hierarchy: ' . $e->getMessage()
                ], 500);
            }
        } catch (Exception $e) {
            Log::error('Menu API failed: ' . $e->getMessage());
            return response()->json([
                'message_type' => 'error',
                'message_display' => 'flash',
                'message' => 'Error retrieving menu: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
