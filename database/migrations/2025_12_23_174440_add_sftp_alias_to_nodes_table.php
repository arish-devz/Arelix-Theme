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
        if (!Schema::hasColumn('nodes', 'sftp_alias')) {
            Schema::table('nodes', function (Blueprint $table) {
                $table->string('sftp_alias')->nullable()->after('fqdn');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('nodes', 'sftp_alias')) {
            Schema::table('nodes', function (Blueprint $table) {
                $table->dropColumn('sftp_alias');
            });
        }
    }
};
