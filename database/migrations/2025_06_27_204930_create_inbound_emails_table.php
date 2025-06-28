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
        Schema::create('inbound_emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_plain')->nullable();
            $table->text('body_html')->nullable();
            $table->text('stripped_text')->nullable();
            $table->text('stripped_html')->nullable();
            $table->text('stripped_signature')->nullable();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->integer('recipient_count')->default(1);
            $table->timestamp('timestamp')->nullable();
            $table->string('token')->nullable();
            $table->string('signature')->nullable();
            $table->string('domain')->nullable();
            $table->json('message_headers')->nullable();
            $table->json('envelope')->nullable();
            $table->integer('attachments_count')->default(0);
            $table->integer('size')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_emails');
    }
};
