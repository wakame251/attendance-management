<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamp_correction_request_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stamp_correction_request_id');

            $table->foreign('stamp_correction_request_id', 'scr_breaks_request_id_fk')
                ->references('id')
                ->on('stamp_correction_requests')
                ->cascadeOnDelete();

            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_request_breaks');
    }
};