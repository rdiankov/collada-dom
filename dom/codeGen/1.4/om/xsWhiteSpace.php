<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsWhiteSpace extends _typedData
{
  function xsWhiteSpace()
  {
    $this->_addAttribute( 'value', array( 'type' => 'xs:string' ) );

    $this->type[] = 'xsWhiteSpace';
    parent::_typedData();
  }
}

?>