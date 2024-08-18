<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_parties', function (Blueprint $table) {
            $table->json('vendors')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('food_parties', function (Blueprint $table) {
            $table->dropColumn('vendors');
        });
    }
};
