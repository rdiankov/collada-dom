<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class _type
{
  var $type = array();
  
  function _type()
  {
    $this->type[] = "MotherOfAllTypes";
  }
  
  function isOfType( $type )
  {
    return( $type == $this->type[ count( $this->type ) - 1 ] );
  }
  
  function getType()
  {
    $type = "NULL";
    if ( count( $this->type ) > 0 ) { $type = $this->type[ 0 ]; }
    return $type;
  }
  
  function isAncestor( $type )
  {
    return in_array( $type, $this->type );
  }
}

class _typedData extends _type
{
  var $data;

  var $attributeMeta = array();
  var $attributes = array();
  
  function _typedData()
  {
    $this->type[] = "TypedData";
    parent::_type();
  }
  
  function _addAttribute( $name, $meta )
  {
    $this->attributeMeta[ $name ] = $meta;
  }
  
  function setAttribute( $name, $value )
  {
    // Make sure we know about the attribute before setting it
    if ( isset( $this->attributeMeta[ $name ] ) )
    {
      $this->attributes[ $name ] = $value;
    }
  }

  function getAttribute( $name )
  {
    $val = "";
    if ( isset( $this->attributeMeta[ $name ] ) && isset( $this->attributes[ $name ] ) ) { 
		$val = $this->attributes[ $name ]; 
	}
    return $val;
  }
  
  function & getAttributes()
  {
    return $this->attributes;
  }

  function set( & $buffer )
  {
    $this->data = $buffer;
  }
  
  function append( & $buffer )
  {
    $this->data .= $buffer;
  }
  
  function get()
  {
    return $this->data;
  }
}

class _elementSet extends _typedData
{
  var $elementMeta = array();
  var $elements = array();
  
  function _elementSet()
  {
    $this->_addAttribute( 'minOccurs', array( 'type' => 'xs:integer' ) );
    $this->setAttribute( 'minOccurs', '1' );
    $this->_addAttribute( 'maxOccurs', array( 'type' => 'xs:integer' ) );
    $this->setAttribute( 'maxOccurs', '1' );

    $this->type[] = "ElementSet";
    parent::_typedData();
  }
  
  function _addElement( $name, $attrs )
  {
    $this->elementMeta[ $name ] = $attrs;
  }

  function addElement( & $e )
  {
    if ( in_array( $e->getType(), array_keys( $this->elementMeta ) ) )
    {
      $this->elements[] = & $e;
    } else
    {
		print "Invalid element ". $e->getType() ."in ". $this->getType() ."\n";
      $this->log( "WARN: " . $e->getType() . " not a valid member of " . $this->getType() );
    }
  }
  
  function & getElements()
  {
    return $this->elements;
  }
  
  function & getElementsByType( $type )
  {
    $list = array();
    for( $i=0; $i<count( $this->elements ); $i++ )
    {
      if ( $this->elements[$i]->getType() == $type )
      {
        $list[] = & $this->elements[$i];
      }
    }
    return $list;
  }
  
  function setElement( $name, & $value )
  {
    $this->elements[ $name ] = $value;
  }
  
  function exists( $name )
  {
    return isset( $this->elements[ $name ] );
  }
  
  function delete( $name )
  {
    unset( $this->elements[ $name ] );
  }
  
  function _delete( $name )
  {
    $this->delete( $name );
    unset( $this->elementMeta[ $name ] );
  }
  
  function getCount()
  {
    return count( $this->elements );
  }

  function findTypeByName( $name )
  {
  	for ( $i = 0; $i < count( $GLOBALS['_globals']['complex_types'] ); $i++ ) {
      if ( !strcmp($name, $GLOBALS['_globals']['complex_types'][$i]->getAttribute('name') ) ) {
        return $GLOBALS['_globals']['complex_types'][$i];
      }
    }
  	for ( $i = 0; $i < count( $GLOBALS['_globals']['simple_types'] ); $i++ ) {
      if ( !strcmp($name, $GLOBALS['_globals']['simple_types'][$i]->getAttribute('name') ) ) {
        return $GLOBALS['_globals']['simple_types'][$i];
      }
    }
  }

}

?>