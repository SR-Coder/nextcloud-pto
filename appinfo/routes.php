<?php

declare(strict_types=1);

return [
    'routes' => [
        // Web UI
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        
        // Requests API
        ['name' => 'request#index', 'url' => '/api/v1/requests', 'verb' => 'GET'],
        ['name' => 'request#pending', 'url' => '/api/v1/requests/pending', 'verb' => 'GET'],
        ['name' => 'request#show', 'url' => '/api/v1/requests/{id}', 'verb' => 'GET'],
        ['name' => 'request#create', 'url' => '/api/v1/requests', 'verb' => 'POST'],
        ['name' => 'request#approve', 'url' => '/api/v1/requests/{id}/approve', 'verb' => 'POST'],
        ['name' => 'request#deny', 'url' => '/api/v1/requests/{id}/deny', 'verb' => 'POST'],
        ['name' => 'request#cancel', 'url' => '/api/v1/requests/{id}/cancel', 'verb' => 'POST'],
        ['name' => 'request#approvals', 'url' => '/api/v1/requests/{id}/approvals', 'verb' => 'GET'],
        
        // Policies API
        ['name' => 'policy#index', 'url' => '/api/v1/policies', 'verb' => 'GET'],
        ['name' => 'policy#show', 'url' => '/api/v1/policies/{id}', 'verb' => 'GET'],
        ['name' => 'policy#create', 'url' => '/api/v1/policies', 'verb' => 'POST'],
        ['name' => 'policy#update', 'url' => '/api/v1/policies/{id}', 'verb' => 'PUT'],
        ['name' => 'policy#destroy', 'url' => '/api/v1/policies/{id}', 'verb' => 'DELETE'],
        
        // Balances API
        ['name' => 'balance#index', 'url' => '/api/v1/balances', 'verb' => 'GET'],
        ['name' => 'balance#show', 'url' => '/api/v1/balances/{policyId}', 'verb' => 'GET'],
        ['name' => 'balance#processAccrual', 'url' => '/api/v1/balances/{policyId}/accrual', 'verb' => 'POST'],
        ['name' => 'balance#assignPolicy', 'url' => '/api/v1/balances/assign', 'verb' => 'POST'],
        
        // Users API (Admin)
        ['name' => 'user#index', 'url' => '/api/v1/users', 'verb' => 'GET'],
        ['name' => 'user#managerSummary', 'url' => '/api/v1/users/managers', 'verb' => 'GET'],
        ['name' => 'user#updateManager', 'url' => '/api/v1/users/{userId}/manager', 'verb' => 'PUT'],
    ]
];
