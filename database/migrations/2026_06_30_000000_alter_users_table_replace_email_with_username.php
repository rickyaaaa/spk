<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }
        });

        // Set default username for any existing users
        DB::table('users')
            ->whereNull('username')
            ->update(['username' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            
            if (Schema::hasColumn('users', 'email')) {
                // Drop unique key first to satisfy SQLite/MySQL
                try {
                    $table->dropUnique('users_email_unique');
                } catch (\Exception $e) {
                    // Ignore if the unique index is not found
                }
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }
            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->unique()->after('name');
            }
            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
        });
    }
};
