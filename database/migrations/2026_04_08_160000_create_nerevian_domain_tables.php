<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rols', function (Blueprint $table) {
            $table->id();
            $table->string('rol')->unique();
        });

        Schema::create('paissos', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('cif')->unique();
        });

        Schema::create('tipus_transports', function (Blueprint $table) {
            $table->id();
            $table->string('tipus')->unique();
        });

        Schema::create('tipus_fluxes', function (Blueprint $table) {
            $table->id();
            $table->string('tipus')->unique();
        });

        Schema::create('tipus_carrega', function (Blueprint $table) {
            $table->id();
            $table->string('tipus')->unique();
        });

        Schema::create('tipus_contenidors', function (Blueprint $table) {
            $table->id();
            $table->string('tipus')->unique();
        });

        Schema::create('tipus_validacions', function (Blueprint $table) {
            $table->id();
            $table->string('tipus')->unique();
        });

        Schema::create('estats_ofertes', function (Blueprint $table) {
            $table->id();
            $table->string('estat')->unique();
        });

        Schema::create('tipus_incoterms', function (Blueprint $table) {
            $table->id();
            $table->string('codi', 10)->unique();
            $table->string('nom');
        });

        Schema::create('tracking_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->unique();
            $table->string('nom');
        });

        Schema::create('usuaris', function (Blueprint $table) {
            $table->id();
            $table->string('correu')->unique();
            $table->string('contrasenya');
            $table->string('nom');
            $table->string('cognoms')->nullable();
            $table->foreignId('rol_id')->nullable()->constrained('rols')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('ciutats', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('pais_id')->constrained('paissos')->cascadeOnDelete();
            $table->unique(['nom', 'pais_id']);
        });

        Schema::create('aeroports', function (Blueprint $table) {
            $table->id();
            $table->string('codi', 10)->unique();
            $table->string('nom');
            $table->foreignId('ciutat_id')->constrained('ciutats')->cascadeOnDelete();
        });

        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('ciutat_id')->constrained('ciutats')->cascadeOnDelete();
            $table->unique(['nom', 'ciutat_id']);
        });

        Schema::create('transportistes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('ciutat_id')->constrained('ciutats')->cascadeOnDelete();
            $table->unique(['nom', 'ciutat_id']);
        });

        Schema::create('linies_transport_maritim', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('ciutat_id')->constrained('ciutats')->cascadeOnDelete();
            $table->unique(['nom', 'ciutat_id']);
        });

        Schema::create('incoterms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipus_inconterm_id')->constrained('tipus_incoterms')->cascadeOnDelete();
            $table->foreignId('tracking_steps_id')->constrained('tracking_steps')->cascadeOnDelete();
            $table->unique(['tipus_inconterm_id', 'tracking_steps_id'], 'incoterms_tipus_tracking_unique');
        });

        Schema::create('ofertes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipus_transport_id')->nullable()->constrained('tipus_transports')->nullOnDelete();
            $table->foreignId('tipus_fluxe_id')->nullable()->constrained('tipus_fluxes')->nullOnDelete();
            $table->foreignId('tipus_carrega_id')->nullable()->constrained('tipus_carrega')->nullOnDelete();
            $table->foreignId('incoterm_id')->nullable()->constrained('incoterms')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->text('comentaris')->nullable();
            $table->foreignId('agent_comercial_id')->nullable()->constrained('usuaris')->nullOnDelete();
            $table->foreignId('transportista_id')->nullable()->constrained('transportistes')->nullOnDelete();
            $table->decimal('pes_brut', 12, 3)->nullable();
            $table->decimal('volum', 12, 3)->nullable();
            $table->foreignId('tipus_validacio_id')->nullable()->constrained('tipus_validacions')->nullOnDelete();
            $table->foreignId('port_origen_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('port_desti_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('aeroport_origen_id')->nullable()->constrained('aeroports')->nullOnDelete();
            $table->foreignId('aeroport_desti_id')->nullable()->constrained('aeroports')->nullOnDelete();
            $table->foreignId('linia_transport_maritim_id')->nullable()->constrained('linies_transport_maritim')->nullOnDelete();
            $table->foreignId('estat_oferta_id')->nullable()->constrained('estats_ofertes')->nullOnDelete();
            $table->foreignId('operador_id')->nullable()->constrained('usuaris')->nullOnDelete();
            $table->date('data_creacio')->nullable();
            $table->date('data_validessa_inicial')->nullable();
            $table->date('data_validessa_fina')->nullable();
            $table->text('rao_rebuig')->nullable();
            $table->foreignId('tipus_contenidor_id')->nullable()->constrained('tipus_contenidors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ofertes');
        Schema::dropIfExists('incoterms');
        Schema::dropIfExists('linies_transport_maritim');
        Schema::dropIfExists('transportistes');
        Schema::dropIfExists('ports');
        Schema::dropIfExists('aeroports');
        Schema::dropIfExists('ciutats');
        Schema::dropIfExists('usuaris');
        Schema::dropIfExists('tracking_steps');
        Schema::dropIfExists('tipus_incoterms');
        Schema::dropIfExists('estats_ofertes');
        Schema::dropIfExists('tipus_validacions');
        Schema::dropIfExists('tipus_contenidors');
        Schema::dropIfExists('tipus_carrega');
        Schema::dropIfExists('tipus_fluxes');
        Schema::dropIfExists('tipus_transports');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('paissos');
        Schema::dropIfExists('rols');
    }
};
