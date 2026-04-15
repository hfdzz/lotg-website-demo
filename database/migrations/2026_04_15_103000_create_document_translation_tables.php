<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 8);
            $table->string('title');
            $table->timestamps();

            $table->unique(['document_id', 'language_code']);
        });

        Schema::create('document_page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_page_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 8);
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->timestamps();

            $table->unique(['document_page_id', 'language_code']);
        });

        $timestamp = now();

        $documentTranslations = [];
        foreach (DB::table('documents')->select('id', 'title')->get() as $document) {
            foreach (['id', 'en'] as $languageCode) {
                $documentTranslations[] = [
                    'document_id' => $document->id,
                    'language_code' => $languageCode,
                    'title' => $document->title,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($documentTranslations !== []) {
            DB::table('document_translations')->insert($documentTranslations);
        }

        $pageTranslations = [];
        foreach (DB::table('document_pages')->select('id', 'title', 'body_html')->get() as $page) {
            foreach (['id', 'en'] as $languageCode) {
                $pageTranslations[] = [
                    'document_page_id' => $page->id,
                    'language_code' => $languageCode,
                    'title' => $page->title,
                    'body_html' => $page->body_html,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        if ($pageTranslations !== []) {
            DB::table('document_page_translations')->insert($pageTranslations);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_page_translations');
        Schema::dropIfExists('document_translations');
    }
};
