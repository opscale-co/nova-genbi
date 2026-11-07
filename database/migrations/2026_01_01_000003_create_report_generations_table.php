<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_generations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('report_template_id')
                ->constrained('report_templates')
                ->cascadeOnDelete();
            $table->json('parameters')->nullable();
            $table->string('format')->default('xlsx');
            $table->string('status')->default('pending');
            $table->string('file_name')->nullable();
            $table->string('file_path', 2048)->nullable();
            $table->unsignedBigInteger('row_count')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_generations');
    }
};
