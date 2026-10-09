<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DestinationController extends Controller
{
    public function index(): View
    {
        return view('admin.destinations.index', [
            'destinations' => Destination::query()->withCount('properties')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.destinations.form', ['destination' => new Destination]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $destination = Destination::create([
            ...collect($data)->except(['name_fr', 'name_en', 'description_fr', 'description_en', 'slug'])->all(),
            'name' => ['fr' => $data['name_fr'], 'en' => $data['name_en'] ?: $data['name_fr']],
            'description' => ['fr' => $data['description_fr'] ?? null, 'en' => $data['description_en'] ?? null],
            'slug' => $data['slug'] ?? false ? Str::slug($data['slug']) : $this->uniqueSlug($data['name_fr']),
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return redirect()->route('admin.destinations.index')
            ->with('status', __('Destination créée : :name.', ['name' => $destination->name->get()]));
    }

    public function edit(Destination $destination): View
    {
        return view('admin.destinations.form', compact('destination'));
    }

    public function update(Request $request, Destination $destination): RedirectResponse
    {
        $data = $this->validated($request, $destination);

        $destination->update([
            ...collect($data)->except(['name_fr', 'name_en', 'description_fr', 'description_en', 'slug'])->all(),
            'name' => ['fr' => $data['name_fr'], 'en' => $data['name_en'] ?: $data['name_fr']],
            'description' => ['fr' => $data['description_fr'] ?? null, 'en' => $data['description_en'] ?? null],
            'slug' => $data['slug'] ?? false ? Str::slug($data['slug']) : $destination->slug,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return redirect()->route('admin.destinations.index')->with('status', __('Destination mise à jour.'));
    }

    /**
     * La contrainte `restrictOnDelete()` sur properties.destination_id protège
     * déjà l'intégrité : impossible de supprimer une destination tant qu'une
     * villa y est rattachée.
     */
    public function destroy(Destination $destination): RedirectResponse
    {
        try {
            $destination->delete();
        } catch (QueryException) {
            return back()->with('error', __(
                'Impossible de supprimer : des villas sont encore rattachées à cette destination.'
            ));
        }

        return redirect()->route('admin.destinations.index')->with('status', __('Destination supprimée.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Destination $destination = null): array
    {
        return $request->validate([
            'name_fr' => ['required', 'string', 'max:120'],
            'name_en' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120',
                Rule::unique('destinations', 'slug')->ignore($destination?->id)],
            'description_fr' => ['nullable', 'string', 'max:4000'],
            'description_en' => ['nullable', 'string', 'max:4000'],
            'region' => ['required', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'position' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:320'],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Destination::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
