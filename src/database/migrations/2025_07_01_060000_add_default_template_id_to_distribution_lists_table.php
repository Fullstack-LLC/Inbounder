<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('distribution_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('default_template_id')->nullable()->after('email_address');
            $table->foreign('default_template_id')->references('id')->on('email_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_lists', function (Blueprint $table) {
            $table->dropForeign(['default_template_id']);
            $table->dropColumn('default_template_id');
        });
    }
};
