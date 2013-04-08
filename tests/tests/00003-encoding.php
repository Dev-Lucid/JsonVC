<?
jvc::set_response('author','this is a test of the escaping of \' various "quotes" and such\'s to make sure that the josn'."\n".' comes out okay');
file_put_contents($output_path,json_encode($__jvc['response']));
?>