<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variant_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Add nullable foreign key to product_variants and migrate existing type data
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('variant_type_id')->nullable()->constrained('variant_types')->nullOnDelete();
        });

        // Migrate existing 'type' strings into variant_types and link
        $types = DB::table('product_variants')->select('type')->distinct()->pluck('type');
        foreach ($types as $t) {
            if (is_null($t)) continue;
            $id = DB::table('variant_types')->insertGetId([
                'name' => $t,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('product_variants')->where('type', $t)->update(['variant_type_id' => $id]);
        }

        // Optionally remove the old 'type' column if it exists
        if (Schema::hasColumn('product_variants', 'type')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    public function down(): void
    {
        // Re-add 'type' column and populate from variant_types
        if (!Schema::hasColumn('product_variants', 'type')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->string('type')->nullable();
            });

            $mapping = DB::table('variant_types')->pluck('id', 'name');
            foreach ($mapping as $name => $id) {
                DB::table('product_variants')->where('variant_type_id', $id)->update(['type' => $name]);
            }
        }

        // Drop foreign key and column
        if (Schema::hasColumn('product_variants', 'variant_type_id')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('variant_type_id');
            });
        }

        Schema::dropIfExists('variant_types');
    }
};
