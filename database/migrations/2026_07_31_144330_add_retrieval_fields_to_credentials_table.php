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
        Schema::table('credentials', function (Blueprint $table) {
            $table->timestamp('last_retrieved_at')->nullable()->after('verification_status');
            $table->foreignUuid('last_retrieved_by')->nullable()->after('last_retrieved_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_retrieved_by');
            $table->dropColumn('last_retrieved_at');
        });
    }
};
