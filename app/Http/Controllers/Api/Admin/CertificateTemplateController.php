<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Services\Certificate\CertificateTemplateService;
use App\Support\Certificate\CertificateConstants;
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

        return response()->json(['message' => 'Success', 'templates' => $items]);
    }

    public function show(CertificateTemplate $template)
    {
        return response()->json([
            'message' => 'Success',
            'template' => $template->loadCount('courses', 'certificates'),
            'placeholders' => CertificateConstants::PLACEHOLDERS,
            'default_layout' => CertificateConstants::DEFAULT_LAYOUT,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $template = $this->templates->create($data, $request->user()->id);

        return response()->json(['message' => 'Template created', 'template' => $template], 201);
    }

    public function update(Request $request, CertificateTemplate $template)
    {
        $template = $this->templates->update($template, $this->validated($request, $template));

        return response()->json(['message' => 'Template updated', 'template' => $template]);
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
            'template' => $template->fresh(),
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    protected function validated(Request $request, ?CertificateTemplate $template = null): array
    {
        return $request->validate([
            'name' => [$template ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'orientation' => ['nullable', 'in:landscape,portrait'],
            'layout' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
        ]);
    }
}
