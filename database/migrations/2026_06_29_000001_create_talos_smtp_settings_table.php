<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talos_smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host')->default('');
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('encryption', 10)->default('tls');
            $table->string('username')->default('');
            $table->text('password')->default('');
            $table->string('from_name')->default('Talos CMS');
            $table->string('from_email')->default('');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talos_smtp_settings');
    }
};
