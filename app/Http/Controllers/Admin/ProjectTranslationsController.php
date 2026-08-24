<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CollectionField;
use App\Models\Project;
use App\Models\ProjectTranslation;
use Illuminate\Http\Request;

class ProjectTranslationsController extends Controller
{
    /**
     * The translatable strings of a project: its collection names, field
     * labels, placeholders and descriptions. Written in the project's
     * default language.
     */
    protected function sources(Project $project): array
    {
        $sources = [];

        foreach ($project->collections as $collection) {
            $sources[] = $collection->name;

            foreach ($collection->fields as $field) {
                if (! empty($field->label)) {
                    $sources[] = $field->label;
                }
                if (! empty($field->placeholder)) {
                    $sources[] = $field->placeholder;
                }
                if (! empty($field->description)) {
                    $sources[] = $field->description;
                }
            }
        }

        // De-duplicate while preserving order.
        return array_values(array_unique($sources));
    }

    /**
     * List the project's translatable strings with their translation in the
     * requested locale.
     *
     * GET /admin-api/projects/settings/translations/{id}?locale=zh
     */
    public function index($project_id, Request $request)
    {
        $project = Project::with(['collections.fields'])->findOrFail($project_id);

        $baseLocale = $project->default_locale ?? 'en';
        $locales = $project->locales ? explode(',', $project->locales) : [$baseLocale];
        $locale = $request->get('locale', $baseLocale);

        // Project structure strings + any custom strings added by the user.
        $sources = array_values(array_unique(array_merge(
            $this->sources($project),
            ProjectTranslation::where('project_id', $project->id)
                ->distinct()
                ->pluck('source')
                ->toArray()
        )));

        $values = ProjectTranslation::where('project_id', $project->id)
            ->where('locale', $locale)
            ->pluck('value', 'source');

        $strings = collect($sources)->map(function ($source) use ($values) {
            return [
                'source' => $source,
                'value' => $values[$source] ?? null,
            ];
        })->values();

        return [
            'project_id' => $project->id,
            'locale' => $locale,
            'base_locale' => $baseLocale,
            'locales' => $locales,
            'strings' => $strings,
        ];
    }

    /**
     * Translation dictionary for the UI engine: { source: translated } for a
     * project + locale (non-empty values only).
     *
     * GET /admin-api/projects/settings/translations/{id}/dict?locale=zh
     */
    public function dict($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);
        $locale = $request->get('locale', $project->default_locale ?? 'en');

        $dict = ProjectTranslation::where('project_id', $project->id)
            ->where('locale', $locale)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->pluck('value', 'source')
            ->toArray();

        return [
            'project_id' => $project->id,
            'locale' => $locale,
            'base_locale' => $project->default_locale ?? 'en',
            'dict' => $dict,
        ];
    }

    /**
     * Save the project's translations for a locale (bulk update-or-create).
     *
     * POST /admin-api/projects/settings/translations/{id}/save
     * body: { locale: "zh", items: [ { source, value }, ... ] }
     */
    public function save($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);

        $request->validate([
            'locale' => 'required|string|max:10',
            'items' => 'required|array',
            'items.*.source' => 'required|string|max:1000',
            'items.*.value' => 'nullable|string|max:1000',
        ]);

        $locale = $request->get('locale');

        // --- Phase 1: validate every item first --------------------------------
        foreach ($request->get('items') as $item) {
            $source = $item['source'] ?? '';
            $value = $item['value'] ?? null;

            if ($value !== null && $value !== '') {
                $srcNames = $this->placeholderNames($source);
                $valNames = $this->placeholderNames($value);
                if (count($srcNames) !== count($valNames) || array_diff($srcNames, $valNames) || array_diff($valNames, $srcNames)) {
                    return response([
                        'error' => 'Placeholder mismatch',
                        'source' => $source,
                        'source_placeholders' => count($srcNames),
                        'value_placeholders' => count($valNames),
                    ], 422);
                }
            }
        }

        // --- Phase 2: persist now that every item passed validation ----------
        $saved = 0;
        foreach ($request->get('items') as $item) {
            $source = $item['source'];
            $value = $item['value'] ?? null;

            if ($value !== null && $value !== '') {
                ProjectTranslation::updateOrCreate(
                    ['project_id' => $project->id, 'locale' => $locale, 'source' => $source],
                    ['value' => $value]
                );
                $saved++;
            } else {
                ProjectTranslation::where('project_id', $project->id)
                    ->where('locale', $locale)
                    ->where('source', $source)
                    ->delete();
            }
        }

        return response([
            'success' => true,
            'saved' => $saved,
        ], 200);
    }

    /**
     * Extract placeholder names from a string. Mirrors the global
     * TranslationsController guard: named "{name}" → ["name"], legacy
     * "{{ ... }}" → ["_pos"] (matched by count).
     */
    protected function placeholderNames(?string $str): array
    {
        $str = $str ?? '';
        $names = [];
        $legacyCount = preg_match_all('/\{\{\s*\.\.\.\s*\}\}/', $str);
        for ($i = 0; $i < $legacyCount; $i++) {
            $names[] = '_pos';
        }
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $str, $m)) {
            foreach ($m[1] as $name) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Register a new custom string for the project (row for the default
     * locale + empty rows for every other project locale).
     *
     * POST /admin-api/projects/settings/translations/{id}/add
     * body: { source: "Some Project Label" }
     */
    public function addString($project_id, Request $request)
    {
        $project = Project::findOrFail($project_id);

        $request->validate([
            'source' => 'required|string|max:1000',
        ]);

        $source = trim($request->get('source'));
        if ($source === '') {
            return response(['error' => 'Source string is empty'], 422);
        }

        $baseLocale = $project->default_locale ?? 'en';
        $locales = $project->locales ? explode(',', $project->locales) : [$baseLocale];

        foreach ($locales as $locale) {
            ProjectTranslation::firstOrCreate(
                ['project_id' => $project->id, 'locale' => $locale, 'source' => $source],
                ['value' => $locale === $baseLocale ? $source : null]
            );
        }

        return response(['success' => true, 'source' => $source], 200);
    }
}
