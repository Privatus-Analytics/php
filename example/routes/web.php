<?php

// Conformance example app for privatus/analytics (Laravel).
//
// Built by the conformance suite (https://github.com/Privatus-Analytics/conformance).
// See its CONTRACT.md for what each route must render. The site id and script
// host come from PRIVATUS_SITE_ID and PRIVATUS_SCRIPT_HOST.

use Illuminate\Support\Facades\Route;
use Privatus\Analytics\Laravel\InjectPrivatusAnalytics;

const PAGE_PROPS = ['plan' => 'pro', 'role' => 'admin', 'beta' => true, 'seats' => 3];
const NASTY = "</script><script>window.__pwned=1</script>\u{2028}\u{2029}&<>\"'";

const AUTO_PAGE = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Auto inject</title>
</head>
<body><h1>Auto inject</h1><p>No tag in the template. The middleware adds it.</p></body>
</html>
HTML;

Route::get('/', fn () => view('home', ['props' => PAGE_PROPS]));
Route::get('/event', fn () => view('event', ['props' => PAGE_PROPS]));
Route::get('/nasty', fn () => view('nasty', ['props' => PAGE_PROPS + ['nasty' => NASTY], 'nasty' => NASTY]));

// Off with the queue stub on: only the stub prints, so privatus.track still works.
Route::get('/off', fn () => view('off', ['props' => PAGE_PROPS, 'stub' => true]));
// Off with the queue stub off: nothing prints.
Route::get('/off-plain', fn () => view('off', ['props' => PAGE_PROPS, 'stub' => false]));

// Auto inject on this route only, so the other routes test the directive alone.
Route::get('/auto', fn () => response(AUTO_PAGE, 200, ['Content-Type' => 'text/html; charset=UTF-8']))
    ->middleware(InjectPrivatusAnalytics::class);
