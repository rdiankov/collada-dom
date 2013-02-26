<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsDocumentation extends _typedData
{
  function xsDocumentation()
  {
    $this->type[] = "xsDocumentation";
    parent::_typedData();
  }
}

?>