<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores UI translations for the admin panel.
     * - locale : target language code (e.g. en, zh)
     * - source : the default-language string (unique per locale)
     * - value  : the translation in `locale` (null = not translated yet)
     */
    public function up()
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 10);
            $table->text('source');
            $table->text('value')->nullable();
            $table->timestamps();

            // MySQL/MariaDB cannot index a TEXT column without a prefix
            // length (error 1170); SQLite/PostgreSQL/SQL Server have no such
            // limit. The 191-char prefix fits the classic 767-byte utf8mb4
            // index limit, while the column itself stays full TEXT.
            if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
                $table->unique(['locale', DB::raw('source(191)')], 'translations_locale_source_unique');
            } else {
                $table->unique(['locale', 'source'], 'translations_locale_source_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('translations');
    }
}
