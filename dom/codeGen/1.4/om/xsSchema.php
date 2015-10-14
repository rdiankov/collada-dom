<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsSchema extends _elementSet
{
  function xsSchema()
  {
    $this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsElement', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsSimpleType', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsComplexType', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsGroup', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsImport', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );

    $this->_addAttribute( 'targetNamespace', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'elementFormDefault', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'xmlns:xs', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'xmlns', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'version', array( 'type' => 'xs:string' ) );

    $this->type[] = 'xsSchema';
    parent::_elementSet();
  }
}

?>