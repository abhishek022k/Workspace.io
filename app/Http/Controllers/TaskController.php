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

class TaskController extends Controller
{
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'due_date' => 'required|date',
            'description' => 'string',
            'status' => [Rule::in(['ASSIGNED', 'IN PROGRESS', 'COMPLETED', 'DELETED'])],
            'assignee' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all()], 422);
        }
        $newTask = $request->all();
        if ($request->has('due_date')) {
            $newTask['due_data'] = date("Y-m-d H:i:s", strtotime($newTask['due_date']));
        }
        if (!$request->has('status')) {
            $newTask['status'] = 'ASSIGNED';
        }
        $newTask['created_by'] = Auth::user()->id;
        $task = Task::create($newTask);
        $assignee = User::find($task->assignee);
        $assignee->notify(new NewTaskMail($task, $assignee->name, Auth::user()->name));
        $modified = ['task' => $task, 'assignee' => $assignee, 'assignor' => Auth::user()];
        return response()->json([
            'message' => 'Task Created Successfully',
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
        
        // $modified = $this->modifyIncludeUsers();
    }

    private function modifyIncludeUsers($tasks)
    {
        $modified = array();
        foreach ($tasks as $task) {
            array_push($modified, ['task' => $task, 'assignee' => User::find($task->assignee), 'assignor' => User::find($task->created_by)]);
        }
        return $modified;
    }
}
