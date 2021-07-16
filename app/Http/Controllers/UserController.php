<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    public function showUsers(){
        // $results = DB::select("select id, name, email, verified, admin_access from users");
        $results = User::select('id','name','email')->get();
        return $results;
    }
}
