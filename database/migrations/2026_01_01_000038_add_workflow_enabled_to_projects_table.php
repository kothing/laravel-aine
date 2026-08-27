<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('projects', 'workflow_enabled')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->boolean('workflow_enabled')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('projects', 'workflow_enabled')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('workflow_enabled');
            });
        }
    }
};