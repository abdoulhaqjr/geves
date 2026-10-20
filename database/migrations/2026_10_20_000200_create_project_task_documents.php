<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents de travail rédigés À PLUSIEURS sur une tâche de projet, et leur
 * historique de versions.
 *
 * ⚠️⚠️ **DEUX REPRÉSENTATIONS DU MÊME DOCUMENT, ET IL FAUT LES DEUX.**
 * - `state` porte l'état **Yjs** (CRDT) : c'est lui qui FUSIONNE les frappes
 *   simultanées de plusieurs personnes sans qu'aucune n'écrase l'autre. Il est
 *   binaire, donc conservé en base64 : une colonne texte voyage de SQLite à
 *   MySQL sans manipulation d'octets, là où un `binary` oblige à s'en occuper
 *   des deux côtés.
 * - `html` porte le rendu **assaini** (`App\Support\Html`), pour tout ce qui
 *   lit le document sans ouvrir l'éditeur : aperçu, export, recherche.
 *   Déduire l'un de l'autre côté serveur demanderait d'exécuter Yjs en PHP.
 *
 * ⚠️ `revision` est le numéro d'ordre des enregistrements, et il sert de
 * **contrôle de concurrence** : un client qui enregistre à partir d'une
 * révision dépassée est refusé et resynchronisé. Sans lui, un poste resté
 * ouvert sur une version ancienne écraserait le travail des autres au premier
 * enregistrement - exactement ce que le CRDT existe pour empêcher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('project_tasks')->cascadeOnDelete();
            $table->string('title', 200);
            $table->longText('html')->nullable();
            $table->longText('state')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'id']);
        });

        Schema::create('project_task_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('project_task_documents')->cascadeOnDelete();
            $table->unsignedInteger('revision')->default(0);
            $table->string('label', 120)->nullable();
            $table->longText('html')->nullable();
            $table->longText('state')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_document_versions');
        Schema::dropIfExists('project_task_documents');
    }
};
