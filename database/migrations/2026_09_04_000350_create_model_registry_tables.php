<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_models', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->string('owner');
            $table->string('status')->default('draft');
            $table->timestampsTz();
        });
        Schema::create('model_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('model_id')->constrained('forecast_models')->cascadeOnDelete();
            $table->string('version');
            $table->string('artifact_ref');
            $table->json('features_schema');
            $table->json('metrics_json')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('retired_at')->nullable();
            $table->timestampsTz();
            $table->unique(['model_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_versions');
        Schema::dropIfExists('forecast_models');
    }
};
