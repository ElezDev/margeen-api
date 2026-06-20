<?php

namespace App\Support;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyLogoStorage
{
    public static function directory(Company $company): string
    {
        return "companies/{$company->id}";
    }

    public static function store(Company $company, UploadedFile $file): string
    {
        if ($company->logo_path) {
            PublicStorage::disk()->delete($company->logo_path);
        }

        $extension = Str::lower($file->getClientOriginalExtension() ?: $file->extension() ?: 'png');

        return $file->storeAs(
            self::directory($company),
            "logo.{$extension}",
            PublicStorage::DISK
        );
    }

    public static function delete(Company $company): void
    {
        if (! $company->logo_path) {
            return;
        }

        PublicStorage::disk()->delete($company->logo_path);
    }

    public static function url(Company $company): ?string
    {
        self::ensureOnPublicDisk($company);

        return PublicStorage::url($company->logo_path);
    }

    public static function contents(Company $company): ?string
    {
        self::ensureOnPublicDisk($company);

        return PublicStorage::contents($company->logo_path);
    }

    /**
     * Mueve logos antiguos del disco private al disco public.
     */
    public static function ensureOnPublicDisk(Company $company): void
    {
        if (! $company->logo_path || PublicStorage::disk()->exists($company->logo_path)) {
            return;
        }

        if (! Storage::disk('local')->exists($company->logo_path)) {
            return;
        }

        $contents = Storage::disk('local')->get($company->logo_path);
        PublicStorage::disk()->put($company->logo_path, $contents);
        Storage::disk('local')->delete($company->logo_path);
    }

    public static function mimeType(Company $company): string
    {
        self::ensureOnPublicDisk($company);

        return PublicStorage::mimeType($company->logo_path);
    }
}
