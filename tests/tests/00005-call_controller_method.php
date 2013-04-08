<?
$controller = jvc::controller('00005');
$controller->do_test();
file_put_contents($output_path,json_encode($__jvc['response']));
?>