<?php
function getTeaserImage ($text,$return = 'path') {
	$regEx  = "/<img[^>]*?>.*?/i";
    $match  = array();
    preg_match($regEx, $text, $match);
	
	$regEx = "/(src)(=)(['\"]?)([^\"']*)(['\"]?)/i";
    $img = array();
    preg_match($regEx, $match[0], $img);
    
    if ($return == 'path') {
	    return $img[4];
    } else {
    	return $match[0];
    }
}
?>