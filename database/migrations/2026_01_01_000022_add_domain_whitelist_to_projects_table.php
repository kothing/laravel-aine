<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDomainWhitelistToProjectsTable extends Migration
{
    public function up()
    {
        if (
            Schema::hasColumn('projects', 'domain_whitelist')
            || Schema::hasColumn('projects', 'api_allowed_domains')
            || Schema::hasColumn('projects', 'frontend_domains')
        ) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->json('domain_whitelist')->nullable()->after('public_api');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('projects', 'domain_whitelist')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('domain_whitelist');
        });
    }
}