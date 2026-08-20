<?php

declare(strict_types=1);

use App\Enums\ComplianceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dossier de conformité d'une villa.
     *
     * ATTENTION — cette table référence des pièces d'identité, des titres de
     * propriété et des documents d'entreprise. Rien ici ne doit jamais être
     * exposé publiquement : les fichiers vivent sur le disque privé
     * « compliance », hors de storage/app/public, et ne sont servis que par
     * une route réservée à l'administrateur.
     */
    public function up(): void
    {
        Schema::create('compliance_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            $table->string('item', 40);
            $table->string('status', 20)->default(ComplianceStatus::Pending->value);

            // Pièce déposée. `document_path` est un chemin sur le disque privé,
            // jamais une URL : il n'existe aucun lien direct vers ces fichiers.
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->string('document_mime', 100)->nullable();
            $table->unsignedInteger('document_size')->nullable();

            // Référence administrative : numéro de RCCM, d'agrément, de pièce…
            $table->string('reference')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Un seul enregistrement par pièce et par villa.
            $table->unique(['property_id', 'item']);
            $table->index(['status', 'expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_checks');
    }
};
