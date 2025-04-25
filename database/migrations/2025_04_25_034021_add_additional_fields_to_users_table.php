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
        Schema::table('users', function (Blueprint $table) {
            // Required fields
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('aadhaar_number', 12)->unique()->nullable();
            $table->string('aadhaar_card')->nullable(); // Stores file path
            $table->string('pincode', 6)->nullable();
            $table->text('address')->nullable();
            $table->string('bank_account_number', 20)->nullable();
            $table->string('ifsc_code', 11)->nullable();

            // Non-required fields
            $table->string('pan_number', 10)->nullable()->unique();
            $table->string('pan_card')->nullable(); // Stores file path
            $table->string('voter_id_number', 20)->nullable()->unique();
            $table->string('voter_id_card')->nullable(); // Stores file path
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'date_of_birth',
                'aadhaar_number',
                'aadhaar_card',
                'pincode',
                'address',
                'bank_account_number',
                'ifsc_code',
                'pan_number',
                'pan_card',
                'voter_id_number',
                'voter_id_card',
            ]);
        });
    }
};
