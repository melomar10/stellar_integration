<?php

namespace App\Http\Controllers;

use App\Models\WasapiSetting;
use App\Models\WasapiWhatsappLine;
use App\Services\WasapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WasapiController extends Controller
{
    public function index()
    {
        $settings = WasapiSetting::instance();
        $lines = WasapiWhatsappLine::query()->orderByDesc('is_default')->orderBy('display_name')->get();

        return view('admin.wasapi', [
            'settings'     => $settings,
            'lines'        => $lines,
            'hasToken'     => $settings->hasToken(),
            'maskedToken'  => $settings->maskedToken(),
            'usingEnvToken'=> ! $settings->hasToken() && trim((string) config('services.wasapi.api_key', '')) !== '',
        ]);
    }

    public function saveCredentials(Request $req)
    {
        $data = $req->validate([
            'api_token' => 'required|string|min:10',
            'base_uri'  => 'nullable|url|max:255',
        ]);

        $settings = WasapiSetting::instance();
        $settings->api_token = trim($data['api_token']);
        if (! empty($data['base_uri'])) {
            $settings->base_uri = rtrim(trim($data['base_uri']), '/');
        }
        $settings->save();

        return redirect()
            ->route('admin.wasapi.index')
            ->with('success', 'Credenciales de Wasapi guardadas correctamente.');
    }

    public function syncLines(WasapiService $wasapi, Request $req)
    {
        try {
            $token = null;
            if ($req->filled('api_token')) {
                $req->validate(['api_token' => 'required|string|min:10']);
                $token = trim((string) $req->input('api_token'));
            }

            $result = $wasapi->syncWhatsappLines($token);

            return redirect()
                ->route('admin.wasapi.index')
                ->with('success', "Se sincronizaron {$result['synced']} línea(s) de WhatsApp.");
        } catch (\Throwable $e) {
            Log::error('Wasapi syncLines', ['message' => $e->getMessage()]);

            return redirect()
                ->route('admin.wasapi.index')
                ->with('error', 'No se pudieron obtener las líneas: '.$e->getMessage());
        }
    }

    public function setDefaultLine(Request $req)
    {
        $data = $req->validate([
            'line_id' => 'required|integer|exists:wasapi_whatsapp_lines,id',
        ]);

        WasapiWhatsappLine::setDefault((int) $data['line_id']);

        return redirect()
            ->route('admin.wasapi.index')
            ->with('success', 'Línea predeterminada actualizada.');
    }

    public function sendTestMessage(Request $req, WasapiService $wasapi)
    {
        $data = $req->validate([
            'wa_id'   => 'required|string|min:7|max:20',
            'message' => 'required|string|min:1|max:4096',
            'from_id' => 'nullable|integer|min:1',
        ]);

        try {
            $fromId = isset($data['from_id']) ? (int) $data['from_id'] : null;
            $response = $wasapi->sendTextMessage($data['wa_id'], $data['message'], $fromId);

            return redirect()
                ->route('admin.wasapi.index')
                ->with('success', 'Mensaje enviado. ID Wasapi: '.($response['data']['id'] ?? 'OK'));
        } catch (\Throwable $e) {
            Log::error('Wasapi sendTestMessage', ['message' => $e->getMessage()]);

            return redirect()
                ->route('admin.wasapi.index')
                ->with('error', 'Error al enviar mensaje: '.$e->getMessage());
        }
    }
}
