<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        // Validate query parameters
        $request->validate([
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'q' => 'nullable|string|max:255',
            'due_before' => 'nullable|date',
            'due_after' => 'nullable|date',
            'overdue' => 'nullable|boolean',
            'sort' => 'nullable|in:title,status,deadline,created_at,updated_at',
            'order' => 'nullable|in:asc,desc',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        // Build query
        $query = $user->tasks();

        // Apply filters manually (not using scopes for now)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('q')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->q . '%')
                  ->orWhere('description', 'LIKE', '%' . $request->q . '%');
            });
        }

        if ($request->has('due_before')) {
            $query->whereDate('deadline', '<=', $request->due_before);
        }

        if ($request->has('due_after')) {
            $query->whereDate('deadline', '>=', $request->due_after);
        }

        if ($request->boolean('overdue')) {
            $query->where('deadline', '<', now())
                  ->whereNotIn('status', ['completed', 'cancelled']);
        }

        // Apply sorting
        $sortColumn = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortColumn, $sortOrder);

        // Get paginated results
        $perPage = $request->get('per_page', 10);
        $tasks = $query->paginate($perPage);

        // Format response
        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully',
            'data' => $tasks->items(),
            'meta' => [
                'total' => $tasks->total(),
                'per_page' => $tasks->perPage(),
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'from' => $tasks->firstItem(),
                'to' => $tasks->lastItem(),
                'path' => $request->url(),
                'has_more' => $tasks->hasMorePages()
            ],
            'filters' => $this->getActiveFilters($request)
        ]);
    }

    public function store(TaskRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $data = $request->validated();
        $task = $user->tasks()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    public function show($id)
    {
        // Query task dengan ownership check sekaligus
        // Ini PENTING untuk mencegah user melihat task user lain
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();
        
        // Return 404 untuk kedua kasus: task tidak ada ATAU bukan milik user
        // Ini mencegah user mengetahui task ID mana yang exist
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        $task->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Task retrieved successfully',
            'data' => $task
        ]);
    }

    public function update(TaskRequest $request, $id)
    {
        // Query task dengan ownership check
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();
        
        // Return 404 jika task tidak ada atau bukan milik user
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        // Check status transition
        if ($request->has('status') && 
            !$task->canTransitionTo($request->status)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status transition'
            ], 422);
        }

        $task->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task
        ]);
    }

    public function destroy($id)
    {
        // Query task dengan ownership check
        $task = Task::where('id', $id)
                    ->where('user_id', auth()->id())
                    ->first();
        
        // Return 404 jika task tidak ada atau bukan milik user
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        $task->delete();

        return response()->json([
            'Task Deleted!'
        ], 204);
    }

    protected function applyFilters($query, Request $request)
    {
        // Status filter
        if ($request->has('status')) {
            $query->status($request->status);
        }

        // Search filter
        if ($request->has('q')) {
            $query->search($request->q);
        }

        // Date filters
        if ($request->has('due_before')) {
            $query->dueBefore($request->due_before);
        }

        if ($request->has('due_after')) {
            $query->dueAfter($request->due_after);
        }

        // Overdue filter
        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        return $query;
    }

    protected function getActiveFilters(Request $request)
    {
        $filters = [];
        $filterParams = ['status', 'q', 'due_before', 'due_after', 'overdue', 'sort', 'order'];
        
        foreach ($filterParams as $param) {
            if ($request->has($param)) {
                $filters[$param] = $request->get($param);
            }
        }
        
        return $filters;
    }

    protected function generateCacheKey(Request $request)
    {
        $userId = auth()->id();
        $params = $request->only(['status', 'q', 'due_before', 'due_after', 'overdue', 'sort', 'order', 'page', 'per_page']);
        
        return 'tasks_' . $userId . '_' . md5(json_encode($params));
    }

    // Statistics endpoint
    public function statistics()
    {
        $user = auth()->user();
        
        $stats = Cache::remember('task_stats_' . $user->id, 300, function () use ($user) {
            return [
                'total' => $user->tasks()->count(),
                'by_status' => [
                    'pending' => $user->tasks()->status('pending')->count(),
                    'in_progress' => $user->tasks()->status('in_progress')->count(),
                    'completed' => $user->tasks()->status('completed')->count(),
                    'cancelled' => $user->tasks()->status('cancelled')->count(),
                ],
                'overdue' => $user->tasks()->overdue()->count(),
                'upcoming_week' => $user->tasks()->upcoming(7)->count(),
                'completed_this_month' => $user->tasks()
                    ->status('completed')
                    ->whereMonth('updated_at', now()->month)
                    ->count()
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}
