<?php

return [
    'permissions' => [
        'dashboard.view',
        'customers.view',
        'customers.manage',
        'coa.manage',
        'quotations.manage',
        'quotations.approve',
        'jobs.view',
        'jobs.manage',
        'costs.manage',
        'jobs.close',
        'invoices.manage',
        'payments.manage',
        'journals.manage',
        'reimbursements.manage',
        'reports.view',
        'financial.view',
        'vendors.manage',
        'pricing.manage',
        'pricing.view',
        'email.manage',
        'users.view',
        'users.manage',
        'roles.manage',
        'settings.manage',
        'activity.view',
    ],
    'reimbursement_categories' => [
        'transport' => ['label' => 'Transportasi', 'icon' => 'truck'],
        'meals' => ['label' => 'Makanan', 'icon' => 'wallet'],
        'travel' => ['label' => 'Perjalanan Dinas', 'icon' => 'briefcase'],
        'supplies' => ['label' => 'Perlengkapan', 'icon' => 'package'],
        'communication' => ['label' => 'Komunikasi', 'icon' => 'wallet'],
        'other' => ['label' => 'Lainnya', 'icon' => 'wallet'],
    ],
    'roles' => [
        'super-admin' => ['label' => 'Super Admin', 'permissions' => ['*']],
        'finance' => ['label' => 'Finance', 'permissions' => [
            'dashboard.view', 'customers.view', 'jobs.view', 'costs.manage', 'jobs.close',
            'invoices.manage', 'payments.manage', 'journals.manage', 'reimbursements.manage', 'reports.view',
            'financial.view', 'pricing.view', 'email.manage', 'activity.view',
        ]],
        'finance-manager' => ['label' => 'Finance Manager', 'permissions' => [
            'dashboard.view', 'customers.view', 'jobs.view', 'costs.manage', 'jobs.close',
            'invoices.manage', 'payments.manage', 'journals.manage', 'reimbursements.manage', 'reports.view',
            'financial.view', 'pricing.view', 'email.manage', 'activity.view', 'users.view',
        ]],
        'sales-manager' => ['label' => 'Sales Manager', 'permissions' => [
            'dashboard.view', 'customers.view', 'customers.manage', 'quotations.manage',
            'quotations.approve', 'jobs.view', 'jobs.manage', 'vendors.manage', 'pricing.manage', 'pricing.view',
        ]],
        'sales' => ['label' => 'Sales', 'permissions' => [
            'dashboard.view', 'customers.view', 'customers.manage', 'quotations.manage',
            'jobs.view', 'pricing.view',
        ]],
        'operational' => ['label' => 'Operation', 'permissions' => [
            'dashboard.view', 'customers.view', 'customers.manage', 'jobs.view', 'jobs.manage',
            'vendors.manage', 'pricing.view',
        ]],
        'customer-service' => ['label' => 'Customer Service', 'permissions' => [
            'dashboard.view', 'customers.view', 'jobs.view', 'jobs.manage', 'pricing.view',
        ]],
        'management' => ['label' => 'Management', 'permissions' => ['dashboard.view']],
    ],
];
