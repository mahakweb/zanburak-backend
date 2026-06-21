<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Services\Certificate\CertificateTemplateService;
use App\Support\Certificate\CertificateConstants;
use App\Support\Certificate\CertificateAssetHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateTemplateController extends Controller
{
    public function __construct(protected CertificateTemplateService $templates) {}

    public function index(Request $request)
    {
        $items = CertificateTemplate::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->search.'%'))
            ->when($request->active === 'yes', fn ($q) => $q->where('is_active', true))
            ->when($request->active === 'no', fn ($q) => $q->where('is_active', false))
            ->withCount('courses', 'certificates')
            ->latest()
            ->paginate((int) $request->input('perPage', 20));

        $items->getCollection()->transform(fn ($t) => $this->formatTemplate($t));

        return response()->json(['message' => 'Success', 'templates' => $items]);
    }

    public function show(CertificateTemplate $template)
    {
        return response()->json([
            'message' => 'Success',
            'template' => $this->formatTemplate($template->loadCount('courses', 'certificates')),
            'placeholders' => CertificateConstants::PLACEHOLDERS,
            'default_layout' => CertificateConstants::DEFAULT_LAYOUT,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $template = $this->templates->create($data, $request->user()->id);

        return response()->json(['message' => 'Template created', 'template' => $this->formatTemplate($template)], 201);
    }

    public function update(Request $request, CertificateTemplate $template)
    {
        $template = $this->templates->update($template, $this->validated($request, $template));

        return response()->json(['message' => 'Template updated', 'template' => $this->formatTemplate($template)]);
    }

    public function destroy(CertificateTemplate $template)
    {
        if ($template->is_default) {
            return response()->json(['message' => 'Cannot delete default template'], 422);
        }

        $template->delete();

        return response()->json(['message' => 'Template deleted']);
    }

    public function duplicate(CertificateTemplate $template, Request $request)
    {
        $copy = $this->templates->duplicate($template, $request->user()->id);

        return response()->json(['message' => 'Template duplicated', 'template' => $copy], 201);
    }

    public function uploadAsset(Request $request, CertificateTemplate $template)
    {
        $request->validate([
            'type' => ['required', 'in:background,logo,signature'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $type = $request->input('type');
        $field = match ($type) {
            'background' => 'background_image',
            'logo' => 'logo_image',
            'signature' => 'signature_image',
        };

        if ($template->{$field}) {
            Storage::disk('public')->delete($template->{$field});
        }

        $path = $request->file('file')->store(
            'certificates/templates/'.$template->id,
            'public'
        );

        $template->update([$field => $path]);

        return response()->json([
            'message' => 'Asset uploaded',
            'template' => $this->formatTemplate($template->fresh()),
            'url' => $this->assetUrl($path),
        ]);
    }

    protected function formatTemplate(CertificateTemplate $template): array
    {
        $data = $template->toArray();

        $data['background_image_url'] = $this->assetUrl($template->background_image);
        $data['logo_image_url'] = $this->assetUrl($template->logo_image);
        $data['signature_image_url'] = $this->assetUrl($template->signature_image);

        return $data;
    }

    protected function assetUrl(?string $path): ?string
    {
        return CertificateAssetHelper::publicUrl($path);
    }

    protected function validated(Request $request, ?CertificateTemplate $template = null): array
    {
        return $request->validate([
            'name' => [$template ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'orientation' => ['nullable', 'in:landscape,portrait'],
            'canvas_width' => ['nullable', 'integer', 'min:400', 'max:5000'],
            'canvas_height' => ['nullable', 'integer', 'min:400', 'max:5000'],
            'layout' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
