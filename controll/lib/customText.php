<?php
// customText - get / update - used by Controll bvack end

// getCustomText - return the custom text for a menu item
function getCustomText($page) {
    switch ($page) {
        case 'reg-admin':
            $apps = "'portal','onlinereg','atcon','controll'";
            break;
        case 'exhibitor':
            $apps = "'exhibitor'";
            break;
        default:
            $response['error'] = 'getCustomText called from invalid application';
            ajaxSuccess($response);
            exit();
    }

    // build missing custom text
    $buildSQL = <<<EOS
INSERT INTO controllTxtItems(appName, appPage, appSection, txtItem, contents)
SELECT a.appName, a.appPage, a.appSection, a.txtItem,
    CONCAT('Controll-Default: This is ', a.appName, '-', a.appPage, '-', a.appSection, '-', a.txtItem,
        '<br/>Custom HTML that can replaced with a custom value in the ConTroll Admin App under RegAdmin/Edit Custom Text.<br/>',
        'Default text display can be suppressed in the configuration file.')
FROM controllAppItems a
LEFT OUTER JOIN controllTxtItems t ON (a.appName = t.appName AND a.appPage = t.appPage AND a.appSection = t.appSection AND a.txtItem = t.txtItem)
WHERE t.contents is NULL;
EOS;
    $numRows = dbCmd($buildSQL);
    if ($numRows > 0) {
        error_log("Info: $numRows rows of new default customText inserted");
    }
    $customTextSQL = <<<EOS
SELECT ROW_NUMBER() OVER (ORDER BY t.appName, t.appPage, t.appSection, t.txtItem) AS rownum,
    t.appName, t.appPage, t.appSection, t.txtItem, t.contents, i.txtItemDescription
FROM controllTxtItems t
JOIN controllAppItems i ON (t.appName = i.appName AND t.appPage = i.appPage AND t.appSection = i.appSection AND t.txtItem = i.txtItem)
WHERE t.appName IN ($apps)
ORDER BY appName, appPage, appSection, txtItem;
EOS;

    $result = dbQuery($customTextSQL);
    $customText = array();
    while ($row = $result->fetch_assoc()) {
        array_push($customText, $row);
    }
    $result->free();
    
    return $customText;
}

function updateCustomText($tableData) {
    $updsql = <<<EOS
UPDATE controllTxtItems
SET contents = ?
WHERE appName = ? AND appPage = ? AND appSection = ? AND txtItem = ?;
EOS;
    // because of the defaults, it's all updates
    foreach ($tabledata as $row) {
        $numrows = dbSafeCmd($updsql, 'sssss',
                             array($row['contents'], $row['appName'], $row['appPage'], $row['appSection'], $row['txtItem']));
        $updated += $numrows;
    }
    return $updated;
}