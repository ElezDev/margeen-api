<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->can('reports.view') || $request->user()->can('reports.view-all'),
            403,
            'No tienes permisos para ver reportes.'
        );

        $data = $this->reportService->dashboard(
            $request->user(),
            $request->input('from'),
            $request->input('to'),
        );

        return response()->json(['data' => $data]);
    }
}
