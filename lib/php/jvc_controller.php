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
		
		array_push($__jvc['parameters'],$p);
		$view_file = $this->path.'/'.$view.'.php';
		if(file_exists($view_file))
		{
			include($view_file);
		}
		else
		{
			throw new Exception('JVC: Cannot find view file: '.$view_file);
		}
		array_shift($__jvc['parameters']);
	}
}

?>