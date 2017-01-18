<?php

namespace AsyncWeb\Text;


class XML{
	public static function GetInnerHtml( $node ) {
		// z php dokumentacie
		$innerHTML= ''; 
		$children = $node->childNodes; 
		foreach ($children as $child) { 
			$innerHTML .= $child->ownerDocument->saveXML( $child ); 
		} 

		return $innerHTML; 
	} 
}