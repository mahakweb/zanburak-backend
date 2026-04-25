<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cooperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CooperationController extends Controller
{
    /**
     * List requested cooperations with filters, sorting and pagination
     */
    public function index(Request $request)
    {
        $query = Cooperation::query()
            ->select(['id', 'role', 'name', 'email', 'mobile', 'melli_code', 'links', 'description', 'melli_card_image', 'resume', 'samples', 'status', 'created_at']);

        // search by title or description
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('melli_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // filter by role
        if ($request->filled('role') && in_array($request->role, ['teacher', 'support', 'content_creator', 'dev', 'marketing', 'design'])) {
            $query->where('role', $request->role);
        }

        // status
        $status = $request->input('status', 'all');
        $query = match ($status) {
            'approved' => $query->where('status', true),
            'not_approved' => $query->where('status', false),
            default => $query,
        };

        // sort
        $sort = $request->input('sort', 'newest');
        $query = match ($sort) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = (int) $request->input('perPage', 10);
        $cooperations = $query->paginate($perPage);

        $data = $cooperations->map(function ($cooperation) {
            return [
                'id' => $cooperation->id,
                'role' => $cooperation->role,
                'name' => $cooperation->name,
                'email' => $cooperation->email,
                'mobile' => $cooperation->mobile,
                'melli_code' => $cooperation->melli_code,
                'links' => $cooperation->links,
                'description' => $cooperation->description,
                'melli_card_image' => $cooperation->melli_card_image,
                'resume' => $cooperation->resume,
                'samples' => $cooperation->samples,
                'status' => $cooperation->status,
                'created_at' => $cooperation->created_at
            ];
        });

        return response()->json([
            'message' => 'Success',
            'cooperations' => $data,
            'pagination' => [
                'current_page' => $cooperations->currentPage(),
                'last_page' => $cooperations->lastPage(),
                'per_page' => $cooperations->perPage(),
                'total' => $cooperations->total(),
                'from' => $cooperations->firstItem(),
                'to' => $cooperations->lastItem(),
            ],
        ], 200);
    }

    /**
     * Show a single requested cooperation
     */
    public function show(Cooperation $cooperation)
    {
        return response()->json([
            'message' => 'Success',
            'cooperation' => [
                'id' => $cooperation->id,
                'role' => $cooperation->role,
                'name' => $cooperation->name,
                'email' => $cooperation->email,
                'mobile' => $cooperation->mobile,
                'melli_code' => $cooperation->melli_code,
                'links' => $cooperation->links,
                'description' => $cooperation->description,
                'melli_card_image' => $cooperation->melli_card_image,
                'resume' => $cooperation->resume,
                'samples' => $cooperation->samples,
                'status' => $cooperation->status,
                'created_at' => $cooperation->created_at
            ],
        ], 200);
    }

    // /**
    //  * Update a requested project basic fields (admin notes in future)
    //  */
    // public function update(Request $request, Project $project)
    // {
    //     $validated = $request->validate([
    //         'title' => ['sometimes', 'string', 'min:4', 'max:255'],
    //         'type' => ['sometimes', 'in:website,app,websiteAndApp'],
    //         'sample' => ['nullable', 'url'],
    //         'description' => ['nullable', 'string', 'min:5'],
    //         'deadline' => ['sometimes', 'integer', 'min:1'],
    //         'min_price' => ['sometimes'],
    //         'max_price' => ['sometimes'],
    //         'attach_file' => ['nullable', 'string'],
    //     ]);

    //     $project->update($validated);

    //     return response()->json([
    //         'message' => 'Project updated successfully',
    //     ], 200);
    // }

    // /**
    //  * Delete a requested project
    //  */
    public function destroy(Cooperation $cooperation)
    {
        // Remove attached file from storage if exists (handle full URL)
        if ($cooperation->melli_card_image) {
            $details = $this->urlDetails($cooperation->melli_card_image);
            if ($details && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
            }
        }

        if ($cooperation->resume) {
            $details = $this->urlDetails($cooperation->resume);
            if ($details && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
            }
        }

        if ($cooperation->samples) {
            $details = $this->urlDetails($cooperation->samples);
            if ($details && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
            }
        }

        $cooperation->delete();

        return response()->json([
            'message' => 'Cooperation deleted successfully',
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


