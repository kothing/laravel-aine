<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiAllowedDomainsToProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (
            Schema::hasColumn('projects', 'api_allowed_domains')
            || Schema::hasColumn('projects', 'frontend_domains')
        ) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->json('api_allowed_domains')->nullable()->after('public_api');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('projects', 'api_allowed_domains')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('api_allowed_domains');
        });
    }
}
