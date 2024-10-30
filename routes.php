<?php
defined('ABSPATH') || exit;
$routes = [
    [
        'endpoint' => '/auth/login',
        'method' => 'POST',
        'callback' => 'Letscms\Controllers\LetscmsUser@login',
        'auth' => false,
    ],
    [
        'endpoint' => '/auth/register',
        'method' => 'POST',
        'callback' => 'Letscms\Controllers\LetscmsUser@register',
        'auth' => false,
    ],
    [
        'endpoint' => '/auth/forgot-password',
        'method' => 'POST',
        'callback' => 'Letscms\Controllers\LetscmsUser@sendPassWord',
        'auth' => false,
    ],
    [
        'endpoint' => '/address/(?P<type>[\w-]+)',
        'method' => 'GET',
        'callback' => 'Letscms\Controllers\LetscmsUser@getAddresses',
        'auth' => true,
    ],
    [
        'endpoint' => '/address/(?P<type>[\w-]+)',
        'method' => 'POST',
        'callback' => 'Letscms\Controllers\LetscmsUser@saveAddresses',
        'auth' => true,
    ],
    [
        'endpoint' => '/account-details',
        'method' => 'POST',
        'callback' => 'Letscms\Controllers\LetscmsUser@updateAccountDetails',
        'auth' => true,
    ],
    [
        'endpoint' => '/page/(?P<name>[\w-]+)',
        'method' => 'GET',
        'callback' => 'Letscms\Controllers\LetscmsPage@getContent',
        'auth' => false,
    ],
];
return apply_filters('lets_woo_routes', $routes);
exit;
