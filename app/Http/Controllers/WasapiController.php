<?php

namespace App\Http\Controllers;

use App\Models\WasapiSetting;
use App\Models\WasapiTemplateCategory;
use App\Models\WasapiWhatsappLine;
use App\Models\WasapiWhatsappTemplate;
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

    public function templatesIndex()
    {
        $savedTemplates = WasapiWhatsappTemplate::query()
            ->with('category')
            ->orderBy('template_id')
            ->get();
        $categories = WasapiTemplateCategory::query()
            ->with('template')
            ->orderBy('name')
            ->get();
        $fetchedTemplates = session('wasapi_fetched_templates', []);
        $savedWasapiIds = $savedTemplates->pluck('wasapi_id')->all();

        return view('admin.wasapi-templates', [
            'savedTemplates'   => $savedTemplates,
            'categories'       => $categories,
            'fetchedTemplates' => is_array($fetchedTemplates) ? $fetchedTemplates : [],
            'savedWasapiIds'   => $savedWasapiIds,
        ]);
    }

    public function fetchTemplates(WasapiService $wasapi)
    {
        try {
            $templates = $wasapi->fetchWhatsAppTemplates();

            usort($templates, static function (array $a, array $b): int {
                return strcasecmp(
                    (string) ($a['template_id'] ?? ''),
                    (string) ($b['template_id'] ?? '')
                );
            });

            session(['wasapi_fetched_templates' => $templates]);

            return redirect()
                ->route('admin.wasapi.templates.index')
                ->with('success', 'Se obtuvieron '.count($templates).' plantilla(s) desde Wasapi.');
        } catch (\Throwable $e) {
            Log::error('Wasapi fetchTemplates', ['message' => $e->getMessage()]);

            return redirect()
                ->route('admin.wasapi.templates.index')
                ->with('error', 'No se pudieron consultar las plantillas: '.$e->getMessage());
        }
    }

    public function saveTemplates(Request $req, WasapiService $wasapi)
    {
        $data = $req->validate([
            'templates'   => 'nullable|array',
            'templates.*' => 'integer|min:1',
        ]);

        $selectedIds = $data['templates'] ?? [];
        $fetched = session('wasapi_fetched_templates', []);

        if (! is_array($fetched) || $fetched === []) {
            return redirect()
                ->route('admin.wasapi.templates.index')
                ->with('error', 'Consulte las plantillas en Wasapi antes de guardar la selección.');
        }

        try {
            $result = $wasapi->saveSelectedTemplates($fetched, $selectedIds);

            return redirect()
                ->route('admin.wasapi.templates.index')
                ->with('success', "Se guardaron {$result['saved']} plantilla(s) seleccionada(s).");
        } catch (\Throwable $e) {
            Log::error('Wasapi saveTemplates', ['message' => $e->getMessage()]);

            return redirect()
                ->route('admin.wasapi.templates.index')
                ->with('error', 'No se pudieron guardar las plantillas: '.$e->getMessage());
        }
    }

    public function templateCategoriesIndex()
    {
        $categories = WasapiTemplateCategory::query()
            ->with('template')
            ->orderBy('name')
            ->get();

        return view('admin.wasapi-template-categories', [
            'categories' => $categories,
        ]);
    }

    public function storeTemplateCategory(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string|min:2|max:120|unique:wasapi_template_categories,name',
        ]);

        WasapiTemplateCategory::create([
            'name' => trim($data['name']),
        ]);

        return redirect()
            ->route('admin.wasapi.template-categories.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function destroyTemplateCategory(WasapiTemplateCategory $category)
    {
        WasapiWhatsappTemplate::query()
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $category->delete();

        return redirect()
            ->route('admin.wasapi.template-categories.index')
            ->with('success', 'Categoría eliminada.');
    }

    public function assignTemplateCategory(Request $req)
    {
        $data = $req->validate([
            'template_id' => 'required|integer|exists:wasapi_whatsapp_templates,id',
            'category_id' => 'nullable|integer|exists:wasapi_template_categories,id',
        ]);

        $template = WasapiWhatsappTemplate::findOrFail((int) $data['template_id']);
        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;

        if ($categoryId !== null) {
            WasapiWhatsappTemplate::query()
                ->where('category_id', $categoryId)
                ->where('id', '!=', $template->id)
                ->update(['category_id' => null]);
        }

        $template->category_id = $categoryId;
        $template->save();

        return redirect()
            ->route('admin.wasapi.templates.index')
            ->with('success', 'Categoría de uso actualizada para la plantilla «'.$template->template_id.'».');
    }

    public function destroySavedTemplate(WasapiWhatsappTemplate $template)
    {
        $name = $template->template_id;
        $template->delete();

        return redirect()
            ->route('admin.wasapi.templates.index')
            ->with('success', 'Plantilla «'.$name.'» eliminada de la lista guardada.');
    }
}
