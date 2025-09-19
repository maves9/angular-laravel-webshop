<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // JSON column to store translations keyed by locale, e.g. {"en": "desc", "da": "beskrivelse"}
            if (!Schema::hasColumn('product_variants', 'descriptions')) {
                $table->json('descriptions')->nullable()->after('stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'descriptions')) {
                $table->dropColumn('descriptions');
            }
        });
    }
};
