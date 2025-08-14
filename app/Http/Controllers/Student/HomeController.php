<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Payment;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(){
        return view('student.dashboard');
    }

    public function payments(){
        $payments = auth()->user()->payments()->orderBy('id', 'desc')->get();
//        return $payments;
        return view('student.payments', compact('payments'));
    }

    public function courses(){
        $courses = auth()->user()->courses()->get();
        return view('student.courses', compact('courses'));
    }

    public function discounts(){
        $discounts = Discount::orderBy('id', 'desc')->get();
        return view('student.discounts', compact('discounts'));
    }
    public function followed(){
        $userFollowings = auth()->user()->followings()->get();
        return view('student.followed', compact('userFollowings'));
    }

    public function notifications(){
        $unreadNotifications = auth()->user()->unreadNotifications()->orderBy('created_at', 'DESC')->get();
        $unreadNotifications->markAsRead();
        return view('student.notifications', compact('unreadNotifications'));
    }

    public function readNotifications(){
        $readNotifications = auth()->user()->readNotifications()->orderBy('created_at', 'DESC')->paginate(20);
        return view('student.read-notifications', compact('readNotifications'));
    }

    public function questions(){
        $questions = auth()->user()->questions()->orderBy('created_at', 'desc')->get();
        $answers = auth()->user()->answers()->orderBy('created_at', 'desc')->get();
        return view('student.questions', compact('questions', 'answers'));
    }
}
