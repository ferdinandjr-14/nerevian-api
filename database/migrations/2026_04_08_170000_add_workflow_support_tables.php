<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuaris', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('rol_id')->constrained('clients')->nullOnDelete();
        });

        Schema::create('oferta_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oferta_id')->constrained('ofertes')->cascadeOnDelete();
            $table->foreignId('tracking_step_id')->constrained('tracking_steps')->cascadeOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('usuaris')->nullOnDelete();
            $table->text('observacions')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['oferta_id', 'tracking_step_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oferta_tracking_events');

        Schema::table('usuaris', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
