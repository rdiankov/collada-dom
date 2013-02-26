<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsImport extends _typedData
{
  function xsImport()
  {
    $this->_addAttribute( 'namespace', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'schemaLocation', array( 'type' => 'xs:string' ) );

    $this->type[] = 'xsImport';
    parent::_typedData();
  }
}

?>