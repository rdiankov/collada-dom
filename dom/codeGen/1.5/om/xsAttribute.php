<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsAttribute extends _elementSet
{
  function xsAttribute()
  {
    $this->_addAttribute( 'name', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'type', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'use', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'default', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'ref', array( 'type' => 'xs:string' ) );
    
    $this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );

    $this->type[] = 'xsAttribute';
    parent::_typedData();
  }
}

?>