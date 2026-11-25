<?php

namespace Database\Factories;

use App\Enums\PodStatus;
use App\Models\PodShipment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PodShipment>
 */
class PodShipmentFactory extends Factory
{
    protected $model = PodShipment::class;

    public function definition(): array
    {
        $shipped = CarbonImmutable::now()->subDays(fake()->numberBetween(5, 40))->setTime(10, 0);

        return [
            'awb' => 'AWB'.fake()->unique()->numerify('##########'),
            'customer_code' => 'C001',
            'customer_name' => 'PT Contoh Satu',
            'dest_branch_code' => 'JKT',
            'dest_branch_name' => 'PCP Jakarta',
            'dest_branch_type' => 'PCP',
            'origin_city' => 'Jakarta',
            'dest_city' => 'Jakarta',
            'dest_city_tier' => 1,
            'service_scope' => 'INTRACITY',
            'pod_required' => true,
            'pod_status' => PodStatus::NotDelivered,
            'shipment_date' => $shipped,
            'delivered_at' => null,
            'pod_returned_at' => null,
            'pod_status_at' => $shipped,
        ];
    }

    public function tidakBalik(): static
    {
        return $this->state(fn (array $a) => [
            'pod_required' => false,
            'pod_status' => null,
            'delivered_at' => CarbonImmutable::parse($a['shipment_date'])->addDay(),
        ]);
    }

    /**
     * Status dengan umur yang terkontrol relatif terhadap $asOf:
     *  - NotDelivered      : shipment_date = asOf - $days
     *  - Delivered pending : delivered_at  = asOf - $days
     *  - Sudah balik       : lead time (returned - delivered) = $days
     */
    public function withStatus(PodStatus $status, int $days, CarbonImmutable $asOf): static
    {
        $asOf = $asOf->setTime(9, 0);

        return $this->state(function () use ($status, $days, $asOf) {
            if ($status === PodStatus::NotDelivered) {
                return [
                    'pod_status' => $status,
                    'shipment_date' => $asOf->subDays($days),
                    'delivered_at' => null,
                    'pod_returned_at' => null,
                ];
            }

            if ($status->isReturned()) {
                $delivered = $asOf->subDays($days + 2);

                return [
                    'pod_status' => $status,
                    'shipment_date' => $delivered->subDay(),
                    'delivered_at' => $delivered,
                    'pod_returned_at' => $delivered->addDays($days)->setTime(15, 0),
                ];
            }

            $delivered = $asOf->subDays($days);

            return [
                'pod_status' => $status,
                'shipment_date' => $delivered->subDay(),
                'delivered_at' => $delivered,
                'pod_returned_at' => null,
            ];
        });
    }
}
