<?php

// Privatus Analytics settings. Every setting is optional except the site id.
// See https://privatusanalytics.com/docs/tracker/attributes for what each tracker attribute does.

return [
    // Your site id. It starts with pa_. You find it under Site settings, Tracking.
    'site_id' => env('PRIVATUS_SITE_ID'),

    // Where /js/pa.js is served. Change it only if you host your own copy.
    'script_host' => env('PRIVATUS_SCRIPT_HOST', 'https://privatusstats.com'),

    // Tracker modules, for example ['engage', 'auto']. null keeps the tracker default. [] loads none.
    'modules' => null,

    // Path patterns the tracker never counts, for example ['/admin/*', '/preview/*'].
    'excluded_paths' => [],

    // Hostnames to count. null counts every host.
    'domains' => null,

    // Single-page app mode: auto, history, hash or off. null keeps the tracker default.
    'spa' => null,

    // true turns off the automatic pageview.
    'manual' => false,

    // true or false forces the tag on or off. null uses enabled_environments.
    'enabled' => env('PRIVATUS_ENABLED'),

    // App environments that get the tag when enabled is null.
    'enabled_environments' => ['production'],

    // true adds the tag before </head> of every page in the web middleware group.
    'auto_inject' => false,

    // Any other data- attribute, for example ['data-mask' => '/orders/*->/orders/:id'].
    'extra_attributes' => [],

    // Page properties sent with every pageview and event, for example ['app_version' => '4.2'].
    // Use an array, or the name of an invokable class that gets the request and returns an array.
    // Never put names, emails, user ids or anything else that points to one person in here.
    'props' => null,

    // true prints a small script so privatus.track() calls made before the tracker loads are kept.
    'queue_stub' => false,
];
