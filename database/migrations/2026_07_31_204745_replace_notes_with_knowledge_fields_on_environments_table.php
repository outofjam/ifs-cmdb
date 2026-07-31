<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->text('purpose')->nullable()->after('build_number');
            $table->text('configuration_notes')->nullable()->after('purpose');
            $table->text('known_issues')->nullable()->after('configuration_notes');
            $table->text('troubleshooting_notes')->nullable()->after('known_issues');
            $table->text('customer_procedures')->nullable()->after('troubleshooting_notes');
            $table->dropColumn('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->dropColumn([
                'purpose',
                'configuration_notes',
                'known_issues',
                'troubleshooting_notes',
                'customer_procedures',
            ]);
        });
    }
};
