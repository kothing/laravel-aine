<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIndexesToContentTables extends Migration
{
    /**
     * The public content API queries these tables on every request
     * (project_id + collection_id + locale/published_at/slug, and
     * project_id + collection_id + content_id/field_name on content_meta).
     * Without indexes these are full table scans.
     *
     * @return void
     */
    public function up()
    {
        if ($this->isMysql()) {
            $this->upMysql();
            return;
        }

        // content: most common list queries filter by project + collection
        // and order/filter by published_at or locale.
        Schema::table('content', function (Blueprint $table) {
            $table->index(
                ['project_id', 'collection_id', 'published_at'],
                'idx_content_project_collection_published'
            );
            $table->index(
                ['project_id', 'collection_id', 'locale'],
                'idx_content_project_collection_locale'
            );
        });

        // content_meta: EAV table queried via
        //   WHERE project_id=? AND collection_id=? AND content_id IN (...)
        //   WHERE project_id=? AND collection_id=? AND field_name=? AND value=?
        Schema::table('content_meta', function (Blueprint $table) {
            $table->index(
                ['project_id', 'collection_id', 'content_id'],
                'idx_content_meta_project_collection_content'
            );
            $table->index(
                ['project_id', 'collection_id', 'field_name'],
                'idx_content_meta_project_collection_field'
            );
        });

        // collections: resolved by (project_id, slug) at the start of
        // every content / relation / detail request.
        Schema::table('collections', function (Blueprint $table) {
            $table->index(
                ['project_id', 'slug'],
                'idx_collections_project_slug'
            );
        });
    }

    /**
     * MySQL/MariaDB variant.
     *
     * Two reasons to build the indexes with raw SQL here instead of the
     * schema builder:
     *
     * 1. Compatibility: MySQL < 5.7.7 (and MariaDB with old row formats)
     *    cap index keys at 767 bytes. A utf8mb4 varchar(255) alone is 1020
     *    bytes, so indexing (project_id, collection_id, field_name) would
     *    fail with "Specified key was too long". Prefix indexes (150 chars,
     *    600 bytes) stay well under the limit on every version.
     *
     * 2. Idempotency: each index is only created if it does not exist yet,
     *    so re-running this migration (e.g. after a previously failed
     *    install) cannot fail with "Duplicate key name".
     *
     * @return void
     */
    private function upMysql()
    {
        $indexes = [
            ['content', 'idx_content_project_collection_published', '(`project_id`, `collection_id`, `published_at`)'],
            ['content', 'idx_content_project_collection_locale', '(`project_id`, `collection_id`, `locale`)'],
            ['content_meta', 'idx_content_meta_project_collection_content', '(`project_id`, `collection_id`, `content_id`)'],
            ['content_meta', 'idx_content_meta_project_collection_field', '(`project_id`, `collection_id`, `field_name`(150))'],
            ['collections', 'idx_collections_project_slug', '(`project_id`, `slug`)'],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            if (! $this->indexExists($table, $name)) {
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` {$columns}");
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ($this->isMysql()) {
            $indexes = [
                ['content', 'idx_content_project_collection_published'],
                ['content', 'idx_content_project_collection_locale'],
                ['content_meta', 'idx_content_meta_project_collection_content'],
                ['content_meta', 'idx_content_meta_project_collection_field'],
                ['collections', 'idx_collections_project_slug'],
            ];

            foreach ($indexes as [$table, $name]) {
                if ($this->indexExists($table, $name)) {
                    DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                }
            }
            return;
        }

        Schema::table('content', function (Blueprint $table) {
            $table->dropIndex('idx_content_project_collection_published');
            $table->dropIndex('idx_content_project_collection_locale');
        });

        Schema::table('content_meta', function (Blueprint $table) {
            $table->dropIndex('idx_content_meta_project_collection_content');
            $table->dropIndex('idx_content_meta_project_collection_field');
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropIndex('idx_collections_project_slug');
        });
    }

    /**
     * @return bool
     */
    private function isMysql()
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists($table, $index)
    {
        $db = DB::connection()->getDatabaseName();

        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        );

        return (int) ($row->c ?? 0) > 0;
    }
}
