<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('excel_templates', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('storage_path');
            $table->json('parsed_data');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('excel_template_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('tier_index')->default(0);
            $table->string('tier_name')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_email')->nullable();
            $table->text('comment')->nullable();
            $table->json('items');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('excel_templates');
    }
};
