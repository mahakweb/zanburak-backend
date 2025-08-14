<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index($username){
        $user = $username;
        return view('profile.index', compact('user'));
    }

    public function articles($username){
        $user = $username;
        return view('profile.articles', compact('user'));
    }

    public function discuss($username){
        $user = $username;
        return view('profile.discuss', compact('user'));
    }

    public function answers($username){
        $user = $username;
        return view('profile.answers', compact('user'));
    }
}
