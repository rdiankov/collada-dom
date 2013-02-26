<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsMaxInclusive extends _typedData
{
  function xsMaxInclusive()
  {
    $this->_addAttribute( 'value', array( 'type' => 'xs:float' ) );

    $this->type[] = 'xsMaxInclusive';
    parent::_typedData();
  }
}

?>