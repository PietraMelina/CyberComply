<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'cmd' => [
        'client_id' => env('CMD_CLIENT_ID'),
        'client_secret' => env('CMD_CLIENT_SECRET'),
        'redirect' => env('CMD_REDIRECT_URL', env('APP_URL').'/auth/cmd/callback'),
        'scopes' => ['openid', 'profile', 'attributes'],
        'authorization_endpoint' => env('CMD_AUTHORIZATION_ENDPOINT', 'https://autenticacao.gov.pt/oauth/authorize'),
        'token_endpoint' => env('CMD_TOKEN_ENDPOINT', 'https://autenticacao.gov.pt/oauth/token'),
        'userinfo_endpoint' => env('CMD_USERINFO_ENDPOINT', 'https://autenticacao.gov.pt/oauth/userinfo'),
        'mock_enabled' => filter_var(env('CMD_MOCK_ENABLED', true), FILTER_VALIDATE_BOOL),
        'mock_nif' => env('CMD_MOCK_NIF', '123456789'),
        'mock_name' => env('CMD_MOCK_NAME', 'Utilizador CMD'),
    ],

];
