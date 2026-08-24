<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminStringSource;
use App\Models\Translation;
use Illuminate\Http\Request;

class TranslationsController extends Controller
{
    /**
     * The language the UI strings are authored in. Always English: the
     * registry (admin_string_sources table) and the translation engine's
     * dictionary keys are English source strings. This is independent of the
     * database "base locale" (the default display language, which the admin
     * may change) — switching the default language must NOT make that
     * language read-only in the translation editor.
     */
    public static function sourceLocale(): string
    {
        return 'en';
    }

    /**
     * List every translatable string with its translation in the requested
     * locale (or null when not translated yet).
     *
     * Languages are managed from Settings → Localization; this controller
     * only reads and writes translations.
     *
     * GET /admin-api/translations?locale=zh
     */
    public function index(Request $request)
    {
        $baseLocale = LocalizationController::baseLocale();
        $locales = LocalizationController::locales();
        $locale = $request->get('locale', $baseLocale);

        $this->ensureSources();

        // Distinct source strings across all locales (the registry).
        $sources = Translation::distinct()->pluck('source');

        // Values for the requested locale.
        $values = Translation::where('locale', $locale)
            ->pluck('value', 'source');

        $strings = $sources->map(function ($source) use ($values) {
            return [
                'source' => $source,
                'value' => $values[$source] ?? null,
            ];
        })->values();

        return [
            'locale' => $locale,
            'base_locale' => $baseLocale,
            'source_locale' => self::sourceLocale(),
            'locales' => $locales,
            'strings' => $strings,
        ];
    }

    /**
     * Just the list of UI locales + base locale (lightweight, for the
     * language switcher).
     *
     * GET /admin-api/translations/locales
     */
    public function localesList()
    {
        $this->ensureSources();

        return [
            'base_locale' => LocalizationController::baseLocale(),
            'locales' => LocalizationController::locales(),
        ];
    }

    /**
     * Translation dictionary for the UI engine: { source: translated } for a
     * locale, containing only non-empty translations.
     *
     * GET /admin-api/translations/dict?locale=zh
     */
    public function dict(Request $request)
    {
        $locale = $request->get('locale', LocalizationController::baseLocale());

        $dict = Translation::where('locale', $locale)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->pluck('value', 'source')
            ->toArray();

        return [
            'locale' => $locale,
            'base_locale' => LocalizationController::baseLocale(),
            'dict' => $dict,
        ];
    }

    /**
     * Save translations for a locale (bulk update-or-create).
     *
     * POST /admin-api/translations/save
     * body: { locale: "zh", items: [ { source, value }, ... ] }
     */
    public function save(Request $request)
    {
        $request->validate([
            'locale' => ['required', 'string', 'max:10', 'in:'.implode(',', LocalizationController::locales())],
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
                // Same count, and (for named placeholders) same name set.
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
                Translation::updateOrCreate(
                    ['locale' => $locale, 'source' => $source],
                    ['value' => $value]
                );
                $saved++;
            } else {
                // Empty translation removes the row so the string goes back to
                // the "not translated yet" state.
                Translation::where('locale', $locale)->where('source', $source)->delete();
            }
        }

        return response([
            'success' => true,
            'saved' => $saved,
        ], 200);
    }

    /**
     * Extract placeholder names from a string. Returns an array of names
     * (e.g. ["languageName", "count"]). Used by save() to guard against
     * translations that dropped, duplicated, or renamed a placeholder.
     */
    protected function placeholderNames(?string $str): array
    {
        $str = $str ?? '';
        $names = [];
        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $str, $m)) {
            foreach ($m[1] as $name) {
                $names[] = $name;
            }
        }
        return $names;
    }

    /**
     * Register a new translatable string (row for every UI locale; the base
     * locale's value is the source itself).
     *
     * POST /admin-api/translations/add
     * body: { source: "Some Label" }
     */
    public function addString(Request $request)
    {
        $request->validate([
            'source' => 'required|string|max:1000',
        ]);

        $source = trim($request->get('source'));
        if ($source === '') {
            return response(['error' => 'Source string is empty'], 422);
        }

        // Also register the string in the registry table, so it stays
        // available even if its translation rows get cleared later.
        AdminStringSource::firstOrCreate(['source' => $source]);

        foreach (LocalizationController::locales() as $locale) {
            Translation::firstOrCreate(
                ['locale' => $locale, 'source' => $source],
                ['value' => $locale === self::sourceLocale() ? $source : null]
            );
        }

        return response(['success' => true, 'source' => $source], 200);
    }

    /**
     * Make sure the registry (admin_string_sources table) is present in the
     * translations table for every UI locale.
     */
    protected function ensureSources()
    {
        $sources = AdminStringSource::pluck('source')->toArray();
        if (empty($sources)) {
            return;
        }

        foreach (LocalizationController::locales() as $locale) {
            $existing = Translation::where('locale', $locale)->pluck('source')->flip();
            foreach ($sources as $source) {
                if (! $existing->has($source)) {
                    Translation::firstOrCreate(
                        ['locale' => $locale, 'source' => $source],
                        ['value' => $locale === self::sourceLocale() ? $source : null]
                    );
                }
            }
        }
    }
}
