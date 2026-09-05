<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Preview token for draft content
|--------------------------------------------------------------------------
|
| Lets an admin share one content item with a non-authenticated reviewer
| before it is published. `preview_token` is a random UUID that doubles as
| the preview route parameter; `preview_expires_at` caps the link lifetime.
| NULL token => no preview for that content. Indexed because the public
| preview endpoint resolves a content item by token alone.
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->uuid('preview_token')->nullable();
            $table->timestamp('preview_expires_at')->nullable();
        });

        Schema::table('content', function (Blueprint $table) {
            $table->index('preview_token', 'idx_content_preview_token');
        });
    }

    public function down(): void
    {
        Schema::table('content', function (Blueprint $table) {
            $table->dropIndex('idx_content_preview_token');
            $table->dropColumn(['preview_token', 'preview_expires_at']);
        });
    }
};