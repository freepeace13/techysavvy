<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_inspector_bins', function (Blueprint $table) {
            $table->id();
            $table->string('bin_id', 16)->unique();
            $table->string('view_token', 40)->unique();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('webhook_inspector_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bin_id')->constrained('webhook_inspector_bins')->cascadeOnDelete();
            $table->string('method', 10);
            $table->text('path');
            $table->text('query');
            $table->json('headers');
            $table->longText('body')->nullable();
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('body_size');
            $table->boolean('truncated');
            $table->boolean('is_binary');
            $table->string('ip', 45)->nullable();
            $table->timestamp('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_inspector_requests');
        Schema::dropIfExists('webhook_inspector_bins');
    }
};
