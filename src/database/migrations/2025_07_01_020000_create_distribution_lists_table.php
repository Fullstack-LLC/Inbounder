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
        Schema::create('distribution_lists', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            /**
             * The category of the list. Used for filtering and searching.
             * e.g., 'newsletter', 'marketing', 'support'
             */
            $table->string('category')->nullable();

            $table->string('inbound_email_address')->nullable();
            $table->string('outbound_email_address')->nullable();

            /**
             * Read-only: Only authenticated users can post to this list. Used for mass announcements, newsletters.
             * Members: Subscribed members of the list can communicate with each other.
             * Everyone: Everyone can post to this list. We recommend to turn spam filtering on when using this mode.
             */
            $table->enum('access_level', ['read-only', 'members', 'everyone'])->default('read-only');

            /**
             * List: Reply-to address is the list email address.
             * Sender: Reply-to address is the sender email address.
             */
            $table->enum('list_type', ['list', 'sender'])->default('list');

            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->onDelete('set null');

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribution_lists');
    }
};
