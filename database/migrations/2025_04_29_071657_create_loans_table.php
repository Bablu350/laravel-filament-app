<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('loan_amount', 15, 2); // e.g., 100000.00
            $table->date('loan_start_date');
            $table->integer('loan_age'); // e.g., in months
            $table->enum('emi_type', ['weekly', 'bi-weekly', 'monthly']);
            $table->decimal('emi_amount', 15, 2); // e.g., 5000.00
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};