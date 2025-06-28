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
        Schema::create('inbound_email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_email_id')->constrained('inbound_emails')->onDelete('cascade');
            $table->string('filename');
            $table->string('content_type');
            $table->integer('size');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('disposition')->default('attachment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_email_attachments');
    }
};
