<?php

namespace App\Http\Controllers;

use App\Models\User; // this is your users table
use Illuminate\Http\Request;

class LMSUsersController extends Controller
{
    public function index()
    {
        // get all users
        $users = User::all();

        // send them to accounts.blade.php
        return view('components.adminDashboard.partials.accounts', compact('users'));
    }
}
