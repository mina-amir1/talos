<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talos_notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event', 32);
            $table->string('content_type_uid')->nullable();
            $table->json('recipients');
            $table->json('fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talos_notification_rules');
    }
};
