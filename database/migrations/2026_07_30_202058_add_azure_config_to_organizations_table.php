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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('azure_client_id')->nullable()->after('name');
            $table->text('azure_client_secret')->nullable()->after('azure_client_id');
            $table->string('azure_tenant_id')->nullable()->after('azure_client_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['azure_client_id', 'azure_client_secret', 'azure_tenant_id']);
        });
    }
};
