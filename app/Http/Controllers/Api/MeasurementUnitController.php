<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeasurementUnitResource;
use App\Services\MeasurementUnitService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeasurementUnitController extends Controller
{
    public function __construct(
        private readonly MeasurementUnitService $measurementUnitService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $companyId = Tenant::companyId($request);

        return MeasurementUnitResource::collection(
            $this->measurementUnitService->listForCompany($companyId)
        );
    }
}
