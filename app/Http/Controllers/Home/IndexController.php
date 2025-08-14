<?php

namespace App\Http\Controllers\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function paths()
    {
        return view('paths');
    }

    public function terms()
    {
        return view('others.terms');
    }

    public function aboutUs()
    {
        return view('others.about-us');
    }

    public function vip()
    {
        return view('others.vip');
    }

    public function contactUs()
    {
        return view('others.contact-us');
    }

    public function sendMessage(Request $request){
        $validData = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required'],
            'message' => ['required'],
        ]);

        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{
            // TODO save data to database
            return response()->json(['status' => 1, 'msg' => 'data has been successfully saved !']);
        }

    }

    public function requestProject()
    {
        return view('request-project');
    }

    public function storeRequestProject(Request $request)
    {
        $attach_file_tmp = '';
        $validData = Validator::make($request->all(), [
            'title' => ['required', 'min:4', 'max:255'],
            'type' => ['required'],
            'min_price' => ['required'],
            'max_price' => ['required'],
            'deadline' => ['required', 'min:1'],
            'attach_file' => ['nullable', 'mimes:jpg,jpeg,png,pdf,txt,rar,zip', 'max:5120'],
            'description' => ['nullable', 'min:5'],
            'sample' => ['nullable', 'url'],
        ]);

        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else {
            $attach_file = null;
            if($request['attach_file']){
                $storagePath  = Storage::disk('static')->url('');
                $path = Storage::disk('static')->put('/project/'.now()->year.'/'.now()->month.'/'.now()->day, $request->attach_file);
                $attach_file = $storagePath.$path;
            }

            $insertData = auth()->user()->project()->create([
                'title' => $request['title'],
                'type' => $request['type'],
                'min_price' => $request['min_price'],
                'max_price' => $request['max_price'],
                'deadline' => $request['deadline'],
                'description' => $request['description'],
                'attach_file' => $attach_file,
                'sample' => $request['sample']
            ]);

            if ($insertData) {
                return response()->json(['status' => 1, 'msg' => 'data has been successfully saved !']);
            }
        }



    }



    public function registerNewsletter(Request $request){
        $validData = $request->validate([
            'email' => ['required', 'email', 'unique:newsletter,email']
        ]);

        $user_ip = $request->ip();
        $user_agent = $request->server('HTTP_USER_AGENT');

        $insertData = DB::table('newsletter')->insert([
            'ip' => $user_ip,
            'user_agent' => $user_agent,
            'email' => $validData['email'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        alert()->success('با تشکر', 'ایمیل شما با موفقیت در خبرنامه ثبت شد.')->showConfirmButton('بسیار خوب');
        return redirect()->back();
    }



    public function editorUpload(Request $request){

        $validData = Validator::make($request->all(), [
            'image' => ['required', 'mimetypes:image/png,image/jpg,image/jpeg', 'max:5120'],
        ], [
            'image.required' => 'ابتدا فایل مورد نظر را انتخاب کنید',
            'image.max' => 'حداکثر سایز تصویر 5 مگابایت میباشد',
            'image.mimetypes' => 'تصویر مورد نظر باید یکی از فرمت‌های: png, jpg, jpeg باشد',
        ]);

        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{
            $storagePath  = Storage::disk('static')->url('');
            $path = Storage::disk('static')->put('/images/editor/'.now()->year.'/'.now()->month.'/'.now()->day, $request->image);
            return response()->json(['status' => 1, 'path' => $storagePath.$path, 'msg' => 'data has been successfully saved !']);
        }



    }
}
