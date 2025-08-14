<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web", "auth" middleware group. Now create something great!
|
*/


Route::get('/', [App\Http\Controllers\Admin\HomeController::class, 'index'])->name('admin-index');

Route::get('/users', [App\Http\Controllers\Admin\User\UserController::class, 'index'])->name('admin-users-list');

Route::get('/user/{user}/show', [App\Http\Controllers\Admin\User\UserController::class, 'show'])->name('admin-show-user');

Route::post('/user/details/update', [App\Http\Controllers\Admin\User\UserController::class, 'update'])->name('admin-update-details');

Route::post('/user/email/update', [App\Http\Controllers\Admin\User\UserController::class, 'updateEmail'])->name('admin-update-email');

Route::post('/user/password/update', [App\Http\Controllers\Admin\User\UserController::class, 'updatePassword'])->name('admin-update-password');

Route::patch('/user/{user}/role/update', [App\Http\Controllers\Admin\User\UserController::class, 'updateRoles'])->name('admin-update-role');

Route::patch('/user/{user}/permission/update', [App\Http\Controllers\Admin\User\UserController::class, 'updatePermissions'])->name('admin-update-permission');

Route::post('/user/comment/update', [App\Http\Controllers\Admin\Comment\CommentController::class, 'update'])->name('admin-update-comment');

Route::post('/user/role/update', [App\Http\Controllers\Admin\Comment\CommentController::class, 'update'])->name('admin-update-comment');






Route::get('/courses', [App\Http\Controllers\Admin\Course\CourseController::class, 'index'])->name('admin-courses-list');

Route::get('/course/{course}/show', [App\Http\Controllers\Admin\Course\CourseController::class, 'show'])->name('admin-show-course');

Route::get('/course/create', [App\Http\Controllers\Admin\Course\CourseController::class, 'create'])->name('admin-create-course');

Route::post('/course/create', [App\Http\Controllers\Admin\Course\CourseController::class, 'store']);

Route::get('/course/{course}/edit', [App\Http\Controllers\Admin\Course\CourseController::class, 'edit'])->name('admin-edit-course');

Route::patch('/course/{course}/edit', [App\Http\Controllers\Admin\Course\CourseController::class, 'update']);

Route::delete('/course/{course}/delete', [App\Http\Controllers\Admin\Course\CourseController::class, 'destroy'])->name('admin-delete-course');

Route::get('/course/{course}/sections', [App\Http\Controllers\Admin\Course\SectionController::class, 'index'])->name('admin-course-sections');

Route::post('/course/{course}/section/create', [App\Http\Controllers\Admin\Course\SectionController::class, 'store'])->name('admin-section-create');

Route::delete('/course/{course}/section/{section}/delete', [App\Http\Controllers\Admin\Course\SectionController::class, 'destroy'])->name('admin-section-delete');

Route::get('/course/{course}/section/{section}/episode/create', [App\Http\Controllers\Admin\Course\EpisodeController::class, 'create'])->name('admin-episode-create');

Route::post('/course/{course}/section/{section}/episode/create', [App\Http\Controllers\Admin\Course\EpisodeController::class, 'store']);

Route::get('/course/{course}/section/{section}/episode/{episode}/edit', [App\Http\Controllers\Admin\Course\EpisodeController::class, 'edit'])->name('admin-episode-edit');

Route::patch('/course/{course}/section/{section}/episode/{episode}/update', [App\Http\Controllers\Admin\Course\EpisodeController::class, 'update'])->name('admin-episode-update');

Route::delete('/course/{course}/section/{section}/episode/{episode}/delete', [App\Http\Controllers\Admin\Course\EpisodeController::class, 'destroy'])->name('admin-episode-delete');

// Route::get('/course/{course}/users', [App\Http\Controllers\Admin\Course\CourseController::class, 'users'])->name('admin-course-users');

// Route::get('/course/{course}/comments', [App\Http\Controllers\Admin\Course\SectionController::class, 'index'])->name('admin-course-sections');

// Route::get('/course/{course}/rating', [App\Http\Controllers\Admin\Course\SectionController::class, 'index'])->name('admin-course-sections');





Route::get('missions/', [App\Http\Controllers\Admin\Mission\MissionController::class, 'index'])->name('admin-mission-list');

Route::get('mission/create', [App\Http\Controllers\Admin\Mission\MissionController::class, 'create'])->name('admin-mission-create');
Route::post('mission/create', [App\Http\Controllers\Admin\Mission\MissionController::class, 'store'])->name('admin-mission-create');







Route::get('/categories/', [App\Http\Controllers\Admin\Course\CategoryController::class, 'index'])->name('admin-categories-list');

Route::get('/category/create', [App\Http\Controllers\Admin\Course\CategoryController::class, 'create'])->name('admin-create-category');

Route::post('/category/create', [App\Http\Controllers\Admin\Course\CategoryController::class, 'store']);

Route::get('/category/{category}/show', [App\Http\Controllers\Admin\Course\CategoryController::class, 'show'])->name('admin-show-category');

Route::delete('/category/{category}/delete', [App\Http\Controllers\Admin\Course\CategoryController::class, 'destroy'])->name('admin-delete-category');

Route::get('/category/{category}/edit', [App\Http\Controllers\Admin\Course\CategoryController::class, 'edit'])->name('admin-edit-category');

Route::patch('/category/{category}/edit', [App\Http\Controllers\Admin\Course\CategoryController::class, 'update']);








Route::get('/statuses/', [App\Http\Controllers\Admin\Course\StatusController::class, 'index'])->name('admin-statuses-list');

Route::get('/status/create', [App\Http\Controllers\Admin\Course\StatusController::class, 'create'])->name('admin-create-status');

Route::post('/status/create', [App\Http\Controllers\Admin\Course\StatusController::class, 'store']);

Route::get('/status/{status}/show', [App\Http\Controllers\Admin\Course\StatusController::class, 'show'])->name('admin-show-status');

Route::delete('/status/{status}/delete', [App\Http\Controllers\Admin\Course\StatusController::class, 'destroy'])->name('admin-delete-status');

Route::get('/status/{status}/edit', [App\Http\Controllers\Admin\Course\StatusController::class, 'edit'])->name('admin-edit-status');

Route::patch('/status/{status}/edit', [App\Http\Controllers\Admin\Course\StatusController::class, 'update']);







Route::get('/levels/', [App\Http\Controllers\Admin\Course\LevelController::class, 'index'])->name('admin-levels-list');

Route::get('/level/create', [App\Http\Controllers\Admin\Course\LevelController::class, 'create'])->name('admin-create-level');

Route::post('/level/create', [App\Http\Controllers\Admin\Course\LevelController::class, 'store']);

Route::get('/level/{level}/show', [App\Http\Controllers\Admin\Course\LevelController::class, 'show'])->name('admin-show-level');

Route::delete('/level/{level}/delete', [App\Http\Controllers\Admin\Course\LevelController::class, 'destroy'])->name('admin-delete-level');

Route::get('/level/{level}/edit', [App\Http\Controllers\Admin\Course\LevelController::class, 'edit'])->name('admin-edit-level');

Route::patch('/level/{level}/edit', [App\Http\Controllers\Admin\Course\LevelController::class, 'update']);








Route::get('/permissions/', [App\Http\Controllers\Admin\Permission\PermissionController::class, 'index'])->name('admin-permissions-list');

Route::post('/permission/create', [App\Http\Controllers\Admin\Permission\PermissionController::class, 'store'])->name('admin-create-permission');

Route::delete('/permission/{permission}/delete', [App\Http\Controllers\Admin\Permission\PermissionController::class, 'destroy'])->name('admin-delete-permission');

Route::get('/permission/{permission}/edit', [App\Http\Controllers\Admin\Permission\PermissionController::class, 'edit'])->name('admin-edit-permission');

Route::patch('/permission/{permission}/edit', [App\Http\Controllers\Admin\Permission\PermissionController::class, 'update']);


Route::delete('/permission/{permission}/detach-user', [App\Http\Controllers\Admin\Permission\PermissionController::class, 'detachUser'])->name('admin-permission-detach-user');




Route::get('/roles/', [App\Http\Controllers\Admin\Role\RoleController::class, 'index'])->name('admin-roles-list');

Route::post('/role/create', [App\Http\Controllers\Admin\Role\RoleController::class, 'store'])->name('admin-create-role');

Route::delete('/role/{role}/delete', [App\Http\Controllers\Admin\Role\RoleController::class, 'destroy'])->name('admin-delete-role');

Route::get('/role/{role}/edit', [App\Http\Controllers\Admin\Role\RoleController::class, 'edit'])->name('admin-edit-role');

Route::patch('/role/{role}/edit', [App\Http\Controllers\Admin\Role\RoleController::class, 'update']);


Route::delete('/role/{role}/detach-user', [App\Http\Controllers\Admin\Role\RoleController::class, 'detachUser'])->name('admin-role-detach-user');



Route::get('/routes/', [App\Http\Controllers\Admin\Route\RouteController::class, 'index'])->name('admin-routes-list');

Route::post('/route/syncPermission', [App\Http\Controllers\Admin\Route\RouteController::class, 'syncPermission'])->name('admin-route-sync-permission');
