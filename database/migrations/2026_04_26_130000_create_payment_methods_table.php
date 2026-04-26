<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Saved payment method metadata.
 *
 * IMPORTANT — what this table is NOT:
 * This table stores ONLY display metadata for cards a user has chosen to
 * keep on file (brand, last 4 digits, expiry month/year). It must NEVER
 * store:
 *   - the full primary account number (PAN)
 *   - the CVV / CVC
 *   - the cardholder name as part of the saved record
 *   - magnetic stripe / EMV / PIN data
 *
 * Storing any of those would put this codebase under PCI-DSS scope, which
 * requires a SAQ-D compliance program, network segmentation, key management,
 * pen tests, and a long list of other controls. The right pattern is to
 * integrate a real PCI-compliant processor (Stripe, Adyen, Mollie, Braintree)
 * which gives you a tokenised reference to the card; you store the token
 * (not the PAN) and let them handle the card data. Until that integration
 * is wired up, the storefront's "save this card" UX is honest about its
 * limits — the user still re-enters the card number + CVV at checkout, and
 * we just persist the brand + last4 + expiry so the saved-cards picker can
 * show a meaningful label like "Visa •••• 4242 (12/27)".
 *
 * `(user_id, last_four, exp_month, exp_year)` is unique to keep the saved
 * list clean — re-saving the same card just refreshes the timestamps via
 * updateOrCreate in the controller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Free-text brand string (visa, mastercard, amex, discover, …).
            // We keep it open rather than enum-ing because card networks
            // shift over time; the backend never branches on this.
            $table->string('brand', 32);
            $table->string('last_four', 4);
            $table->unsignedTinyInteger('exp_month');
            $table->unsignedSmallInteger('exp_year');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'last_four', 'exp_month', 'exp_year'], 'payment_methods_unique_card');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
