<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('mailgun_outbound_emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->index();
            $table->string('recipient')->index();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->string('campaign_id')->nullable()->index();
            $table->foreignId('distribution_list_id')->constrained('distribution_lists')->onDelete('cascade');
            $table->foreignId('email_template_id')->constrained('email_templates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->string('status')->default('sent')->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamp('complained_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['recipient', 'sent_at']);
            $table->index(['campaign_id', 'sent_at']);
            $table->index(['user_id', 'sent_at']);
            $table->index(['status', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mailgun_outbound_emails');
    }
};
