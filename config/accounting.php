<?php

return [
    'types' => ['asset' => 'Aset', 'liability' => 'Liabilitas', 'equity' => 'Ekuitas', 'revenue' => 'Pendapatan', 'cogs' => 'HPP', 'expense' => 'Beban'],
    'mappings' => [
        'cash' => ['label' => 'Kas', 'type' => 'asset', 'code' => '1101', 'name' => 'Kas'],
        'bank' => ['label' => 'Bank', 'type' => 'asset', 'code' => '1102', 'name' => 'Bank'],
        'receivable' => ['label' => 'Piutang Customer', 'type' => 'asset', 'code' => '1103', 'name' => 'Piutang Customer'],
        'temporary' => ['label' => 'Temporary Job', 'type' => 'asset', 'code' => '1104', 'name' => 'Temporary Job'],
        'provision_wip' => ['label' => 'Provision / WIP', 'type' => 'asset', 'code' => '1105', 'name' => 'Provision / Job Cost WIP'],
        'revenue' => ['label' => 'Pendapatan Jasa', 'type' => 'revenue', 'code' => '4101', 'name' => 'Pendapatan Jasa'],
        'cogs' => ['label' => 'HPP Job', 'type' => 'cogs', 'code' => '5101', 'name' => 'HPP Job'],
        'expense' => ['label' => 'Beban Operasional', 'type' => 'expense', 'code' => '6101', 'name' => 'Beban Operasional'],
        'tax_payable' => ['label' => 'Utang Pajak', 'type' => 'liability', 'code' => '2102', 'name' => 'Utang Pajak'],
    ],
    'coretax' => [
        // Profil PKP penjual untuk ekspor XML Coretax (NPWP 16 digit).
        'seller_npwp' => '0100000000000000',
        'seller_name' => 'PT Jasa Logistik Nusantara',
        'seller_address' => 'Jl. Raya Pelabuhan No. 1',
        'seller_city' => 'Jakarta',
        'seller_postal_code' => '10110',
        'vat_rate' => 11,
    ],
];
