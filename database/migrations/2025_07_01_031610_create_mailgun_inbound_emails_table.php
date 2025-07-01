<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailgun_inbound_emails', function (Blueprint $table) {
            $table->id();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_plain')->nullable();
            $table->text('body_html')->nullable();
            $table->string('message_id')->nullable();
            $table->unsignedBigInteger('timestamp')->nullable();
            $table->string('token')->nullable();
            $table->string('signature')->nullable();
            $table->string('recipient')->nullable();
            $table->string('sender')->nullable();
            $table->text('stripped_text')->nullable();
            $table->text('stripped_html')->nullable();
            $table->text('stripped_signature')->nullable();
            $table->text('message_headers')->nullable();
            $table->text('content_id_map')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailgun_inbound_emails');
    }
};
