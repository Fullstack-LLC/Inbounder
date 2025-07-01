<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('distribution_lists', function (Blueprint $table) {
            $table->string('email_address')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_lists', function (Blueprint $table) {
            $table->dropColumn('email_address');
        });
    }
};
