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
        Schema::create('mailgun_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // delivered, bounced, complained, etc.
            $table->string('message_id')->nullable();
            $table->string('recipient')->nullable();
            $table->string('domain')->nullable();
            $table->string('ip')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('client_type')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_os')->nullable();
            $table->string('reason')->nullable();
            $table->string('code')->nullable();
            $table->string('error')->nullable();
            $table->string('severity')->nullable();
            $table->json('delivery_status')->nullable();
            $table->json('envelope')->nullable();
            $table->json('flags')->nullable();
            $table->json('tags')->nullable();
            $table->json('campaigns')->nullable();
            $table->json('user_variables')->nullable();
            $table->timestamp('event_timestamp')->nullable();
            $table->json('raw_data')->nullable(); // Store the complete webhook data
            $table->timestamps();

            // Indexes for common queries
            $table->index('event_type');
            $table->index('message_id');
            $table->index('recipient');
            $table->index('event_timestamp');
            $table->index(['event_type', 'event_timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailgun_events');
    }
};
