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
        Schema::table('mobil', function (Blueprint $table) {
            $table->dateTime('tanggal_start_sewa')->nullable()->after('tanggal_pembelian');
            $table->bigInteger('km_start_sewa')->nullable()->after('tanggal_start_sewa');
            $table->string('rental_reference', 100)->nullable()->after('km_start_sewa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobil', function (Blueprint $table) {
            $table->dropColumn(['tanggal_start_sewa', 'km_start_sewa', 'rental_reference']);
        });
    }
};
