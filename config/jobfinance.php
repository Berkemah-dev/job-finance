<?php

return [
    'permissions' => ['dashboard.view', 'customers.manage', 'coa.manage', 'quotations.manage', 'quotations.approve', 'jobs.view', 'jobs.manage', 'costs.manage', 'jobs.close', 'invoices.manage', 'payments.manage', 'journals.manage', 'reports.view', 'users.view', 'users.manage', 'roles.manage', 'settings.manage', 'activity.view'],
    'roles' => [
        'super-admin' => ['label' => 'Super Admin', 'permissions' => ['*']],
        'operational' => ['label' => 'Operational', 'permissions' => ['dashboard.view', 'customers.manage', 'quotations.manage', 'quotations.approve', 'jobs.view', 'jobs.manage']],
        'finance' => ['label' => 'Finance', 'permissions' => ['dashboard.view', 'jobs.view', 'costs.manage', 'jobs.close', 'invoices.manage', 'payments.manage', 'journals.manage', 'reports.view']],
        'management' => ['label' => 'Management', 'permissions' => ['dashboard.view', 'reports.view']],
    ],
];
