<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('projects', 'slug')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('slug', 60)->nullable()->unique()->after('name');
            });

            DB::table('projects')->whereNull('slug')->orderBy('id')->chunk(100, function ($projects) {
                foreach ($projects as $project) {
                    $slug = Str::slug($project->name);
                    $originalSlug = $slug;
                    $counter = 1;
                    
                    while (DB::table('projects')->where('slug', $slug)->exists()) {
                        $slug = $originalSlug . '-' . $counter;
                        $counter++;
                    }
                    
                    DB::table('projects')->where('id', $project->id)->update(['slug' => $slug]);
                }
            });

            Schema::table('projects', function (Blueprint $table) {
                $table->string('slug', 60)->nullable(false)->change();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('projects', 'slug')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};