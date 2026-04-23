<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // aki csinálta (ha felhasználót törölnek, maradjon a log)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // cachelt admin név hogy ne tűnjön el, ha a usert törlik
            $table->string('user_name')->nullable();

            // created | updated | deleted | bulk_deleted | bulk_updated | status_changed | payment_changed
            $table->string('action', 32);

            // 'Product' | 'Order' | 'Coupon' stb.
            $table->string('model_type', 64);
            $table->unsignedBigInteger('model_id')->nullable();

            // rövid, ember által olvasható leírás (UI-hoz)
            $table->string('description', 500)->nullable();

            // részletes változások (old/new) JSON-ban
            $table->json('changes')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('model_type');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
