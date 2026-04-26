<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `phone` is referenced from UserResource and the storefront's checkout form
 * (OrderPage.vue pre-fills `form.phone = user?.phone || ''`), but the column
 * has been silently missing on the users table — every read returned null.
 * Adding it as a nullable string so both new and existing users can have one
 * without forcing a backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
