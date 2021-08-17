<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->mediumIncrements('id');
            $table->string('title');
            $table->timestamps();
            $table->softDeletes();
            $table->dateTime("due_date")->nullable();
            $table->unsignedMediumInteger('created_by');
            $table->unsignedMediumInteger('assignee')->nullable();
            $table->string("status",18);
            $table->longText('description')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assignee')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
