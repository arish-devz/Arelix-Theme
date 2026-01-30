<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert the admin_theme setting with default value
        DB::table('settings')->insertOrIgnore([
            'key' => 'settings::app:admin_theme',
            'value' => 'default',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the admin_theme setting
        DB::table('settings')->where('key', 'settings::app:admin_theme')->delete();
    }
};
