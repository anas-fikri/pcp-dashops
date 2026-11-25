<?php

namespace App\Http\Requests;

use App\Enums\PodNode;
use App\Services\Pod\PodFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Whitelist semua parameter filter. Nilai yang lolos validasi saja
 * yang masuk ke query (sebagian disisipkan ke SQL sebagai literal tervalidasi).
 */
class PodFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: ganti dengan policy/RBAC saat autentikasi dipasang.
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'as_of' => ['nullable', 'date_format:Y-m-d'],
            'customer' => ['nullable', 'string', 'max:20', 'alpha_dash'],
            'branch_type' => ['nullable', Rule::in(array_keys(config('pod.branch_types')))],
            'branch' => ['nullable', 'string', 'max:20', 'alpha_dash'],
            'tier' => ['nullable', 'integer', Rule::in(array_keys(config('pod.city_tiers')))],
            'scope' => ['nullable', Rule::in(array_keys(config('pod.scopes')))],
            'node' => ['nullable', Rule::enum(PodNode::class)],
            'bucket' => ['nullable', 'integer', Rule::in(array_keys(config('pod.aging_buckets')))],
            'color' => ['nullable', Rule::in(array_keys(config('pod.aging_colors')))],
            'q' => ['nullable', 'string', 'max:30'],
            'sort' => ['nullable', Rule::in(PodFilter::SORTABLE)],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $f = PodFilter::fromArray($v->validated());
            if ($f->dateFrom->greaterThan($f->dateTo)) {
                $v->errors()->add('date_from', 'Tanggal awal harus sebelum tanggal akhir.');
            }
            if ($f->dateFrom->diffInDays($f->dateTo) + 1 > config('pod.max_period_days')) {
                $v->errors()->add('date_from', 'Rentang tanggal maksimal '.config('pod.max_period_days').' hari.');
            }
            if ($f->dateTo->greaterThan($f->asOf)) {
                $v->errors()->add('date_to', 'Tanggal akhir tidak boleh melewati tanggal posisi (as of).');
            }
        }];
    }

    public function filter(): PodFilter
    {
        return PodFilter::fromArray($this->validated());
    }
}
