<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConnectedAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('connected_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->json('metadata');
            $table->timestamps();
        });

        Schema::create('connected_account_status_update', function (Blueprint $table) {
            $table->unsignedBigInteger('connected_account_id');
            $table->foreign('connected_account_id')->references('id')->on('connected_accounts');
            $table->unsignedBigInteger('status_update_id');
            $table->foreign('status_update_id')->references('id')->on('status_updates');

            $table->datetime('send_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('connected_account_status_update');
        Schema::dropIfExists('connected_accounts');
    }
}
