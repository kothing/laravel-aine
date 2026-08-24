<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrate legacy "{{ ... }}" placeholders to named "{name}" placeholders.
 *
 * The admin UI translation engine switched from positional "{{ ... }}"
 * placeholders to named ones (e.g. "{languageName}"). This migration
 * rewrites the four translation tables in place so existing deployments
 * keep their translations after the upgrade:
 *
 *   - admin_string_sources       (source)
 *   - admin_translation_defaults (source, value)
 *   - translations               (source, value)
 *   - project_translations       (source, value)
 *
 * The mapping below is the authoritative list of source strings that
 * changed. Each entry maps an OLD source (with "{{ ... }}") to its NEW
 * source (with named placeholders). The SAME placeholder substitution is
 * applied to the `value` column, so a translated string like
 * '语言 "{{ ... }}" 已添加。' becomes '语言 "{languageName}" 已添加。'.
 *
 * Strings not listed here (no placeholders, or already named) are left
 * untouched. Idempotent: re-running matches nothing because the old forms
 * are already gone.
 */
return new class extends Migration
{
    /**
     * OLD source => NEW source. The value column is rewritten by replacing
     * the old source's "{{ ... }}" occurrences with the new source's named
     * placeholders, in order of appearance.
     */
    private const MIGRATIONS = [
        '{{ ... }} / {{ ... }} translated'                                              => '{translatedCount} / {stringsLength} translated',
        '{{ ... }} records, {{ ... }} - {{ ... }} showing'                              => '{total} records, {from} - {to} showing',
        '{{ ... }} x {{ ... }}'                                                          => '{selectedFileWidth} x {selectedFileHeight}',
        'Default language is now "{{ ... }}".'                                           => 'Default language is now "{languageName}".',
        'Language "{{ ... }}" added.'                                                    => 'Language "{languageName}" added.',
        'Language "{{ ... }}" removed.'                                                  => 'Language "{languageName}" removed.',
        'Saved {{ ... }} translations.'                                                  => 'Saved {translationsCount} translations.',
        'total {{ ... }} files, {{ ... }} - {{ ... }} showing'                           => 'total {mediaTotal} files, {mediaFrom} - {mediaTo} showing',
        'Translation ({{ ... }})'                                                        => 'Translation ({locale})',
        'you want to delete these {{ ... }} files?'                                      => 'you want to delete these {fileCount} files?',
        'you want to remove language "{{ ... }}" and all of its translations?'           => 'you want to remove language "{languageName}" and all of its translations?',
        'you want to set "{{ ... }}" as the default (source) UI language?'               => 'you want to set "{languageName}" as the default (source) UI language?',
        '/ {{ ... }} / Logs'                                                              => '/ {webhookName} / Logs',
        'All({{ ... }})'                                                                 => 'All({totalCount})',
        'Delete ({{ ... }})'                                                             => 'Delete ({selectedCount})',
        'Description: {{ ... }}'                                                         => 'Description: {description}',
        'Draft({{ ... }})'                                                               => 'Draft({draftCount})',
        'Forms ({{ ... }})'                                                              => 'Forms ({formsCount})',
        'Insert Files ({{ ... }})'                                                       => 'Insert Files ({selectedCount})',
        'Last Used at {{ ... }}'                                                         => 'Last Used at {date}',
        'Project: {{ ... }}'                                                             => 'Project: {projectName}',
        'Published({{ ... }})'                                                           => 'Published({publishedCount})',
        'Select Relation ({{ ... }})'                                                    => 'Select Relation ({relationType})',
        'Select relation ({{ ... }})'                                                    => 'Select relation ({relationType})',
        'Trashed({{ ... }})'                                                             => 'Trashed({trashedCount})',
        'Up to {{ ... }}'                                                                => 'Up to {maxSize}',
        'Version: {{ ... }}'                                                             => 'Version: {version}',
        '{{ ... }} field have invalid value, please correct it before saving.'           => '{errorCount} field have invalid value, please correct it before saving.',
        '{{ ... }} fields have invalid values, please correct them before saving.'       => '{errorCount} fields have invalid values, please correct them before saving.',
        '{{ ... }} items selected'                                                       => '{selectedCount} items selected',
    ];

    public function up(): void
    {
        foreach (self::MIGRATIONS as $oldSource => $newSource) {
            $newNames = $this->namedPlaceholders($newSource);

            // --- admin_string_sources: rewrite `source` ---------------------
            DB::table('admin_string_sources')
                ->where('source', $oldSource)
                ->update(['source' => $newSource]);

            // --- admin_translation_defaults: rewrite `source` + `value` -----
            $this->rewriteSourceAndValue('admin_translation_defaults', $oldSource, $newSource, $newNames);

            // --- translations: rewrite `source` + `value` -------------------
            $this->rewriteSourceAndValue('translations', $oldSource, $newSource, $newNames);

            // --- project_translations: rewrite `source` + `value` ----------
            $this->rewriteSourceAndValue('project_translations', $oldSource, $newSource, $newNames);
        }
    }

    public function down(): void
    {
        // Reverse mapping: NEW => OLD. The value column can't be perfectly
        // reversed (named → positional loses the name), so we only restore
        // the `source` column and leave `value` as-is. This is acceptable
        // because rolling back this migration is an emergency operation.
        foreach (self::MIGRATIONS as $oldSource => $newSource) {
            foreach (['admin_string_sources'] as $table) {
                DB::table($table)->where('source', $newSource)->update(['source' => $oldSource]);
            }
            foreach (['admin_translation_defaults', 'translations', 'project_translations'] as $table) {
                DB::table($table)->where('source', $newSource)->update(['source' => $oldSource]);
            }
        }
    }

    /**
     * Rewrite the `source` column (exact match) and, for rows that matched,
     * also rewrite the `value` column by replacing "{{ ... }}" occurrences
     * with the named placeholders in order.
     */
    private function rewriteSourceAndValue(string $table, string $oldSource, string $newSource, array $newNames): void
    {
        $rows = DB::table($table)->where('source', $oldSource)->get(['id', 'value']);
        foreach ($rows as $row) {
            $newValue = $this->rewriteValue($row->value, $newNames);
            DB::table($table)->where('id', $row->id)->update([
                'source' => $newSource,
                'value'  => $newValue,
            ]);
        }
    }

    /**
     * Replace each "{{ ... }}" in a value with the next named placeholder,
     * in order of appearance. If the value has a different number of
     * "{{ ... }}" than the source, leave it untouched (can't map safely).
     */
    private function rewriteValue(?string $value, array $newNames): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        if (strpos($value, '{{') === false) {
            return $value; // already named or no placeholders
        }
        $legacyCount = preg_match_all('/\{\{\s*\.\.\.\s*\}\}/', $value);
        if ($legacyCount !== count($newNames)) {
            return $value; // mismatch in count — leave as-is, don't corrupt
        }
        $i = 0;
        return preg_replace_callback('/\{\{\s*\.\.\.\s*\}\}/', function () use (&$i, $newNames) {
            return '{' . ($newNames[$i++] ?? 'value') . '}';
        }, $value);
    }

    /**
     * Extract the named placeholder names from a NEW source string, in
     * order of appearance. e.g. '{total} records, {from} - {to}' →
     * ['total', 'from', 'to'].
     */
    private function namedPlaceholders(string $str): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $str, $m);
        return $m[1] ?? [];
    }
};