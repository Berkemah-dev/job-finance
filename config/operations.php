<?php

return [
    'job_statuses' => ['draft' => 'Draft', 'open' => 'Open', 'closed' => 'Closed', 'cancelled' => 'Dibatalkan'],
    'cost_statuses' => ['draft' => 'Draft', 'final' => 'Final'],
    'service_types' => ['land' => 'Darat', 'sea' => 'Laut', 'air' => 'Udara', 'multimodal' => 'Multimoda', 'other' => 'Lainnya'],
    'vendor_types' => ['shipping_line' => 'Shipping Lines', 'trucking' => 'Vendor Trucking', 'international_agent' => 'International Agent', 'national_agent' => 'National Agent'],
    'customer_payment_terms' => ['cash' => 'Cash', 'net_7' => 'Net 7', 'net_14' => 'Net 14', 'net_30' => 'Net 30', 'net_60' => 'Net 60'],
    'container_types' => ['20ft' => '20 FT', '40ft' => '40 FT', 'lcl' => 'LCL'],
    'currencies' => ['IDR' => 'IDR (Rupiah Indonesia)', 'USD' => 'USD (US Dollar)'],
    'pricing_sources' => ['manual' => 'Manual', 'trucking' => 'Tarif Trucking'],
];
