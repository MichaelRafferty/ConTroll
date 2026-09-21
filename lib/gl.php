<?php
// gl - anything to do with the PHP side of the general ledger so it can be used by multiple modules
// getGL - get the GL config
function getGL() {
    $gl = null;
    $glNums = [];
    $glLabels = [];

    $glQ = <<<EOS
SELECT *
FROM gl
WHERE active = 'Y'
ORDER BY sortOrder;
EOS;
    $glR = dbQuery($glQ);
    if ($glR !== false) {
        $gl = array ();
        while ($glrow = $glR->fetch_assoc()) {
            $gl[] = $glrow;
            $glNums[] = $glrow['glNum'];
            $glLabels[$glrow['glNum']] = $glrow['glLabel'];
        }
        $glR->free();
        if (count($gl) == 0) {
            $gl = null;
        }
    }
    return (array($gl, $glNums, $glLabels));
}
