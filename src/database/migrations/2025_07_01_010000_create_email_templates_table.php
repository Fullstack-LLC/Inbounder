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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('html_content');
            $table->text('text_content')->nullable();
            $table->json('variables')->nullable(); // Template variables like {{name}}, {{email}}, etc.
            $table->json('metadata')->nullable(); // Additional template metadata
            $table->boolean('is_active')->default(true);
            $table->string('category')->nullable(); // e.g., 'newsletter', 'notification', 'marketing'
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
