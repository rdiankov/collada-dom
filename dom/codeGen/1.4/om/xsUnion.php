<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsUnion extends _typedData
{
  function xsUnion()
  {
    $this->_addAttribute( 'memberTypes', array( 'type' => 'xs:string' ) );

    $this->type[] = 'xsUnion';
    parent::_typedData();
  }
}

?>