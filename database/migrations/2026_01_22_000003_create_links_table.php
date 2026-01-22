<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->text('original_url');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['slug', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
