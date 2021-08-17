<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;



class UserController extends Controller
{
    public function showList(Request $request)
    {
        $s = $request->search;
        $d = $request->deleted;
        $show = $request->input('show', 8);
        if ($s && !($d)) {
            $usersList = User::where('verified', '=', 1)->where(function ($q) use ($s) {
                $q->where('name', 'LIKE', '%' . $s . '%')->orWhere('email', 'LIKE', '%' . $s . '%')->get();
            })->paginate($show);
        } else if ($s && $d) {
            if (Auth::user()->admin_access == 1) {
                $usersList = User::withTrashed()->where('verified', '=', 1)->where(function ($q) use ($s) {
                    $q->where('name', 'LIKE', '%' . $s . '%')->orWhere('email', 'LIKE', '%' . $s . '%')->get();
                })->paginate($show);
            } else {
                $usersList = User::where('verified', '=', 1)->where(function ($q) use ($s) {
                    $q->where('name', 'LIKE', '%' . $s . '%')->orWhere('email', 'LIKE', '%' . $s . '%')->get();
                })->paginate($show);
            }
        } else if ($d && !($s)) {
            if (Auth::user()->admin_access == 1) {
                $usersList = User::withTrashed()->paginate($show);
            } else {
                $usersList = User::paginate($show);
            }
        } else {
            $usersList = User::paginate($show);
        }
        if ($d && Auth::user()->admin_access == 1) {
            $usersList->data = $usersList->makeVisible('deleted_at');
        }
        if ($usersList->isEmpty()) {
            return response()->json([], 204);
        };
        return response()->json([
            'results' => $usersList
        ], 200);
    }
    public function detail(Request $request)
    {
        $user = User::withTrashed()->find($request->user);
        $user->makeVisible("deleted_at");
        if ($user) {
            return response()->json([
                'user' => $user,
            ]);
        }
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }
    public function delete(Request $request)
    {
        $user = User::find($request->id);
        if ($user) {
            $user->delete();
            $user->save();
            return response()->json([
                'message' => "User deleted successfully",
            ], 200);
        }
        return response()->json([
            'message' => "User does not exist"
        ], 404);
    }
    public function restore(Request $request)
    {
        $user = User::onlyTrashed()->find($request->id);
        if ($user) {
            $user->restore();
            return response()->json([
                'message' => "User account restored successfully",
            ], 200);
        }
        return response()->json([
            'message' => "User does not exist"
        ], 404);
    }
    public function allUsers(){
        $users = User::where("verified","=",1)->get();
        return response()->json([
            'results' => $users,
        ]);
    }
}
