<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 32)->index();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('company', 120)->nullable();
            $table->json('services')->nullable();
            $table->string('budget', 120)->nullable();
            $table->string('timeline', 32)->nullable();
            $table->text('message');
            $table->string('locale', 2);
            // A keyed hash, never the address itself: enough to spot repeat senders.
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
