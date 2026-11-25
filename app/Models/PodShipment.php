<?php

namespace App\Models;

use App\Enums\PodStatus;
use Database\Factories\PodShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PodShipment extends Model
{
    /** @use HasFactory<PodShipmentFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pod_required' => 'boolean',
            'pod_status' => PodStatus::class,
            'dest_city_tier' => 'integer',
            'shipment_date' => 'datetime',
            'delivered_at' => 'datetime',
            'pod_returned_at' => 'datetime',
            'pod_status_at' => 'datetime',
        ];
    }
}
