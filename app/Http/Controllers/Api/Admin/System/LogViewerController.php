<?php

namespace App\Http\Controllers\Api\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\LaravelLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogViewerController extends Controller
{
    public function __construct(
        private readonly LaravelLogService $logService
    ) {}

    public function files(): JsonResponse
    {
        return response()->json([
            'message' => 'Success',
            'files' => $this->logService->listFiles(),
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        try {
            $data = $this->logService->analyze(
                $request->input('file', 'laravel.log'),
                $request->input('from'),
                $request->input('to')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $data,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:20',
            'search' => 'nullable|string|max:500',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        try {
            $result = $this->logService->listEntries($request->all());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Success',
            ...$result,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => 'nullable|string|max:255',
        ]);

        try {
            $entry = $this->logService->getEntry(
                $request->input('file', 'laravel.log'),
                $id
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (!$entry) {
            return response()->json(['message' => 'لاگ پیدا نشد.'], 404);
        }

        return response()->json([
            'message' => 'Success',
            'data' => $entry,
        ]);
    }

    public function download(Request $request): BinaryFileResponse|JsonResponse
    {
        $request->validate([
            'file' => 'required|string|max:255',
        ]);

        $path = $this->logService->downloadPath($request->input('file'));
        if (!$path) {
            return response()->json(['message' => 'فایل لاگ معتبر نیست.'], 422);
        }

        return response()->download($path, basename($path));
    }

    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|string|max:255',
        ]);

        try {
            $this->logService->clearFile($request->input('file'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'فایل لاگ با موفقیت پاک شد.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|string|max:255',
        ]);

        try {
            $this->logService->deleteFile($request->input('file'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'فایل لاگ حذف شد.']);
    }
}
