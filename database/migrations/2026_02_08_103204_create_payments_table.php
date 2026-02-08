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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_method', 50);
            $table->decimal('amount', 10, 3);
            $table->string('status', 50)->default('pending');
            $table->string('transaction_id')->nullable()->unique();
            $table->json('payment_details')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index('order_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
