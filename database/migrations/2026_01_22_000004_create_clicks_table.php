<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('link_id')->index();
            $table->timestamp('clicked_at')->useCurrent();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
