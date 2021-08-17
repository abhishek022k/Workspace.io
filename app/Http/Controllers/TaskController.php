<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Notifications\NewTaskMail;
use App\Notifications\StatusUpdate;
use App\Notifications\TaskUpdate;
use Pusher\Pusher;

class TaskController extends Controller
{
    protected $app_id;
    protected $app_key;
    protected $app_secret;
    protected $options;
    public function __construct()
    {
        $this->app_id  = env('PUSHER_APP_ID');
        $this->app_key = env('PUSHER_APP_KEY');
        $this->app_secret = env('PUSHER_APP_SECRET');
        $this->options = [
            'cluster' => 'ap2',
            'useTLS' => false
        ];
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'due_date' => 'required|date',
            'description' => 'string',
            'assignee' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all()], 422);
        }
        if (!(User::find($request->assignee))) {
            return response()->json(['message' => ['The selected assignee is invalid']], 422);
        }
        $newTask = $request->all();
        if ($request->has('due_date')) {
            $newTask['due_data'] = date("Y-m-d H:i:s", strtotime($newTask['due_date']));
        }
        $newTask['status'] = 'ASSIGNED';
        $newTask['created_by'] = Auth::user()->id;
        $task = Task::create($newTask);
        $assignee = User::find($task->assignee);
        $assignee->notify(new NewTaskMail($task, $assignee->name, Auth::user()->name));
        $modified = ['task' => $task, 'assignee' => $assignee, 'assignor' => Auth::user()];
        $pusher = new Pusher($this->app_key, $this->app_secret, $this->app_id, $this->options);
        $pusher->trigger('my-channel' . $assignee->id, 'create-event', [
            'message' => 'NEW TASK ASSIGNED: ' . $task->title,
        ]);
        return response()->json([
            'message' => ['Task Created Successfully'],
            'results' => $modified,
        ], 201);
    }

    public function update(Request $request)
    {
        $task = Task::find($request->id);
        if (!$task) {
            return response()->json(['message' => 'Task does not exist'], 404);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'string',
            'due_date' => 'date',
            'description' => 'string',
            'status' => [Rule::in(['ASSIGNED', 'IN PROGRESS', 'COMPLETED', 'DELETED'])],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all()], 422);
        }
        if (Auth::user()->id == $task->created_by) {
            $updatedTask = $request->only(['title', 'description', 'due_date']);
            if ($request->has('due_date')) {
                $updatedTask['due_data'] = date("Y-m-d H:i:s", strtotime($updatedTask['due_date']));
            }
            $task->update($updatedTask);
            $assignee = User::find($task->assignee);
            $assignee->notify(new TaskUpdate($task, $assignee->name, Auth::user()->name));
        }
        if (Auth::user()->id == $task->assignee && $request->has('status')) {
            $statusUpdate = $request->only('status');
            $task->update($statusUpdate);
            $assignor = User::find($task->created_by);
            $assignor->notify(new StatusUpdate($task, Auth::user()->name, $assignor->name));
        }
        if (Auth::user()->id == $task->created_by || (Auth::user()->id == $task->assignee && $request->has('status'))) {
            $modified = ['task' => $task, 'assignee' => User::find($task->assignee), 'assignor' => User::find($task->created_by)];
            return response()->json(['message' => 'Task updated successfully', 'task' => $modified]);
        }
        return response()->json(['message' => 'You are not authorized to edit this task'], 401);
    }

    public function showList(Request $request)
    {
        $s = $request->search;
        $ae = $request->assignee;
        $ar = $request->assignor;
        $User = Auth::user();
        $tasks = Task::when($User->admin_access == 0, function ($q) use ($User) {
            $q->where(function ($e) use ($User) {
                $e->where('assignee', '=', $User->id)->orWhere('created_by', '=', $User->id);
            });
        })->when($request->has('search'), function ($q) use ($s) {
            $q->where(function ($e) use ($s) {
                $e->where('title', 'LIKE', '%' . $s . '%')->orWhere('description', 'LIKE', '%' . $s . '%');
            });
        })->when($request->has('assignee'), function ($q) use ($ae) {
            $q->where('assignee', '=', $ae);
        })->when($request->has('assignor'), function ($q) use ($ar) {
            $q->where('created_by', '=', $ar);
        })->get();
        return response()->json(['results' => $this->modifyIncludeUsers($tasks)]);
    }

    private function modifyIncludeUsers($tasks)
    {
        $modified = array();
        foreach ($tasks as $task) {
            $assignee = User::find($task->assignee);
            $assignor = User::find($task->created_by);
            if ($assignor && $assignee) {
                array_push($modified, ['task' => $task, 'assignee' => $assignee, 'assignor' => $assignor]);
            }
        }
        return $modified;
    }
}
