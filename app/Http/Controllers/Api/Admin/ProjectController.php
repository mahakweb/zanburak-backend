<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * List requested projects with filters, sorting and pagination
     */
    public function index(Request $request)
    {
        $query = Project::query()
            ->with(['user:id,first_name,last_name,username,email,profile_pic'])
            ->select(['id', 'user_id', 'title', 'type', 'sample', 'description', 'deadline', 'min_price', 'max_price', 'attach_file', 'created_at']);

        // search by title or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // filter by type
        if ($request->filled('type') && in_array($request->type, ['website', 'app', 'websiteAndApp'])) {
            $query->where('type', $request->type);
        }

        // filter by user
        if ($request->filled('username')) {
            $username = $request->input('username');
            $query->whereHas('user', function ($q) use ($username) {
                $q->where('username', $username);
            });
        }

        // sort
        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'price_min' => $query->orderByRaw('CAST(min_price as UNSIGNED) asc'),
            'price_max' => $query->orderByRaw('CAST(max_price as UNSIGNED) desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = (int) $request->input('perPage', 12);
        $projects = $query->paginate($perPage);

        $data = $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'type' => $project->type,
                'deadline' => (int) $project->deadline,
                'min_price' => (int) $project->min_price,
                'max_price' => (int) $project->max_price,
                'attach_file' => $project->attach_file,
                'sample' => $project->sample,
                'created_at' => $project->created_at,
                'user' => $project->user ? [
                    'id' => $project->user->id,
                    'first_name' => $project->user->first_name,
                    'last_name' => $project->user->last_name,
                    'username' => $project->user->username,
                    'email' => $project->user->email,
                    'profile_pic' => $project->user->profile_pic,
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'projects' => $data,
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
            ],
        ], 200);
    }

    /**
     * Show a single requested project
     */
    public function show(Project $project)
    {
        $project->load(['user:id,first_name,last_name,username,email,profile_pic']);

        return response()->json([
            'message' => 'Success',
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'type' => $project->type,
                'sample' => $project->sample,
                'description' => $project->description,
                'deadline' => (int) $project->deadline,
                'min_price' => (int) $project->min_price,
                'max_price' => (int) $project->max_price,
                'attach_file' => $project->attach_file,
                'created_at' => $project->created_at,
                'user' => $project->user ? [
                    'id' => $project->user->id,
                    'first_name' => $project->user->first_name,
                    'last_name' => $project->user->last_name,
                    'username' => $project->user->username,
                    'email' => $project->user->email,
                    'profile_pic' => $project->user->profile_pic,
                ] : null,
            ],
        ], 200);
    }

    /**
     * Update a requested project basic fields (admin notes in future)
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'min:4', 'max:255'],
            'type' => ['sometimes', 'in:website,app,websiteAndApp'],
            'sample' => ['nullable', 'url'],
            'description' => ['nullable', 'string', 'min:5'],
            'deadline' => ['sometimes', 'integer', 'min:1'],
            'min_price' => ['sometimes'],
            'max_price' => ['sometimes'],
            'attach_file' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully',
        ], 200);
    }

    /**
     * Delete a requested project
     */
    public function destroy(Project $project)
    {
        // Remove attached file from storage if exists (handle full URL)
        if ($project->attach_file) {
            $details = $this->urlDetails($project->attach_file);
            if ($details && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
            }
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully',
        ], 200);
    }

    private function urlDetails($url)
    {
        if (!Str::is('http*://*', $url)) {
            return null;
        }

        foreach (config('filesystems.disks') as $disk => $config) {
            if (!isset($config['url'])) {
                continue;
            }

            $baseUrl = rtrim($config['url'], '/');
            if (str_starts_with($url, $baseUrl)) {
                $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                return [
                    'domain' => $baseUrl,
                    'disk' => $disk,
                    'path' => $relativePath,
                    'size' => Storage::disk($disk)->exists($relativePath) ? Storage::disk($disk)->size($relativePath) : null,
                    'ext' => pathinfo($relativePath, PATHINFO_EXTENSION),
                    'url' => $url,
                ];
            }
        }

        return null;
    }
}


