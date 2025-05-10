<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerifiedByColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('p_info_verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('doc_verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('address_verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('bank_verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('user_verified_by')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['p_info_verified_by']);
            $table->dropForeign(['doc_verified_by']);
            $table->dropForeign(['address_verified_by']);
            $table->dropForeign(['bank_verified_by']);
            $table->dropForeign(['user_verified_by']);
            $table->dropColumn(['p_info_verified_by', 'doc_verified_by', 'address_verified_by', 'bank_verified_by', 'user_verified_by']);
        });
    }
}
