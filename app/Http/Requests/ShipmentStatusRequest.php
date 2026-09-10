<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ShipmentStatusRequest extends VersionRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), ['shipment_status' => ['required', 'string', Rule::in(array_keys(config('operations.shipment_statuses')))]], ['reason' => ['nullable', 'string', 'max:1000']]);
    }
}
