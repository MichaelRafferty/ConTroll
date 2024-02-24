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

function drawBug($cols) {
    global $db_ini;

    $textCols = $cols - 1;
    echo <<<EOS
        <div class="col-sm-$textCols">
            <p>
            Powered by ConTroll. Copyright 2015-2024, Michael Rafferty.</br>
            <img src="/lib/apglv3-bug.png"> ConTroll is freely available for use under the GNU Affero General Public License, Version 3.
            See the <a href="https://github.com/MichaelRafferty/ConTroll/blob/master/README.md" target="_blank">ConTroll ReadMe file</a>.
            </p>
        </div>
        <div class="col col-sm-1">
            <img src="/lib/ConTroll-bug.png" alt="ConTroll Logo">
        </div>
EOS;
}
