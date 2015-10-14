<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsSimpleContent extends _elementSet
{
  function xsSimpleContent()
  {
    $this->_addElement( 'xsRestriction', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsExtension', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );

//    $this->_addAttribute( 'name', array( 'type' => 'xs:string' ) );

    $this->type[] = 'xsSimpleContent';
    parent::_elementSet();
  }
}

?>