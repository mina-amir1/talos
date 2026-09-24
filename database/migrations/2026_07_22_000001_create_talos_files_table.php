<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talos_files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('ext', 20);
            $table->unsignedBigInteger('size');
            $table->string('path');
            $table->string('disk', 20)->default('public');
            $table->string('hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talos_files');
    }
};
