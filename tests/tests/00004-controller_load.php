<?
$controller = jvc::controller('00004');
jvc::set_response('keywords',get_class($controller));
file_put_contents($output_path,json_encode($__jvc['response']));
?>