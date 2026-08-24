<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminStringSource;
use App\Models\AdminTranslationDefault;
use App\Models\Translation;
use App\Models\UiLocale;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    /**
     * The default (base) UI language — the language the UI strings are
     * authored in.
     */
    public static function baseLocale(): string
    {
        $default = UiLocale::where('is_default', true)->first();

        return $default ? $default->code : 'en';
    }

    /**
     * All UI locales configured for the admin panel.
     */
    public static function locales(): array
    {
        return UiLocale::orderBy('code')->pluck('code')->toArray();
    }

    /**
     * List the admin UI languages.
     *
     * GET /admin-api/localization
     */
    public function index()
    {
        return [
            'base_locale' => self::baseLocale(),
            'locales' => UiLocale::orderBy('code')->get(),
        ];
    }

    /**
     * Add a UI language. Creates an empty translation row for every known
     * source string in that language.
     *
     * POST /admin-api/localization
     * body: { code: "fr", name: "Français" }
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2,3}([_-][A-Za-z]{2,4})?$/', 'unique:ui_locales,code'],
            'name' => 'nullable|string|max:100',
        ]);

        $locale = $request->get('code');

        $uiLocale = UiLocale::create([
            'code' => $locale,
            'name' => $request->get('name') ?: null,
            'is_default' => UiLocale::count() === 0,
        ]);

        // Make sure every known source string has a row for the new language.
        // The registry lives in the admin_string_sources table (seeded from
        // database/seeders/data/admin_strings.php); strings the admin added by
        // hand live in the translations table.
        $sources = AdminStringSource::pluck('source')
            ->merge(Translation::distinct()->pluck('source'))
            ->unique()
            ->values();

        // Pre-fill factory default translations for this locale, if any.
        $defaults = AdminTranslationDefault::where('locale', $locale)->pluck('value', 'source');

        foreach ($sources as $source) {
            Translation::firstOrCreate(
                ['locale' => $locale, 'source' => $source],
                ['value' => $defaults[$source] ?? null]
            );
        }

        return response([
            'success' => true,
            'locale' => $uiLocale,
            'base_locale' => self::baseLocale(),
            'locales' => self::locales(),
        ], 200);
    }

    /**
     * Set the default (base) UI language.
     *
     * POST /admin-api/localization/set-default
     * body: { code: "en" }
     */
    public function setDefault(Request $request)
    {
        $request->validate([
            'code' => 'required|string|exists:ui_locales,code',
        ]);

        UiLocale::query()->update(['is_default' => false]);
        UiLocale::where('code', $request->get('code'))->update(['is_default' => true]);

        return response([
            'success' => true,
            'base_locale' => self::baseLocale(),
            'locales' => self::locales(),
        ], 200);
    }

    /**
     * Remove a UI language and all of its translations. The default (base)
     * language can never be removed.
     *
     * DELETE /admin-api/localization/{code}
     */
    public function destroy($code)
    {
        if ($code === self::baseLocale()) {
            return response(['error' => 'The default language cannot be removed.'], 422);
        }

        UiLocale::where('code', $code)->delete();
        Translation::where('locale', $code)->delete();

        return response([
            'success' => true,
            'locale' => $code,
            'base_locale' => self::baseLocale(),
            'locales' => self::locales(),
        ], 200);
    }
}
