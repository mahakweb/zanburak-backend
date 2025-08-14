<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;


class CertificateController extends Controller
{
    public function certifications(Request $request)
    {
        $filter = $request->input('filter', 'online');
        $user = auth('api')->user();

        $query = match ($filter) {
            'online' => $user->certificates(),
            'tech' => $user->certificates()->where('id', null), // for send null
        };

        $query = $query->orderBy('created_at', 'desc');

        $perPage = $request->input('perPage', 8);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function ($item) use ($filter) {
            if ($filter === 'online') {
                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'issued_at' => $item->issued_at,
                    'user_name' => $item->user_name,
                    'course_title' => $item->course_title,
                    'time_completed' => $item->time_completed,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'user' => [
                        'id' => $item->user->id,
                        'first_name' => $item->user->first_name,
                        'last_name' => $item->user->last_name,
                        'username' => $item->user->username,
                        'profile_pic' => $item->user->profile_pic,
                    ],
                    'course' => [
                        'id' => $item->course->id,
                        'title' => $item->course->title,
                        'english_title' => $item->course->english_title,
                        'slug' => $item->course->slug,
                        'poster' => $item->course->poster,
                    ],
                ];
            } else {
                
            }
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function index($uuid)
    {
        $certificate = Certificate::where('uuid', $uuid)->firstOrFail();
        if ($certificate) {
            $certificateData = $certificate->only('uuid', 'user_name', 'course_title', 'time_completed', 'issued_at');
            $certificateData['user'] = $certificate->user->only('first_name', 'last_name', 'username', 'profile_pic');
            $certificateData['course'] = $certificate->course->only('title', 'english_title', 'slug', 'poster');
            $certificateData['course']['teacher'] = $certificate->course->teacher->only('first_name', 'last_name', 'username', 'profile_pic');
            return response()->json([
                'message' => 'Success',
                'certificate' => $certificateData
            ], 200);
        } else {
            return response()->json([
                'message' => 'Error! Not found',
            ], 404);
        }
    }

}
