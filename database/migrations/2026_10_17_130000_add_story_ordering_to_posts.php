<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A project can own several case studies. sort_order is the reading order inside that series;
     * the story whose slug equals its project key is the overview and always comes first.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedSmallInteger('sort_order')->default(100)->after('project_key');
            $table->index(['type', 'project_key', 'sort_order'], 'posts_series_index');
        });
        DB::table('posts')->where('type', 'case_study')->whereColumn('slug', 'project_key')->update(['sort_order' => 0]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_series_index');
            $table->dropColumn('sort_order');
        });
    }
};
