<?php

namespace App\Http\Controllers\Api\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\SystemResourceService;
use Illuminate\Http\JsonResponse;

class SystemResourceController extends Controller
{
    public function __construct(
        private readonly SystemResourceService $systemResourceService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Success',
            'data' => $this->systemResourceService->snapshot(),
        ]);
    }

    public function history(): JsonResponse
    {
        return response()->json([
            'message' => 'Success',
            'history' => $this->systemResourceService->getHistory(),
            'charts' => $this->systemResourceService->getHistoryCharts(),
        ]);
    }
}
