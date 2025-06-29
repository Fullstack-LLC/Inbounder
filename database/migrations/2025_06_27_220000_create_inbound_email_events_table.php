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
        Schema::create('inbound_email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_email_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // opened, clicked, bounced, delivered, etc.
            $table->string('event_id')->nullable(); // Mailgun event ID
            $table->json('event_data')->nullable(); // Additional event data
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('client_type')->nullable(); // webmail, mobile, desktop
            $table->string('client_name')->nullable(); // Gmail, Outlook, etc.
            $table->string('client_os')->nullable();
            $table->string('url')->nullable(); // For click events
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['inbound_email_id', 'event_type']);
            $table->index(['event_type', 'occurred_at']);
            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_email_events');
    }
};
