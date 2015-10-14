<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsMaxLength extends _typedData
{
  function xsMaxLength()
  {
    $this->_addAttribute( 'value', array( 'type' => 'xs:integer' ) );

    $this->type[] = 'xsMaxLength';
    parent::_typedData();
  }
}

?>