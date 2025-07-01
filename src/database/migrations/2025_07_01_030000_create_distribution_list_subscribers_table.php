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
        Schema::create('distribution_list_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_list_id')->constrained('distribution_lists')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional subscriber metadata
            $table->timestamps();

            $table->unique(['distribution_list_id', 'email']);
            $table->index(['distribution_list_id', 'is_active']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_list_subscribers');
    }
};
