<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remove the unused "menu-position" field from the CMS template and from
 * every existing project that already has it:
 *   - collection_fields rows (the field definition)
 *   - content_meta rows (values stored on page content)
 *
 * The field only ever displayed a badge on the frontend pages list; it had
 * no navigation/menu logic behind it.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('collection_fields')
            ->where('name', 'menu-position')
            ->delete();

        DB::table('content_meta')
            ->where('field_name', 'menu-position')
            ->delete();
    }

    public function down()
    {
        // No automatic restore: the field definition lives in the CMS
        // template (app/Aine/ProjectTemplates.php) and content values were
        // only demo seed data. Re-seeding recreates projects without it.
    }
};
