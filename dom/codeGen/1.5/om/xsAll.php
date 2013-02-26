<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsAll extends _elementSet
{
  function xsAll()
  {
    $this->_addElement( 'xsElement', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsAttribute', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    
    $this->type[] = "xsAll";
    parent::_elementSet();
  }

  function addAllElement( & $e )
  {
    $this->addElement( $e );
  }  
}

?>