<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        $contacts = Contact::where('user_id', $user->id)->with(['contactUser' => function($query) { $query->select('id', 'first_name', 'last_name', 'last_seen', 'username', 'profile_pic'); }])->get();
        return response()->json(['contacts' => $contacts], 200);
    }
}
