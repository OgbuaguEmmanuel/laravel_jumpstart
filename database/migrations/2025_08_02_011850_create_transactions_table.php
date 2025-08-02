<?php

use App\Models\User;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transactionable_id');
            $table->string('transactionable_type');
            $table->string('reference');
            $table->foreignIdFor(User::class)->nullable()->constrained();
            $table->string('payment_status');
            $table->string('payment_gateway');
            $table->string('payment_method')->nullable();
            $table->string('payment_purpose');
            $table->string('gateway_reference');
            $table->string('gateway_response')->nullable();
            $table->string('currency')->nullable();
            $table->string('amount');
            $table->float('discount')->nullable()->comment('This column is in percentage');
            $table->longText('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
