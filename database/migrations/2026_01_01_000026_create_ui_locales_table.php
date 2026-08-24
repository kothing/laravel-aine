<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUiLocalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * The languages of the admin UI (global). Managed from
     * Settings → Localization. One row is the default (base) language — the
     * language the UI strings are authored in.
     */
    public function up()
    {
        Schema::create('ui_locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('ui_locales');
    }
}
