<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->date('due_date');
            $table->decimal('emi_amount', 15, 2); // e.g., 3322.66
            $table->decimal('emi_paid_amount', 15, 2)->default(0.00); // e.g., 0.00 or 3322.66
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emis');
    }
};
