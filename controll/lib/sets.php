<?php
// define admin auth sets in a single place, so they are the same everywhere.

function get_admin_sets(): array
{
    $sets = array(
        'base' => array('overview'),
        'admin' => array('admin','people-old'),
        'comp_entry' => array('badge', 'search'),
        'registration' => array('people', 'registration', 'search', 'badge'),
        'reg_admin' => array('reg_admin', 'reports', 'coupon'),
        'artshow_admin' => array('people', 'artist', 'artshow', 'art_control', 'art_sales', 'search', 'reports', 'vendor'),
        'artshow' => array('art_control', 'search'),
        'atcon' => array('monitor', 'atcon', 'atcon_checkin', 'atcon_register'),
        'exhibits' => array('people', 'search', 'reports', 'exhibitor'),
        'club' => array('club', 'reports', 'search', 'people'),
        'virtual' => array('virtual')
    );

    return $sets;
}
