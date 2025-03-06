<?php
// define admin auth sets in a single place, so they are the same everywhere.

function get_admin_sets(): array
{
    $sets = array(
        'gen_reports' => array('reports', 'gen_rpts'),
        'admin' => array('admin', 'search'),
        'comp_entry' => array('badge', 'search'),
        'stats' => array('overview', 'monitor','atcon'),
        'registration' => array('people', 'registration', 'badge', 'search', 'reports', 'gen_rpts'),
        'lookup' => array('lookup', 'search'),
        'reg_admin' => array('reg_admin', 'search', 'reports', 'gen_rpts', 'coupon'),
        'artshow_admin' => array('people', 'art_control', 'search', 'reports', 'gen_rpts', 'exhibitor'),
        'finance' => array('finance', 'search',  'reports', 'gen_rpts'),
        'exhibits' => array('people', 'search', 'reports', 'gen_rpts', 'exhibitor'),
        'club' => array('club', 'search', 'reports', 'gen_rpts', 'people'),
        'virtual' => array('virtual')
    );

    return $sets;
}
