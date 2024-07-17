<?php
//  global.php
// functions useful everywhere in the reg system

// non Windows implementation of guidv4
function guidv4($data = null) {
    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// draw the small ConTroll bug (and in the future the apglv3 bug)
function drawBug($cols): void {
    $textCols = $cols - 1;
    echo <<<EOS
        <div class="col-sm-$textCols">
            <p>
            Powered by ConTroll™. Copyright 2015-2024, Michael Rafferty.</br>
            <img src="/lib/apglv3-bug.png" alt="GNU Affero General Public License logo"> ConTroll™ is freely available for use under the GNU Affero General 
            Public License, Version 3.
            See the <a href="https://github.com/MichaelRafferty/ConTroll/blob/master/README.md" target="_blank">ConTroll™ ReadMe file</a>.
            </p>
        </div>
        <div class="col col-sm-1">
            <img src="/lib/ConTroll-bug.png" alt="ConTroll Logo as a small 'bug'">
        </div>
EOS;
}

// getTabulatorIncludes - returns CDN string for Tabulator
function getTabulatorIncludes(): array {
    return ( [
        'tabcss' => 'https://unpkg.com/tabulator-tables@6.2.1/dist/css/tabulator.min.css',
        'tabbs5' => 'https://unpkg.com/tabulator-tables@6.2.1/dist/css/tabulator_bootstrap5.min.css',
        'tabjs' => 'https://unpkg.com/tabulator-tables@6.2.1/dist/js/tabulator.min.js',
        'luxon' => 'https://cdn.jsdelivr.net/npm/luxon@3.4.4/build/global/luxon.min.js',
        'bs5css' => "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' integrity='sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH' crossorigin='anonymous",
        'bs5js' => "https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js' integrity='sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz' crossorigin='anonymous",
        'jqjs' => '/jslib/jquery-3.7.1.min.js',
        'jquijs' => '/jslib/jquery-ui.min-1.13.1.js',
        'jquicss' => '/csslib/jquery-ui-1.13.1.css',
    ]);
}

// session functions to allow for prefixes
// get value from session
function getSessionVar($name) {
    global $appSessionPrefix;
    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    if (isset($_SESSION[$sesName])) {
        return $_SESSION[$sesName];
    }
    return null;
}

// set value in session
function setSessionVar($name, $value) {
    global $appSessionPrefix;

    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    $_SESSION[$sesName] = $value;
}

// does session variable exist
function isSessionVar($name) {
    global $appSessionPrefix;

    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    return isset($_SESSION[$sesName]);
}
// unset session variable
function unsetSessionVar($name) {
    global $appSessionPrefix;
    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    unset($_SESSION[$sesName]);
}
// clear the session for re-use on logout
function clearSession($prefix = '') {
    global $appSessionPrefix;
    $checkPrefix = ($appSessionPrefix != null ? $appSessionPrefix : '') . $prefix;
    $len = strlen($checkPrefix);
    foreach ($_SESSION as $key => $value) {
        if (mb_substr($key, 0, $len) == $checkPrefix)
            unset($_SESSION[$key]);
    }
}

// is a memList item a primary membership type
function isPrimary($mtype, $conid) {
    if ($mtype['price'] == 0 || $conid != $mtype['conid'] ||
        ($mtype['memCategory'] != 'standard' && $mtype['memCategory'] != 'supplement' && $mtype['memCategory'] != 'virtual')
    ) {
        return false;
    }
    return true;
}

//// functions for custom text usage
global $customTexT, $keyPrefix, $customTextFilter;


// loadCustomText - load all the relevant custom text for this page
    function loadCustomText($app, $page, $filter) {
        global $customTexT, $keyPrefix, $customTextFilter;

        $keyPrefix = $app . '/' . $page . '/';
        $customTextFilter = $filter;
        $keyApp = $app;
        $customTexT = [];
        $txtQ = <<<EOS
SELECT *
FROM controllTxtItems
WHERE appName = ? AND appPage = ?;
EOS;
        $txtR = dbSafeQuery($txtQ, 'ss',array($app, $page));
        if ($txtR == false)
            return;
        while ($txtL = $txtR->fetch_assoc()) {
            $key = $txtL['appName'] . '/' . $txtL['appPage'] . '/' . $txtL['appSection'] . '/' . $txtL['txtItem'];
            $customTexT[$key] = $txtL['contents'];
        }
        $txtR->free();
    }

// output CustomText - output in a <div container-fluid> a custom text field if it exists and is non empty
    function outputCustomText($key) {
        global $customTexT, $keyPrefix, $customTextFilter;

        if ($customTextFilter == 'none')
            return;

        if (array_key_exists($keyPrefix . $key, $customTexT)) {
            $contents = $customTexT[$keyPrefix . $key];
            if ($contents != null && $contents != '') {
                if ($customTextFilter == 'nodefault' || $customTextFilter == 'production') {
                    $prefixStr = 'Controll-Default: ';
                    if (substr($contents, 0, strlen($prefixStr)) == $prefixStr)
                        return;
                }

                echo '<div class="container-fluid p-0 m-0">' . PHP_EOL .
                    $contents . PHP_EOL .
                    '</div>' . PHP_EOL;
            }
        }
    }
