<?php
# Copyright 2013 Mike Thorn (github: WasabiVengeance). All rights reserved.
# Use of this source code is governed by a BSD-style
# license that can be found in the LICENSE file.

class jvc_controller
{
	function __construct($name,$path)
	{
		$this->name = $name;
		$this->path = $path;
	}
	
	function __call($view,$p)
	{
		global $__jvc;
		jvc::log('calling view '.$view);
		
		array_push($__jvc['parameters'],$p);
		$view_file = $this->path.'views/'.$view.'.php';
		if(file_exists($view_file))
		{
			$orig_return_count = $__jvc['return_count'];
			include($view_file);
			$new_return_count  = $__jvc['return_count'];
			
			if($orig_return_count < $new_return_count)
			{
				$__jvc['return_count']--;
				return array_shift($__jvc['returns']);
			}
		}
		else
		{
			throw new Exception('JVC: Cannot find view file: '.$view_file);
		}
		array_shift($__jvc['parameters']);
	}
		
	public function view_return($data)
	{
		global $__jvc;
		$__jvc['returns'][] = $data;
		$__jvc['return_count']++;
	}
}

?>