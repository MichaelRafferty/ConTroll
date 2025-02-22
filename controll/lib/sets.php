<?php
// define admin auth sets in a single place, so they are the same everywhere.

function get_admin_sets(): array
{
    $sets = array(
        'gen_reports' => array('reports', 'gen_rpts'),
        'admin' => array('admin'),
        'comp_entry' => array('badge'),
        'stats' => array('overview','monitor','atcon'),
        'registration' => array('people', 'registration', 'badge','reports', 'gen_rpts'),
        'lookup' => array('lookup'),
        'reg_admin' => array('reg_admin', 'reports', 'gen_rpts', 'coupon'),
        'artshow_admin' => array('people', 'art_control', 'reports', 'gen_rpts', 'exhibitor'),
        'finance' => array('finance', 'reports', 'gen_rpts'),
        'exhibits' => array('people', 'reports', 'gen_rpts', 'exhibitor'),
        'club' => array('club', 'reports', 'gen_rpts', 'people'),
        'virtual' => array('virtual')
    );

    return $sets;
}
