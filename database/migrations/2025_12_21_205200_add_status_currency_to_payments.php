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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('amount');
            $table->string('currency')->default('USD')->after('amount');
        });

        // Migrate existing data
        // If completed=1 -> status='completed', else 'pending'
        DB::table('payments')->where('completed', true)->update(['status' => 'completed']);
        DB::table('payments')->where('completed', false)->update(['status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['status', 'currency']);
        });
    }
};
