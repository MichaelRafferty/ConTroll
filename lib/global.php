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
            Powered by ConTroll™. Copyright 2015-2025, Michael Rafferty.</br>
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

// get all with the prefix, for response and vardump sort of uses
function getAllSessionVars($prefix = '') {
    global $appSessionPrefix;
    $checkPrefix = ($appSessionPrefix != null ? $appSessionPrefix : '') . $prefix;
    $len = strlen($checkPrefix);
    $vars = [];
    foreach ($_SESSION as $key => $value) {
        if (mb_substr($key, 0, $len) == $checkPrefix)
            $vars[$key] = $_SESSION[$key];
    }
    return $vars;
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
global $customTexT, $keyPrefix, $customTextFilter, $loadedPrefixes;
$loadedPrefixes = [];

// loadCustomText - load all the relevant custom text for this page
    function loadCustomText($app, $page, $filter, $addmode = false) {
        global $customTexT, $keyPrefix, $customTextFilter, $loadedPrefixes;

        $usePrefix = $app . '/' . $page . '/';
        if (array_key_exists($usePrefix, $loadedPrefixes))
            return; // already loaded;

        if (!$addmode) {
            $keyPrefix = $usePrefix;
            $customTexT = [];
            $customTextFilter = $filter;
        }
        $txtQ = <<<EOS
SELECT *
FROM controllTxtItems
WHERE appName = ? AND appPage = ?;
EOS;
        $txtR = dbSafeQuery($txtQ, 'ss',array($app, $page));
        if ($txtR === false)
            return;
        while ($txtL = $txtR->fetch_assoc()) {
            $key = $txtL['appName'] . '/' . $txtL['appPage'] . '/' . $txtL['appSection'] . '/' . $txtL['txtItem'];
            $customTexT[$key] = $txtL['contents'];
        }
        $loadedPrefixes[$usePrefix] = $txtR->num_rows;
        $txtR->free();
    }

// output CustomText - output in a <div container-fluid> a custom text field if it exists and is non empty
    function outputCustomText($key, $overridePrefix = null) {
        global $customTexT, $keyPrefix, $customTextFilter;

        if ($customTextFilter == 'none')
            return;

        if ($customTexT == null) {
            return; // custom text not loaded.
        }

        if ($overridePrefix) {
            $usePrefix = $overridePrefix;
        } else {
            $usePrefix = $keyPrefix;
        }

        if (array_key_exists($usePrefix . $key, $customTexT)) {
            $contents = $customTexT[$usePrefix . $key];
            if ($contents != null && $contents != '') {
                if ($customTextFilter == 'nodefault' || $customTextFilter == 'production') {
                    $prefixStr = 'Controll-Default: ';
                    if (substr($contents, 0, strlen($prefixStr)) == $prefixStr)
                        return;
                    $prefixStr = '<p>Controll-Default: ';
                    if (substr($contents, 0, strlen($prefixStr)) == $prefixStr)
                        return;
                }

                echo '<div class="container-fluid">' . PHP_EOL .
                    $contents . PHP_EOL .
                    '</div>' . PHP_EOL;
            }
        }
    }
    function returnCustomText($key, $overridePrefix = null) {
        global $customTexT, $keyPrefix, $customTextFilter;

        if ($customTextFilter == 'none')
            return '';

        if ($customTexT == null) {
            return ''; // custom text not loaded.
        }

        if ($overridePrefix) {
            $usePrefix = $overridePrefix;
        } else {
            $usePrefix = $keyPrefix;
        }

        if (array_key_exists($usePrefix . $key, $customTexT)) {
            $contents = $customTexT[$usePrefix . $key];
            if ($contents != null && $contents != '') {
                if ($customTextFilter == 'nodefault' || $customTextFilter == 'production') {
                    $prefixStr = 'Controll-Default: ';
                    if (substr($contents, 0, strlen($prefixStr)) == $prefixStr)
                        return '';
                    $prefixStr = '<p>Controll-Default: ';
                    if (substr($contents, 0, strlen($prefixStr)) == $prefixStr)
                        return '';
                }

                return $contents;
            }
        }
        return '';
    }

// replace in strings, items from the config file you can replace in strings
// used by interests and policies, available for emails as well
    function replaceVariables($string) {
        $con = get_conf('con');
        $replaceSource = ['#CONID#', '#CONNAME#', '#CONLABEL#', '#POLICYLINK#', '#POLICYTEXT#'];
        $replaceValue = [ $con['id'], $con['conname'], $con['label'], $con['policy'], $con['policytext'] ];

        return str_replace($replaceSource, $replaceValue, $string);
    }

// rempve L-R override from strings like cut/pasted phone numbers from contact forms
    function removeLROveride($string) {
    if ($string == null || $string == '')
        return $string;

    return preg_replace('/'. mb_chr(0x202d). '/', '', $string);
}