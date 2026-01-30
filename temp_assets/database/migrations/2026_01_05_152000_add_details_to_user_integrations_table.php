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
        Schema::table('user_integrations', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('provider_email');
            $table->string('provider_avatar')->nullable()->after('provider_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_integrations', function (Blueprint $table) {
            $table->dropColumn(['provider_name', 'provider_avatar']);
        });
    }
};
