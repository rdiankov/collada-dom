<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsEnumeration extends _elementSet
{
  function xsEnumeration()
  {
    $this->_addAttribute( 'value', array( 'type' => 'xs:integer' ) );
	
	$this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
	
    $this->type[] = 'xsEnumeration';
    parent::_typedData();
  }
}

?>