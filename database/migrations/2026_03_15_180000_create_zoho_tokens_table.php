<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoho_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('access_token', 2000);
            $table->string('refresh_token', 2000)->nullable();
            $table->string('token_type')->default('Bearer');
            $table->integer('expires_in')->default(3600);
            $table->timestamp('expires_at');
            $table->string('api_domain')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoho_tokens');
    }
};
