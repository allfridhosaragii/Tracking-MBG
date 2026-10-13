<?php

namespace App\Http\Controllers;

use App\Models\EvidencePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EvidencePhotoController extends Controller
{
    /**
     * Securely stream/view an internal evidence photo from private storage.
     * Enforces strict Policy authorization and tenant scoping.
     */
    public function show(EvidencePhoto $evidencePhoto)
    {
        Gate::authorize('view', $evidencePhoto);

        if (! Storage::disk('local')->exists($evidencePhoto->file_path)) {
            abort(Response::HTTP_NOT_FOUND, 'Berkas bukti foto tidak ditemukan pada media penyimpanan.');
        }

        $fullPath = Storage::disk('local')->path($evidencePhoto->file_path);

        return response()->file($fullPath, [
            'Content-Type' => $evidencePhoto->mime_type ?: 'image/jpeg',
            'Cache-Control' => 'private, no-cache, must-revalidate',
        ]);
    }
}
