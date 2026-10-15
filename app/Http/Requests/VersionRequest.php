<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            $items = $this->input('items');
            foreach ($items as $key => $item) {
                if (isset($item['unit_cost']) && is_string($item['unit_cost'])) {
                    $val = trim($item['unit_cost']);
                    if (str_contains($val, '.') && str_contains($val, ',')) {
                        $val = str_replace('.', '', $val);
                        $val = str_replace(',', '.', $val);
                    } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $val)) {
                        $val = str_replace('.', '', $val);
                    } else {
                        $val = str_replace(',', '.', $val);
                    }
                    $items[$key]['unit_cost'] = $val;
                }
                if (isset($item['cost_exchange_rate']) && is_string($item['cost_exchange_rate'])) {
                    $items[$key]['cost_exchange_rate'] = str_replace(',', '.', trim($item['cost_exchange_rate']));
                }
            }
            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.cost_currency' => ['nullable', 'string', Rule::in(array_keys(config('operations.currencies')))],
            'items.*.cost_exchange_rate' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
