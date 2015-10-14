<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsAny extends _typedData
{
  function xsAny()
  {
    $this->_addAttribute( 'namespace', array( 'type' => 'xs:anyURI' ) );
    $this->_addAttribute( 'processContents', array( 'type' => 'xs:string' ) );

	$this->_addAttribute( 'minOccurs', array( 'type' => 'xs:integer' ) );
    $this->setAttribute( 'minOccurs', '1' );
    $this->_addAttribute( 'maxOccurs', array( 'type' => 'xs:integer' ) );
    $this->setAttribute( 'maxOccurs', '1' );
    
    $this->type[] = 'xsAny';
    parent::_typedData();
  }
}

?>