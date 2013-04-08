<?
$controller = jvc::controller('00006');
$controller->do_test();
file_put_contents($output_path,json_encode($__jvc['response']));
?>