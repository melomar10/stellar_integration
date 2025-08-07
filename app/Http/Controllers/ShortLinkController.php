<?php

namespace App\Http\Controllers;

use App\Services\ShortLinkService;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    private $shortLinkService;

    public function __construct()
    {
        $this->shortLinkService = new ShortLinkService();
    }

    /**
     * Crea un enlace corto
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createShortLink(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'ttl' => 'nullable|integer|min:1',
            'path' => 'nullable|string|max:50'
        ]);

        $result = $this->shortLinkService->createShortLink(
            $request->url,
            $request->ttl,
            $request->path
        );

        return response()->json($result);
    }

    /**
     * Obtiene información de un enlace corto
     * 
     * @param string $linkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getShortLinkInfo($linkId)
    {
        $result = $this->shortLinkService->getShortLinkInfo($linkId);
        return response()->json($result);
    }

    /**
     * Actualiza un enlace corto
     * 
     * @param Request $request
     * @param string $linkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateShortLink(Request $request, $linkId)
    {
        $request->validate([
            'originalURL' => 'nullable|url',
            'path' => 'nullable|string|max:50',
            'ttl' => 'nullable|integer|min:1'
        ]);

        $result = $this->shortLinkService->updateShortLink($linkId, $request->all());
        return response()->json($result);
    }

    /**
     * Elimina un enlace corto
     * 
     * @param string $linkId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteShortLink($linkId)
    {
        $result = $this->shortLinkService->deleteShortLink($linkId);
        return response()->json($result);
    }
} 