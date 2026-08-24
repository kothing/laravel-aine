<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminStringTables extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores the admin UI translation registry in the database instead of a
     * config file:
     *
     * 1. admin_string_sources — every translatable UI string (the registry).
     *    Populated from the codebase by `scripts/extract-admin-strings.js`
     *    (via the AdminTranslationsSeeder data file).
     *
     * 2. admin_translation_defaults — factory ("out of the box") translations
     *    per locale (e.g. zh). Used when seeding new `translations` rows so
     *    new locales start with sensible values. Editing in the admin panel
     *    never touches this table; it only holds the shipped defaults.
     */
    public function up()
    {
        Schema::create('admin_string_sources', function (Blueprint $table) {
            $table->id();
            $table->text('source');
            $table->timestamps();

            // MySQL/MariaDB cannot index a TEXT column without a prefix
            // length (error 1170); SQLite/PostgreSQL/SQL Server have no such
            // limit. The 191-char prefix fits the classic 767-byte utf8mb4
            // index limit, while the column itself stays full TEXT.
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
                $table->unique([DB::raw('source(191)')], 'admin_string_sources_source_unique');
            } else {
                $table->unique(['source'], 'admin_string_sources_source_unique');
            }
        });

        Schema::create('admin_translation_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->text('source');
            $table->text('value')->nullable();
            $table->timestamps();

            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
                $table->unique(['locale', DB::raw('source(191)')], 'admin_translation_defaults_locale_source_unique');
            } else {
                $table->unique(['locale', 'source'], 'admin_translation_defaults_locale_source_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('admin_translation_defaults');
        Schema::dropIfExists('admin_string_sources');
    }
}
