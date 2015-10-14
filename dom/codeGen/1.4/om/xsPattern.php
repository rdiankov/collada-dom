<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsPattern extends _elementSet
{
  function xsPattern()
  {
    $this->_addAttribute( 'value', array( 'type' => 'xs:string' ) );
	
	$this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
	
    $this->type[] = 'xsPattern';
    parent::_typedData();
  }
}

?>