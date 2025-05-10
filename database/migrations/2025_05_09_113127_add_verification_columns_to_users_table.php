<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVerificationColumnsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('p_info_verified')->default(0);
            $table->boolean('doc_verified')->default(0);
            $table->boolean('address_verified')->default(0);
            $table->boolean('bank_verified')->default(0);
            $table->boolean('user_verified')->default(0);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'p_info_verified',
                'doc_verified',
                'address_verified',
                'bank_verified',
                'user_verified',
            ]);
        });
    }
}
