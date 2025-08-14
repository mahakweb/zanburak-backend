<?php

namespace App\Http\Controllers\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.apps.categories.list');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.apps.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon' => ['required'],
            'status' => ['required', 'in:0,1'],
            'parent_id' => ['required'],
            'tags' => ['required'],
            'title' => ['required', 'min:3', 'max:255', 'unique:categories,title'],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', 'unique:categories,english_title'],
            'description' => ['nullable'],
        ]);
        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();

            if($validData['parent_id'] == 0) $validData['parent_id'] = null;

            $category = Category::create($validData);

            $request['tags'] = json_decode($request['tags'], true);


            foreach ($request['tags'] as $key => $value) {
                $category->tag($value);
            }



            return response()->json(['status' => 1, 'msg' => 'ok']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return view('admin.apps.categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        return view('admin.apps.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        $validator = Validator::make($request->all(), [
            'icon' => ['required'],
            'status' => ['required', 'in:0,1'],
            'parent_id' => ['required'],
            'tags' => ['required'],
            'title' => ['required', 'min:3', 'max:255', Rule::unique('categories')->ignore($category->id),],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', Rule::unique('categories')->ignore($category->id)],
            'description' => ['nullable'],
        ]);
        if(!$validator->passes()){
            return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
        }else{
            $validData = $validator->validated();

            if($validData['parent_id'] == 0) $validData['parent_id'] = null;

            $category->update($validData);

            $request['tags'] = json_decode($request['tags'], true);


            $category->detag();

            foreach ($request['tags'] as $key => $value) {
                $category->tag($value);
            }



            return response()->json(['status' => 1, 'msg' => 'ok']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Category $category)
    {

        $delete = $category->delete();
        if($delete){
            return response()->json(['status' => 1, 'msg' => 'successfully']);
        }

    }
}
