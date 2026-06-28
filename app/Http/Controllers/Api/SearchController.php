<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'min:1', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'in:course,episode,question'],
            'sort' => ['nullable', 'string', 'in:relevance,newest,popular'],
            'level' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $payload = $this->searchService->search($validated);

        return response()->json($payload);
    }
}
