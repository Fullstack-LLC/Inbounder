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
            $table->unsignedBigInteger('distribution_list_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional subscriber metadata
            $table->timestamps();

            $table->foreign('distribution_list_id')->references('id')->on('distribution_lists')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['distribution_list_id', 'user_id'], 'dl_subs_list_user_unique');
            $table->unique(['distribution_list_id', 'email'], 'dl_subs_list_email_unique');
            $table->index(['distribution_list_id', 'is_active'], 'dl_subscribers_list_active_idx');
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
