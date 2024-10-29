<?php

function renderHtml($arr, $html){
	$template = $html;
	preg_match_all("#\<loop\>(.*?)\<\/loop\>#s", $template, $template_loops);
	$template_loop_string = "";
	if( isset($template_loops[1][0]) && !empty($template_loops[1][0]) )
	{
		$template_loop = $template_loops[1][0];
		foreach ($arr as $p)
		{
			$template_loop_ = $template_loop;
			foreach($p as $kitem => $item)
			{
				$template_loop_ = preg_replace('#\{\{\$'.$kitem.'\}\}#',  $item, $template_loop_);
			}
			$template_loop_string .= $template_loop_;
		}
		$template = str_replace($template_loops[0][0], $template_loop_string, $template);
	}
	else
	{
		$template_ = $html;
		foreach($arr as $kitem => $item)
		{
			if(!is_array($item) && !is_object($item))
			{
				$template_ = preg_replace('#\{\{\$'.$kitem.'\}\}#',  $item, $template_);
			}
		}
		$template = $template_;
	}
	return $template;
}

	