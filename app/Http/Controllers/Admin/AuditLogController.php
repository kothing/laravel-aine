<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

class AuditLogController extends Controller
{
    /**
     * List audit logs for a project.
     *
     * Supports filtering by action / entity type and pagination.
     *
     * @param int $project_id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->isSuperAdmin() && !$user->hasRole('admin' . $project->id)) {
            throw UnauthorizedException::forRoles(['admin' . $project->id]);
        }

        $query = AuditLog::where('project_id', $project->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->get('entity_type'));
        }

        $logs = $query->paginate($request->get('limit', 50));

        return response()->json([
            'data' => $logs->getCollection()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'project_id' => $log->project_id,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'action' => $log->action,
                    'entity_type' => $log->entity_type,
                    'entity_id' => $log->entity_id,
                    'entity_label' => $log->entity_label,
                    'details' => $log->details,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ], 200);
    }
}
