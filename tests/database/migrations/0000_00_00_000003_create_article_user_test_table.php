<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticleUserTestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('article_user', function (Blueprint $table) {
            $table->unsignedInteger('article_id');
            $table->unsignedInteger('user_id');
            $table->boolean('liked')->nullable();
            $table->timestamps();

            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('article_user');
    }
}
