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
        Schema::table('inbound_emails', function (Blueprint $table) {
            // Add JSON fields for multiple recipients
            $table->json('to_emails')->nullable()->after('to_name');
            $table->json('cc_emails')->nullable()->after('to_emails');
            $table->json('bcc_emails')->nullable()->after('cc_emails');
        });

        // Make the original fields nullable in a separate operation
        Schema::table('inbound_emails', function (Blueprint $table) {
            $table->string('to_email')->nullable()->change();
            $table->string('to_name')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inbound_emails', function (Blueprint $table) {
            $table->dropColumn(['to_emails', 'cc_emails', 'bcc_emails']);
        });

        // Restore original fields to not nullable
        Schema::table('inbound_emails', function (Blueprint $table) {
            $table->string('to_email')->nullable(false)->change();
            $table->string('to_name')->nullable(false)->change();
        });
    }
};
