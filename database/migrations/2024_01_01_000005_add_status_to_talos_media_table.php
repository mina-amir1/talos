<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talos_media', function (Blueprint $table) {
            $table->string('status')->default('ready')->after('folder');
        });
    }

    public function down(): void
    {
        Schema::table('talos_media', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
