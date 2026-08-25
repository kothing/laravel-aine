<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusFieldToProjectsTable extends Migration
{
    /**
     * Add the `status` column used to enable/disable a project.
     */
    public function up()
    {
        if (! Schema::hasColumn('projects', 'status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->tinyInteger('status')->default(1);
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('projects', 'status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
}
