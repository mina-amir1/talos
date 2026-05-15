<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talos_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('type')->default('full-access'); // full-access | read-only | custom
            $table->json('permissions')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('talos_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talos_api_tokens');
    }
};
