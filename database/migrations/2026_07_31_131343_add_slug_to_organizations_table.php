<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        $usedSlugs = [];

        DB::table('organizations')->orderBy('created_at')->get(['id', 'name'])->each(function ($organization) use (&$usedSlugs) {
            $base = Str::slug($organization->name) ?: 'organization';
            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            $usedSlugs[] = $slug;

            DB::table('organizations')->where('id', $organization->id)->update(['slug' => $slug]);
        });

        // Blueprint::change() requires doctrine/dbal, which isn't installed --
        // raw SQL avoids adding that dependency just to tighten a nullable column.
        DB::statement('ALTER TABLE organizations ALTER COLUMN slug SET NOT NULL');

        Schema::table('organizations', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
