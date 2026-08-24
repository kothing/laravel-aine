<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProjectTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-project translations of the project's own strings (collection
     * names, field labels, placeholders, descriptions). Independent from the
     * global admin UI translations.
     *
     * - project_id : the project this translation belongs to
     * - locale     : target language code (one of the project's locales)
     * - source     : the string in the project's default language
     * - value      : the translation in `locale` (null = not translated yet)
     */
    public function up()
    {
        Schema::create('project_translations', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->string('locale', 10);
            $table->text('source');
            $table->text('value')->nullable();
            $table->timestamps();

            // MySQL/MariaDB cannot index a TEXT column without a prefix
            // length (error 1170); SQLite/PostgreSQL/SQL Server have no such
            // limit. The 191-char prefix fits the classic 767-byte utf8mb4
            // index limit, while the column itself stays full TEXT.
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
                $table->unique(['project_id', 'locale', DB::raw('source(191)')], 'project_translations_project_id_locale_source_unique');
            } else {
                $table->unique(['project_id', 'locale', 'source'], 'project_translations_project_id_locale_source_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('project_translations');
    }
}
