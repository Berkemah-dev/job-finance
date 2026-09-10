@props(['name', 'label' => null, 'value' => null, 'allowCustom' => false])
@php($terms = config('operations.customer_payment_terms'))
<label for="{{ $name }}">{{ $label ?? 'Syarat pembayaran' }}</label>
<select id="{{ $name }}" name="{{ $name }}" {{ $attributes }}>
    <option value="">—</option>
    @foreach($terms as $key => $optionLabel)
    <option value="{{ $key }}" @selected((string)$value === (string)$key)>{{ $optionLabel }}</option>
    @endforeach
</select>