<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCompanyRequest;
use App\Http\Requests\Platform\UpdateCompanyRequest;
use App\Http\Requests\Platform\UploadCompanyLogoRequest;
use App\Http\Resources\PlatformCompanyResource;
use App\Models\Company;
use App\Services\PlatformCompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function __construct(
        private readonly PlatformCompanyService $platformCompanyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403, 'No tienes permisos de plataforma.');

        $companies = $this->platformCompanyService->list($request->input('q'));

        return response()->json([
            'data' => PlatformCompanyResource::collection($companies),
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $company = $this->platformCompanyService->create($request->validated());

        return response()->json([
            'message' => 'Empresa creada.',
            'data' => PlatformCompanyResource::make($company),
        ], 201);
    }

    public function show(Request $request, Company $company): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403, 'No tienes permisos de plataforma.');

        $this->platformCompanyService->ensureTenantCompany($company);
        $company->loadCount(['users', 'clients', 'products', 'invoices']);

        return response()->json([
            'data' => PlatformCompanyResource::make($company),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $this->platformCompanyService->ensureTenantCompany($company);

        $company = $this->platformCompanyService->update($company, $request->validated());

        return response()->json([
            'message' => 'Empresa actualizada.',
            'data' => PlatformCompanyResource::make($company),
        ]);
    }

    public function uploadLogo(UploadCompanyLogoRequest $request, Company $company): JsonResponse
    {
        $this->platformCompanyService->ensureTenantCompany($company);

        $company = $this->platformCompanyService->storeLogo($company, $request->file('logo'));

        return response()->json([
            'message' => 'Logo actualizado.',
            'data' => PlatformCompanyResource::make($company),
        ]);
    }

    public function deleteLogo(Request $request, Company $company): JsonResponse
    {
        abort_unless($request->user()?->can('platform.manage'), 403, 'No tienes permisos de plataforma.');

        $this->platformCompanyService->ensureTenantCompany($company);

        $company = $this->platformCompanyService->deleteLogo($company);

        return response()->json([
            'message' => 'Logo eliminado.',
            'data' => PlatformCompanyResource::make($company),
        ]);
    }

    public function logo(Request $request, Company $company): Response
    {
        abort_unless(
            $request->user()?->can('platform.manage') || $request->user()?->company_id === $company->id,
            403,
            'No tienes permisos para ver este logo.'
        );

        $this->platformCompanyService->migrateLegacyLogo($company);

        $url = $this->platformCompanyService->logoUrl($company);

        if ($url) {
            return redirect($url);
        }

        abort(404, 'Logo no encontrado.');
    }
}
