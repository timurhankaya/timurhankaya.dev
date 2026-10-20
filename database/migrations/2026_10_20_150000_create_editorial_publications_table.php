<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editorial_publications', function (Blueprint $table) {
            $table->id();
            $table->string('topic_key', 150)->unique();
            $table->string('bundle_id', 64)->unique();
            $table->string('source_fingerprint', 64)->unique();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('editorial_publication_lock', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->timestamp('updated_at')->nullable();
        });
        DB::table('editorial_publication_lock')->insert(['id' => 1, 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('editorial_publications');
        Schema::dropIfExists('editorial_publication_lock');
    }
};
