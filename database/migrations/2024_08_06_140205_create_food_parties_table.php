<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->restrictOnUpdate();

            $table->string('description')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->unsignedTinyInteger('threshold')->default(0);
            $table->unsignedBigInteger('tg_chat_id');
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_parties');
    }
};
