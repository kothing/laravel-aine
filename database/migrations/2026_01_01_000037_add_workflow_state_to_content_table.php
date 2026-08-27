<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->string('workflow_state', 20)->default('draft');
            $table->string('reviewer_comment', 1000)->nullable();
        });
        Schema::table('content', function (Blueprint $table) {
            $table->index('workflow_state', 'idx_content_workflow_state');
        });
    }

    public function down(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->dropIndex('idx_content_workflow_state');
            $table->dropColumn(['workflow_state', 'reviewer_comment']);
        });
    }
};