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
        Schema::create('daily_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->unsignedTinyInteger('hour')->index();
            $table->string('page_url', 500)->nullable();
            $table->enum('event_type', ['pageview', 'click', 'time_spent', 'add_to_cart', 'remove_from_cart', 'checkout']);
            $table->unsignedInteger('event_count')->default(0);
            $table->unsignedInteger('unique_sessions')->default(0);
            $table->float('avg_duration_sec')->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->unsignedInteger('bounce_count')->default(0);
            $table->unsignedInteger('new_visitors')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_aggregates');
    }
};
