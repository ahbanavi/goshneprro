<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_parties', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_item')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('market_parties', function (Blueprint $table) {
            $table->dropColumn('max_item');
        });
    }
};
