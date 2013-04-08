<?
jvc::set_response('title','title testing');
file_put_contents($output_path,json_encode($__jvc['response']));
?>