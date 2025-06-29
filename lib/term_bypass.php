<?php
//  bypass.php - library of modules to short circuit terminals for testing of code
// uses config variables:
// [cc]
// type=bypass - selects that reg is not to deal with payment

function createDeviceCode($name, $locationId, $useLogWrite = false): array {
    $term = array('name' => $name, 'location_id' => $locationId, 'product_type' => 'Bypass_Terminal', 'code' => $name,
                  'id' => 'id_' . $name, 'pair_by' => '2040-12-31 23:59:59', 'created_at' => date_create('now')->format('Y-m-d H:i:s'),
                  'status' => 'UNPAIRED', 'status_changed_at' => date_create('now')->format('Y-m-d H:i:s'));
    return $term;
}

