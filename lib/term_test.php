<?php
//  test.php - library of modules to insert a stub payment mechanism
// uses config variables:
// [cc]
// env="sandbox" or demo=1 or it will fail
// [reg]
// test=1 or it will fail
//

require_once("global.php");

function createDeviceCode($name, $locationId, $useLogWrite = false) : array {
    $term = array('name' => $name, 'location_id' => $locationId, 'product_type' => 'Test_Terminal', 'code' => $name,
        'id' => 'id_' . $name, 'pair_by' => '2040-12-31 23:59:59', 'created_at' => date_create('now')->format('Y-m-d H:i:s'),
        'status' => 'UNPAIRED', 'status_changed_at' => date_create('now')->format('Y-m-d H:i:s'));
    return $term;
}
