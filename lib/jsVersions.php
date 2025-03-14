<?php

// getTabulatorIncludes - returns CDN string for Tabulator
function getTabulatorIncludes(): array {
    return ( [
        'tabcss' => 'https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator.min.css',
        'tabbs5' => 'https://unpkg.com/tabulator-tables@6.3.1/dist/css/tabulator_bootstrap5.min.css',
        'tabjs' => 'https://unpkg.com/tabulator-tables@6.3.1/dist/js/tabulator.min.js',
        'luxon' => 'https://cdn.jsdelivr.net/npm/luxon@3.5.0/build/global/luxon.min.js',
        'bs5css' => "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous",
        'bs5js' => "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js' integrity='sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz' crossorigin='anonymous",
        'popjs' => 'https://unpkg.com/@popperjs/core@2',
        'jqjs' => '/jslib/jquery-3.7.1.min.js',
        'jquijs' => '/jslib/jquery-ui.min-1.13.1.js',
        'jquicss' => '/csslib/jquery-ui-1.13.1.css',
    ]);
}

// JS Version items
global $portalJSVersion, $libJSversion, $controllJSversion, $globalJSversion, $atJSversion, $exhibitorJSversion, $onlineregJSversion;
$portalJSVersion = '1.1.4';
$libJSversion = '1.1.13';
$controllJSversion = '1.1.14';
$globalJSversion = '1.1.9';
$atJSversion = '1.1.6';
$exhibitorJSversion = '1.1.6';
$onlineregJSversion = '1.1.4';