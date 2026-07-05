<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }

            if (Schema::hasColumn('users', 'email')) {
                // SQLite (and some other drivers) refuse to drop a column that still
                // has a unique index attached to it, so the index must go first.
                $indexes = array_column(Schema::getIndexes('users'), 'name');

                if (in_array('users_email_unique', $indexes, true)) {
                    $table->dropUnique('users_email_unique');
                }

                $table->dropColumn('email');
            }

            if (! Schema::hasColumn('users', 'nik')) {
                $table->string('nik', 16)->unique()->after('name');
            }

            if (! Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('nik');
            }

            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('checker')->after('whatsapp');
            }

            if (! Schema::hasColumn('users', 'profile_pic')) {
                $table->string('profile_pic')->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'pin')) {
                $table->string('pin')->nullable()->after('password');
            }
        });

        // Drop the old profile photo column separately if it exists alongside the new one
        // (kept as a plain drop rather than renameColumn to avoid a doctrine/dbal dependency).
        if (Schema::hasColumn('users', 'profile_photo_path')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('profile_photo_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->dropColumn(['nik', 'whatsapp', 'role', 'profile_pic', 'pin']);
        });
    }
};
