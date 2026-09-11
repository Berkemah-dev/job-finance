<?php

return [
    'job_statuses' => ['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed', 'cancelled' => 'Dibatalkan'],
    'shipment_statuses' => ['booked' => 'Booked', 'in_progress' => 'In Progress', 'departed' => 'Departed', 'arrived' => 'Arrived', 'spjm' => 'SPJM', 'sppb' => 'SPPB', 'do_process' => 'DO Process', 'completed' => 'Completed'],
    'cost_statuses' => ['draft' => 'Draft', 'final' => 'Final'],
    'service_types' => [
        'imp_sea' => 'Import Sea',
        'exp_sea' => 'Export Sea',
        'imp_air' => 'Import Air',
        'exp_air' => 'Export Air',
        'dom_sea' => 'Domestic Sea',
        'dom_air' => 'Domestic Air',
        'land' => 'Darat / Trucking',
        'sea' => 'Laut',
        'air' => 'Udara',
        'multimodal' => 'Multimoda',
        'other' => 'Lainnya',
    ],
    'terms_of_delivery' => [
        'EXW' => 'EXW (Ex Works)',
        'FOB' => 'FOB (Free On Board)',
        'CFR' => 'CFR (Cost and Freight)',
        'CIF' => 'CIF (Cost, Insurance and Freight)',
        'CIP' => 'CIP (Carriage and Insurance Paid)',
        'CPT' => 'CPT (Carriage Paid To)',
        'DAP' => 'DAP (Delivered at Place)',
        'DPU' => 'DPU (Delivered at Place Unloaded)',
        'DDP' => 'DDP (Delivered Duty Paid)',
        'DDU' => 'DDU (Delivered Duty Unpaid)',
        'D2D' => 'D2D (Door to Door)',
    ],
    'vendor_types' => ['shipping_line' => 'Shipping Lines', 'trucking' => 'Vendor Trucking', 'international_agent' => 'International Agent', 'national_agent' => 'National Agent'],
    'customer_payment_terms' => ['cash' => 'Cash', 'net_7' => 'Net 7', 'net_14' => 'Net 14', 'net_30' => 'Net 30', 'net_45' => 'Net 45', 'net_60' => 'Net 60', 'custom' => 'Custom'],
    // Format kode customer otomatis: prefix + delimiter + tahun (period year) + delimiter + nomor urut pad.
    'customer_code' => ['prefix' => 'CUS', 'delimiter' => '-', 'period' => 'year', 'pad' => 5],
    // Aturan unggahan dokumen customer (NPWP/NIB): MIME, ekstensi, dan batas ukuran KB.
    'customer_documents' => ['mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 'extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 5120],
    // Nilai bawaan kalkulator LCL (boleh kosong; rate ditentukan pemakai bila belum terisi).
    'lcl' => ['default_rate' => null],
    'container_types' => ['20ft' => '20 FT', '40ft' => '40 FT', '40hc' => '40 HC', 'lcl' => 'LCL'],
    'currencies' => ['IDR' => 'IDR (Rupiah Indonesia)', 'USD' => 'USD (US Dollar)'],
    'pricing_sources' => ['manual' => 'Manual', 'trucking' => 'Tarif Trucking'],
];
