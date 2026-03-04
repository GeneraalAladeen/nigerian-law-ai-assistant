<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('law_documents', function (Blueprint $table) {
            $table->string('jurisdiction')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('law_documents', function (Blueprint $table) {
            $table->dropColumn('jurisdiction');
        });
    }
};
