<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('client')->nullable();
            $table->string('category')->nullable();
            $table->string('summary', 300)->nullable();
            $table->string('headline')->nullable();
            $table->text('description')->nullable();
            $table->json('highlights')->nullable();
            $table->json('results')->nullable();
            $table->json('technologies')->nullable();
            $table->string('live_url')->nullable();
            $table->string('cover_path')->nullable();
            $table->json('screenshots')->nullable();
            $table->string('seo_description', 300)->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
