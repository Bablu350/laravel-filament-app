<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emis', function (Blueprint $table) {
            $table->decimal('fine', 15, 2)->default(0.00)->after('emi_paid_amount'); // Add fine column
        });
    }

    public function down(): void
    {
        Schema::table('emis', function (Blueprint $table) {
            $table->dropColumn('fine');
        });
    }
};