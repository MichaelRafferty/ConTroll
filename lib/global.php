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
function setSessionVar($name, $value) : void {
    global $appSessionPrefix;

    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    $_SESSION[$sesName] = $value;
}

// does session variable exist
function isSessionVar($name) : bool {
    global $appSessionPrefix;

    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    return isset($_SESSION[$sesName]);
}
// unset session variable
function unsetSessionVar($name) : void {
    global $appSessionPrefix;
    $sesName = ($appSessionPrefix != null ? $appSessionPrefix : '') . $name;
    unset($_SESSION[$sesName]);
}
// clear the session for re-use on logout
function clearSession($prefix = '') : void {
    global $appSessionPrefix;
    $checkPrefix = ($appSessionPrefix != null ? $appSessionPrefix : '') . $prefix;
    $len = strlen($checkPrefix);
    foreach ($_SESSION as $key => $value) {
        if (mb_substr($key, 0, $len) == $checkPrefix)
            unset($_SESSION[$key]);
    }
}

// get all with the prefix, for response and vardump sort of uses
function getAllSessionVars($prefix = '') : array {
    global $appSessionPrefix;
    $checkPrefix = ($appSessionPrefix != null ? $appSessionPrefix : '') . $prefix;
    $len = strlen($checkPrefix);
    $vars = [];
    foreach ($_SESSION as $key => $value) {
        if (mb_substr($key, 0, $len) == $checkPrefix)
            $vars[$key] = $value;
    }
    return $vars;
}

// is a memList item a primary membership type
function isPrimary($mtype, $conid, $use = 'all') : bool {
    if ($conid != $mtype['conid']) // must be a current year membership to be primary, no year aheads for next year
        return false;

    $memType = $mtype['memType'];
    if (!($memType == 'full' || $memType == 'oneday' || $memType == 'virtual'))
        return false;   // must be one of these main types to even be considered a primary

    if ($use == 'all')
        return true;    // the basic case, it's a primary if it's one of these types

    if ($use == 'coupon') {
        if ($mtype['price'] == 0 || $memType != 'full')
            return false; // free memberships and oneday/virtual are not eligible for coupons
    }

    if ($use == 'print') {
        if ($mtype['memCategory'] == 'virtual')
            return false; // virtual cannot be printed
    }

    // we got this far, all the 'falses; are called out, so it must be true
    return true;
}

//// functions for custom text usage
global $customTexT, $keyPrefix, $customTextFilter, $loadedPrefixes;
$loadedPrefixes = [];
$keyPrefix = '';

// loadCustomText - load all the relevant custom text for this page
    function loadCustomText($app, $page, $filter, $addmode = false) : void {
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
    function outputCustomText($key, $overridePrefix = null) : void {
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
                    if (str_starts_with($contents, $prefixStr))
                        return;
                    $prefixStr = '<p>Controll-Default: ';
                    if (str_starts_with($contents, $prefixStr))
                        return;
                }

                echo '<div class="container-fluid">' . PHP_EOL .
                    replaceConfigTokens($contents) . PHP_EOL .
                    '</div>' . PHP_EOL;
            }
        }
    }
    function returnCustomText($key, $overridePrefix = null) : string {
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
                    if (str_starts_with($contents, $prefixStr))
                        return '';
                    $prefixStr = '<p>Controll-Default: ';
                    if (str_starts_with($contents, $prefixStr))
                        return '';
                }

                return replaceConfigTokens($contents);
            }
        }
        return '';
    }

// replace in strings, items from the config file you can replace in strings
// used by interests and policies, available for emails as well
    function replaceVariables($string) : string {
        $con = get_conf('con');
        $replaceSource = ['#CONID#', '#CONNAME#', '#CONLABEL#', '#POLICYLINK#', '#POLICYTEXT#'];
        $replaceValue = [ $con['id'], $con['conname'], $con['label'], $con['policy'], $con['policytext'] ];

        return str_replace($replaceSource, $replaceValue, $string);
    }

// replaceConfigTokens - replace configuration tokens of the form #section.element# in a text string with values from the parsed configuration file
// NOTE: the sections cc, client, debug, email, google, local, log, mysql are skipped for security reasons as they hold keys and other protected data
const replaceConfigTokensSkip = ['cc', 'client', 'debug', 'email', 'google', 'local', 'log', 'mysql'];
    function replaceConfigTokens($string) : string {
        global $db_ini;

        $pattern = '/#[^#]+#/';     // config tokens are #item.section#, but if the dot is missing, 'reg' will be assumed
        // get the matches if any
        $count = preg_match_all($pattern, $string, $matches);
        if ($count == 0 || count($matches) == 0)
            return $string;     // nothing was returned by the pattern check, just deal with the string as is
        $matches = $matches[0]; // it returns an array of pattern parts and then matches
        if (count($matches) == 0)
            return $string;     // not strings found

        foreach ($matches as $match) {                          // loop over all variables found and replace them
            $token = mb_substr($match, 1, strlen($match) - 2);  // string the #'s off each end
            if (str_contains($token, '.')) {
                [$section, $element] = explode('.', $token);        // split into parts
            } else {
                $element = $token;  // default to reg. if the section is missing
                $section = 'con';
            }

            if (in_array($section, replaceConfigTokensSkip)) // skip over restricted tokens
                continue;

            if (!array_key_exists($section, $db_ini))
                continue;       // section missing, leave token in the string and move on
            if (!array_key_exists($element, $db_ini[$section]))
                continue;       // element missing, leave token in the string and move on

            $string = str_replace($match, $db_ini[$section][$element], $string);
        }

        return $string;
    }

// rempve L-R override from strings like cut/pasted phone numbers from contact forms
    function removeLROveride($string) {
    if ($string == null || $string == '')
        return $string;

    return preg_replace('/'. mb_chr(0x202d). '/', '', $string);
}

// as per: https://en.wikipedia.org/wiki/North_American_Numbering_Plan
$callingCodes = null;
// phoneNumberNormalize: take a struct with email, phone, country and return the normalized phone number or empty string if unable.
function phoneNumberNormalize($buyer) : string {
    global $NANPcountries, $callingCodes;
    if (!array_key_exists('phone', $buyer))
        return '';
    $phone = $buyer['phone'];
    if ($phone == '')
        return '';

    if (!array_key_exists('country', $buyer))
        return '';

    $country = $buyer['country'];
    if ($country == '')
        return '';

    // ok we have a country and a phone number
    //  buyer_phone_number: string
    //
    //  The buyer's phone number. Must follow the following format:
    //
    //  A leading + symbol (followed by a country code)
    //  The phone number can contain spaces and the special characters ( , ) , - , and .. Alphabetical characters aren't allowed.
    //  The phone number must contain between 9 and 16 digits.

    if ($callingCodes == null) {
        $fh = fopen(__DIR__ . '/../lib/callingCodes.csv', 'r');
        if ($fh === false)
            return '';

        $callingCodes = [];
        while (($data = fgetcsv($fh, 1000, ',', '"')) != false) {
            $callingCodes[$data[0]] = $data[1];
        }
        fclose($fh);
    }
    if (!array_key_exists($country, $callingCodes))
        return '';

    $code = $callingCodes[$country];

    $phone = preg_replace('/[^0-9]/', '', $phone);
    $len = strlen($phone);
    if ($len > 16) // too long, will be adding a + later.
        return '';

    // for NANP
    if ($code == 1) {
        // 10 +1 followed by 10 digits
        // if it starts with 1, strip the leading one, no area code starts with 1.
        if ($len == 11 && substr($phone, 0, 1) == '1')
            return '+' . $phone;

        if ($len != 10) // not a NANP xxx-xxx-xxxx number (minus the - cleaned above in preg_replace)
            return '';

        return '+1' . $phone;
    }

    if (!str_starts_with($phone, $code)) {
        $phone = $code . $phone;
    }
    $phone = '+' . $phone;
    $len = strlen($phone);
    if ($len < 10 || $len > 17) // the + doesn't count, only the digits
        return ''; // illegal length

    // rest of the world, presume the number has the country code on it already
    return $phone;
}

// return a eyeslash toggle style password field
function eyepwField($id, $name, $width = 40, $placeholder = '', $tabIndex = -1) {
    $html = <<<EOS
<input class='form-control-sm' type='password' id='$id' name='$name' size="$width" autocomplete="off" required 
    tabindex="$tabIndex" placeholder="$placeholder"/>
<i class='bi bi-eye-slash' id='toggle_$id' style="margin-left: -30px;"></i>
EOS;
    return $html;
}