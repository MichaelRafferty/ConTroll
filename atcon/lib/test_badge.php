<?php
require("conreg.php");


$test_base = array(
    'full_name' => "Adult Test",
    'category'  => 'test',
    'age'       => 'adult',
    'type'      => 'full',
    'id'        => 'test',
    'badge_name'     => 'Test Standard'
);

$test_standard = $test_base; $test_standard['category']='standard';
$test_nobadgename = $test_base; $test_nobadgename['badge_name']="";
$test_youth = $test_base; $test_youth['age']='youth';
$test_child = $test_base; $test_child['age']='child';
$test_kit = $test_base; $test_kit['age']='kit';
$test_long = $test_base; $test_long['badge_name']="badge 1234567890 long!";
$test_oneday = $test_base; $test_oneday['type']='oneday'; $test_oneday['day']="TEST";

$label_0 = $test_base; $label_0['badge_name'] = 'Printer 0';
$label_1 = $test_base; $label_1['badge_name'] = 'Printer 1';
$label_2 = $test_base; $label_2['badge_name'] = 'Printer 2';
$label_3 = $test_base; $label_3['badge_name'] = 'Printer 3';
$label_4 = $test_base; $label_4['badge_name'] = 'Printer 4';
$label_5 = $test_base; $label_5['badge_name'] = 'Printer 5';
$label_old1 = $test_base; $label_old1['badge_name'] = 'Old 1';
$label_old2 = $test_base; $label_old2['badge_name'] = 'Old 2';

/* Print Demo Badges */
$file_full = init_file('0');
write_badge($test_standard, $file_full, '0');
var_dump(print_badge('0', $file_full));
rename($file_full, "demo/test_standard.ps");

$file_full = init_file('0');
write_badge($test_nobadgename, $file_full, '0');
var_dump(print_badge('1', $file_full));
rename($file_full, "demo/test_nobadgename.ps");
/*
$file_full = init_file('0');
write_badge($test_youth, $file_full, '0');
var_dump(print_badge('1', $file_full));
rename($file_full, "demo/test_youth.ps");

$file_full = init_file('0');
write_badge($test_child, $file_full, '0');
var_dump(print_badge('1', $file_full));
rename($file_full, "demo/test_child.ps");

$file_full = init_file('0');
write_badge($test_kit, $file_full, '0');
var_dump(print_badge('1', $file_full));
rename($file_full, "demo/test_kit.ps");

$file_full = init_file('0');
write_badge($test_long, $file_full, '0');
var_dump(print_badge('1', $file_full));
rename($file_full, "demo/test_long.ps");

$file_full = init_file('0');
write_badge($test_oneday, $file_full, '0');
var_dump(print_badge('1', $file_full));
rename($file_full, "demo/test_oneday.ps");
/**/

/* print printer labels * /

$file_full = init_file('0');
write_badge($label_0, $file_full, '0');
var_dump(print_badge('0', $file_full));
unlink($file_full);

$file_full = init_file('1');
write_badge($label_1, $file_full, '1');
var_dump(print_badge('1', $file_full));
unlink($file_full);

$file_full = init_file('2');
write_badge($label_2, $file_full, '2');
var_dump(print_badge('2', $file_full));
unlink($file_full);

$file_full = init_file('3');
write_badge($label_3, $file_full, '3');
var_dump(print_badge('3', $file_full));
unlink($file_full);

$file_full = init_file('4');
write_badge($label_4, $file_full, '4');
var_dump(print_badge('4', $file_full));
unlink($file_full);
/**/
?>
