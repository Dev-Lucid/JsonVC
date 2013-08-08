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
	

	'commands-pre-request'=>array(),
	'commands-request'=>array(),
	'commands-post-request'=>array(),
	'commands-pre-page'=>array(),
	'commands-post-page'=>array(),

	
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
		
		# check for controller commands either in the redirect url
		# or in the _escaped_fragment_ get var
		
		$url  = explode('/',$_SERVER['REDIRECT_URL']);
		if(count($url) > 1)
		{
			$view = array_pop($url);
			$cont = array_pop($url);
			
			$__jvc['commands-request'][] = $cont.'/'.$view;
		}
		
		if(isset($_REQUEST['_escaped_fragment_']))
		{
			$parts = explode('--',$_REQUEST['_escaped_fragment_']);
			
			# determine controller
			$parts[0] = explode('-',$parts[0]);
			$__jvc['commands-request'][] = $parts[0][0].'/'.$parts[0][1];
			
			# determine request parameters
			$parts[1] = explode('|',$parts[0]);
			for($i=0;$i<count($parts[1]);$i++)
			{
				$_REQUEST[$parts[1][$i]] = $parts[1][$i+1];
			}
		}

		include_once(__DIR__.'/jvc_controller.php');
		
		jvc::log('init complete');
	}
	
	public static function get_response($position)
	{
		global $__jvc;
		jvc::log('get response called for position '.$position);
		switch($position)
		{
			case 'title': case 'description': case 'keywords': case 'js': case 'author':
				return $__jvc['response'][$position];
				break;
			default:
				#jvc::log('found a content area. compiling content');
				$content = '';
				$content .= (isset($__jvc['response']['prepend'][$position]))?$__jvc['response']['prepend'][$position]:'';
				$content .= (isset($__jvc['response']['replace'][$position]))?$__jvc['response']['replace'][$position]:'';
				$content .= (isset($__jvc['response']['append'][$position]))?$__jvc['response']['append'][$position]:'';
				return $content;
				break;
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
				if(!isset($__jvc['response'][$position]))
				{
					$__jvc['response'][$position] = '';
				}
				$__jvc['response'][$position] .= (string)$content;
				break;
			default:
				if(!isset($__jvc['response'][$mode][$position]))
				{
					$__jvc['response'][$mode][$position] = '';
				}
				if($mode == 'prepend')
				{
					$__jvc['response'][$mode][$position] = (string)$content . $__jvc['response'][$mode][$position];
				}
				else if($mode == 'append')
				{
					$__jvc['response'][$mode][$position] .= (string)$content;
				}
				else
				{
					$__jvc['response'][$mode][$position] .= (string)$content;
				}
				break;
		}
	}
	
	public static function controller($name)
	{
		global $__jvc;
		jvc::log('controller instantiate: '.$name);
		jvc::call_hook('pre-controller',$name);
			
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
		jvc::call_hook('post-controller',$controller);
		return $controller;
	}
	
	public static function process($command_list=null)
	{
		global $__jvc;
		
		if(is_null($command_list))
		{
			jvc::process('pre-request');
			jvc::process('request');
			jvc::process('post-request');
			
			if(isset($_REQUEST['ajax']) && $_REQUEST['ajax'] === 'yes')
			{
				jvc::deinit(true);
			}
		}
		else
		{
			foreach($__jvc['commands-'.$command_list] as $command)
			{
				jvc::process_command($command);
			}
		}
		
		if(isset($_REQUEST['_escaped_fragment_']))
		{
			print_r($__jvc['response']);
			exit();
		}

	}
	
	public static function process_command($cont_view,$p0=null,$p1=null,$p2=null,$p3=null,$p4=null,$p5=null)
	{
		global $__jvc;
		list($cont,$view) = explode('/',$cont_view);
		if(isset($cont) && isset($view))
		{
			$cont = jvc::controller($cont);
			$cont->$view($p0,$p1,$p2,$p3,$p4,$p5);
		}
	}
	
	public static function deinit($do_ajax=false)
	{
		global $__jvc;
		jvc::log('deinit called, cleaning up');
		if($do_ajax)
		{
			jvc::log('sending back json');
			header('Content-type: text/json');
			header('Content-type: application/json');
			jvc::call_hook('deinit');
		
			try
			{
				exit(json_encode($__jvc['response']));
			}
			catch(Exception $e)
			{
				jvc::log(print_r(error_get_last(),true));
			}
		}
		jvc::call_hook('deinit');
	}
	
		
	public static function get_reset_buffer()
	{
		$to_return = ob_get_clean();
		ob_start();
		return $to_return;
	}
}

?>