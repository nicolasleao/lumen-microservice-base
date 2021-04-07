<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->string('id');
            $table->string('store_id');
            $table->string('user_id')->nullable();
            $table->string('database_schema')->nullable();
            $table->string('database_host')->nullable();
            $table->string('database_port')->nullable();
            $table->string('database_uid')->nullable();
            $table->string('database_pass')->nullable();
            $table->string('database_db')->nullable();
            $table->string('role')->default("visitor");
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_keys');
    }
}
