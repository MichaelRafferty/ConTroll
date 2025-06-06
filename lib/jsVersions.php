<?php

// getTabulatorIncludes - returns CDN string for Tabulator
function getTabulatorIncludes(): array {
    return ( [
        'tabcss' => 'https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css',
        'tabbs5' => 'https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator_bootstrap5.min.css',
        'tabjs' => 'https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js',
        'luxon' => 'https://cdn.jsdelivr.net/npm/luxon@3.5.0/build/global/luxon.min.js',
        'bs5css' => "https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css' rel='stylesheet' integrity='sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT' crossorigin='anonymous'",
        'bs5js' => "https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js' integrity='sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO' crossorigin='anonymous'",
        'popjs' => 'https://unpkg.com/@popperjs/core@2',
        'jqjs' => '/jslib/jquery-3.7.1.min.js',
        'jquijs' => '/jslib/jquery-ui.min-1.13.1.js',
        'jquicss' => '/csslib/jquery-ui-1.13.1.css',
    ]);
}

// JS Version items
global $portalJSVersion, $libJSversion, $controllJSversion, $globalJSversion, $atJSversion, $exhibitorJSversion, $onlineregJSversion;
$portalJSVersion = '1.3.0';
$libJSversion = '1.3.3';
$controllJSversion = '1.3.2';
$globalJSversion = '1.3.0';
$atJSversion = '1.3.0';
$exhibitorJSversion = '1.3.0';
$onlineregJSversion = '1.3.0';