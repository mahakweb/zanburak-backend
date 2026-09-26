<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageWebpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Fallback when worker did not (or could not) write a WebP sibling after poster/cover upload.
 */
class MediaWebpController extends Controller
{
    public function ensure(Request $request, ImageWebpService $webp)
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string', 'max:1000'],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $url = (string) $validator->validated()['url'];
        $resolved = $webp->resolveUrl($url);
        if (! $resolved) {
            return response()->json([
                'ok' => false,
                'message' => 'URL is not on a configured media disk',
            ], 422);
        }

        $result = $webp->ensureSibling($resolved['disk'], $resolved['path']);

        return response()->json([
            'ok' => (bool) ($result['ok'] ?? false),
            'skipped' => (bool) ($result['skipped'] ?? false),
            'reason' => $result['reason'] ?? null,
            'webp_path' => $result['webp_path'] ?? null,
            'source' => 'api',
        ], ($result['ok'] ?? false) ? 200 : 422);
    }
}
