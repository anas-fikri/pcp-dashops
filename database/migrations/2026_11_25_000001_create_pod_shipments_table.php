<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel kanonik dashboard POD: satu baris per AWB.
 * Di produksi, isi tabel ini via ETL/sinkronisasi terjadwal dari sistem operasional
 * (atau ganti dengan VIEW berkolom sama). Lihat docs/pod-dashboard-spec.md §6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pod_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('awb', 30)->unique();

            $table->string('customer_code', 20);
            $table->string('customer_name', 120);

            // Cabang/perwakilan tujuan = pihak yang bertanggung jawab mengembalikan POD.
            $table->string('dest_branch_code', 20);
            $table->string('dest_branch_name', 120);
            $table->string('dest_branch_type', 12);          // PCP | PERWAKILAN
            $table->string('origin_city', 80);
            $table->string('dest_city', 80);
            $table->unsignedTinyInteger('dest_city_tier');   // 1..3

            $table->string('service_scope', 12);             // INTRACITY | DOMESTIK
            $table->boolean('pod_required');                 // true = POD Balik
            $table->string('pod_status', 24)->nullable();    // App\Enums\PodStatus; null utk Tidak Balik

            $table->dateTime('shipment_date');
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('pod_returned_at')->nullable(); // terisi saat status masuk grup Sudah Balik
            $table->dateTime('pod_status_at')->nullable();
            $table->string('recipient_name', 120)->nullable();
            $table->string('remarks', 255)->nullable();

            $table->timestamps();

            $table->index(['pod_required', 'pod_status', 'service_scope']);
            $table->index('shipment_date');
            $table->index('customer_code');
            $table->index('dest_branch_code');
            $table->index('dest_city_tier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pod_shipments');
    }
};
