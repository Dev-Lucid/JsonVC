<?php
# Copyright 2013 Mike Thorn (github: WasabiVengeance). All rights reserved.
# Use of this source code is governed by a BSD-style
# license that can be found in the LICENSE file.

global $__jvc;

class jvc
{
	public static function init($base_dir,$config_file)
	{
		global $__jvc;
		
		ob_start();
		
		$__jvc = array(
			'parameters'=>array(),
			'paths'=>array(
				'base'=>$base_dir,
			),
			'response'=>array(
				'title'=>'',
				'description'=>'',
				'keywords'=>'',
				'js'=>'',
				'prepend'=>array(),
				'append'=>array(),
				'replace'=>array(),
			)
		);
		
		include(__DIR__.'/jvc_controller.php');
	}
	
	public static function get_response($position)
	{
		global $__jvc;
		if(is_array($__jvc['response'][$position]))
		{
			$content = '';
			$content .= (isset($__jvc['response'][$position]['prepend']))?$__jvc[$position]['prepend']:'';
			$content .= (isset($__jvc['response'][$position]['replace']))?$__jvc[$position]['prepend']:'';
			$content .= (isset($__jvc['response'][$position]['append']))?$__jvc[$position]['prepend']:'';
			return $content;
		}
		else
		{
			return $__jvc['response'][$position];
		}
	}
	
	public static function set_response($position,$content=null,$mode='replace')
	{
		global $__jvc;
		
		if(is_null($content))
		{
			$content = ob_get_clean();
			ob_start();
		}
		
		switch($position)
		{
			case 'title': case 'description': case 'keywords': case 'js':
				$__jvc['response'][$position] = $content;
				break;
			default:
				if(!isset($__jvc['response'][$position][$mode]))
				{
					$__jvc['response'][$position][$mode] = '';
				}
				if($mode == 'prepend')
				{
					$__jvc['response'][$position][$mode] = $content . $__jvc[$position][$mode];
				}
				else if($mode == 'append')
				{
					$__jvc['response'][$position][$mode] .= $content;
				}
				else
				{
					$__jvc['response'][$position][$mode] = $content;
				}
				break;
		}
	}
	
	public static function controller($name)
	{
		global $__jvc;
		$class = 'jvc_controller_'.$name;
		$path  = $__jvc['paths']['base'].'/controllers/'.$name.'/';
		if(!class_exists($class))
		{
			if(file_exists($path.$name.'.php'))
			{
				include($path.$name.'.php');
			}
			else
			{
				throw new Exception('JVC: Could not find controller file to load: '.$path.$name.'.php');
			}
			if(!class_exists($class))
			{
				throw new Exception('JVC: Controller was loaded, but properly named class was not found: '.$path.$name.'.php');
			}
		}
		$controller = new $class($name,$path);
		return $controller;
	}
	
	public static function process()
	{
		global $__jvc;
		$url  = explode('/',$_SERVER['REDIRECT_URL']);
		if(count($url) > 1)
		{
			$view = array_pop($url);
			$cont = array_pop($url);
			
			$cont = jvc::controller($cont);
			$cont->$view();
			 
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'yes')
			{
				jvc::deinit(true);
				dbm::deinit();
			}
		}
	}
	
	public static function deinit($do_ajax=false)
	{
		global $__jvc;
		if($do_ajax)
		{
			exit(json_encode($__jvc['response']));
		}
	}
}

?>