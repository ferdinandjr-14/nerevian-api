<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) {
            Schema::drop('documents');
        }

        if (Schema::hasColumn('usuaris', 'dni_document_path')) {
            Schema::table('usuaris', function (Blueprint $table) {
                $table->dropColumn('dni_document_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('usuaris', 'dni_document_path')) {
            Schema::table('usuaris', function (Blueprint $table) {
                $table->string('dni_document_path')->nullable()->after('remember_token');
            });
        }

        if (! Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('oferta_id')->nullable()->constrained('ofertes')->cascadeOnDelete();
                $table->foreignId('usuari_id')->nullable()->constrained('usuaris')->cascadeOnDelete();
                $table->foreignId('uploaded_by_id')->nullable()->constrained('usuaris')->nullOnDelete();
                $table->string('tipus', 50);
                $table->string('nom_original');
                $table->string('disk', 50)->default('local');
                $table->string('path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('mida')->nullable();
                $table->timestamps();
            });
        }
    }
};
