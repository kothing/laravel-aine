<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddStatusFieldToProjectsTable extends Migration
{
    /**
     * Add the `status` column used to enable/disable a project.
     * Also repairs legacy rows whose status is missing or invalid.
     */
    public function up()
    {
        if (! Schema::hasColumn('projects', 'status')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->tinyInteger('status')->default(1);
            });
        }

        // Anything that is not an explicit 0 (disabled) is treated as enabled.
        // CAST avoids MySQL's implicit numeric coercion swallowing '' values.
        DB::statement("UPDATE projects SET status = 1 WHERE status IS NULL OR CAST(status AS CHAR) NOT IN ('0', '1')");
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
