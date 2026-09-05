<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Draft branching support for content
|--------------------------------------------------------------------------
|
| A content row with draft_parent_id IS NULL is a "main" row that may be
| published or draft.  A content row with draft_parent_id pointing to
| another content row is a "draft branch" — an independent working copy
| that can be edited without affecting the published (main) version.
|
| When the draft branch is published, its meta replaces the main row's
| meta and the draft row is deleted.  Public APIs are unaffected because
| draft branches always have published_at = NULL.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->unsignedBigInteger('draft_parent_id')->nullable()->after('id');
        });

        Schema::table('content', function (Blueprint $table) {
            $table->index('draft_parent_id', 'idx_content_draft_parent');
        });
    }

    public function down(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->dropIndex('idx_content_draft_parent');
            $table->dropColumn('draft_parent_id');
        });
    }
};