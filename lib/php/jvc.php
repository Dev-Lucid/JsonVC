<?php
# Copyright 2013 Mike Thorn (github: WasabiVengeance). All rights reserved.
# Use of this source code is governed by a BSD-style
# license that can be found in the LICENSE file.

global $__jvc;
$__jvc = array(
	'parameters'=>array(),
	'returns'=>array(),
	'return_count'=>0,
	
	'paths'=>array(
		'base'=>'',
	),
	'response'=>array(
		'title'=>'',
		'description'=>'',
		'keywords'=>'',
		'author'=>'',
		'js'=>'',
		'prepend'=>array(),
		'append'=>array(),
		'replace'=>array(),
	),
	
	'hooks'=>array(),
	
	'base_dir'=>'',
	'config_file'=>'',
);

class jvc
{
	function log($to_write)
	{
		global $__jvc;
		if(isset($__jvc['hooks']['log']))
		{
			$to_write=(is_object($to_write) || is_array($to_write))?print_r($to_write,true):$to_write;
			$__jvc['hooks']['log']('JVC: '.$to_write);
		}
	}
	
	function call_hook($hook,$p0=null,$p1=null,$p2=null,$p3=null,$p4=null,$p5=null,$p6=null)
	{
		global $__jvc;
		if(isset($__jvc['hooks'][$hook]))
			$__jvc['hooks'][$hook]($p0,$p1,$p2,$p3,$p4,$p5,$p6);
	}
	
	public static function init($config=array())
	{
		global $__jvc;
		
		foreach($config as $key=>$value)
		{
			if(is_array($value))
			{
				foreach($value as $subkey=>$subvalue)
				{
					if(is_numeric($subkey))
						$__jvc[$key][] = $subvalue;
					else
						$__jvc[$key][$subkey] = $subvalue;
				}

			}
			else
				$__jvc[$key] = $value;
		}
		
		ob_start();

		include_once(__DIR__.'/jvc_controller.php');
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
			case 'title': case 'description': case 'keywords': case 'js': case 'author':
				$__jvc['response'][$position] = (string)$content;
				break;
			default:
				if(!isset($__jvc['response'][$mode][$position]))
				{
					$__jvc['response'][$mode][$position] = '';
				}
				if($mode == 'prepend')
				{
					$__jvc['response'][$mode][$position] = (string)$content . $__jvc[$mode][$position];
				}
				else if($mode == 'append')
				{
					$__jvc['response'][$mode][$position] .= (string)$content;
				}
				else
				{
					$__jvc['response'][$mode][$position] = (string)$content;
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
			
			jvc::log('processing controller->view: '.$cont.'->'.$view);
			
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
			jvc::log('sending back json');
			header('Content-type: text/json');
			header('Content-type: application/json');
			jvc::call_hook('deinit');
			exit(json_encode($__jvc['response']));
		}
	}
}

?>