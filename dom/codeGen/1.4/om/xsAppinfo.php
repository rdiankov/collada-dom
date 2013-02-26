<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsAppinfo extends _typedData
{
  function xsAppinfo()
  {
    $this->type[] = "xsAppinfo";
    parent::_typedData();
  }
}

?>