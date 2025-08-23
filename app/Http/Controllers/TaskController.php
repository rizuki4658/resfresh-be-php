<?php
namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Http\Requests\TaskRequest;

class TaskController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = auth()->user();
        
        $tasks = $user->tasks()
            ->orderBy('deadline', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Tasks retrieved successfully',
            'data' => $tasks
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
}
