<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('type')->default('article')->index();
            $table->string('project_key')->nullable()->index();
            $table->json('case_meta')->nullable();
            $table->json('translations')->nullable();
            $table->timestamp('first_published_at')->nullable();
            $table->softDeletes();
        });
        DB::table('posts')->whereNotNull('published_at')->update(['first_published_at' => DB::raw('published_at')]);
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn(['type', 'project_key', 'case_meta', 'translations', 'first_published_at', 'deleted_at']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
