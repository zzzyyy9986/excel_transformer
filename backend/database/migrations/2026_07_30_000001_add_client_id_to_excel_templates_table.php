<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('excel_templates', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('excel_templates', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
