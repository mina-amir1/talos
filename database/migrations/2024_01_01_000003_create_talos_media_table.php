<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talos_media', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alternative_text')->nullable();
            $table->string('caption')->nullable();
            $table->string('mime_type');
            $table->string('ext');
            $table->unsignedBigInteger('size'); // bytes
            $table->string('url');
            $table->string('path');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('formats')->nullable(); // thumbnail, small, medium, large
            $table->string('hash')->unique();
            $table->foreignId('uploaded_by')->nullable()->constrained('talos_users')->nullOnDelete();
            $table->string('folder')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talos_media');
    }
};
