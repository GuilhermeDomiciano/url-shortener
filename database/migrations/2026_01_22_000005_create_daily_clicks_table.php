<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id')->index();
            $table->date('day');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['link_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_clicks');
    }
};
