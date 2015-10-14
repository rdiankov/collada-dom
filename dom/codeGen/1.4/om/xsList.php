<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsList extends _typedData
{
  var $minLength;
  var $maxLength;
  
  function xsList()
  {
    global $MAX_ARRAY_LENGTH;

    $this->minLength = 0;
    $this->maxLength = $MAX_ARRAY_LENGTH;
    
    $this->_addAttribute( 'itemType', array( 'type' => 'xs:string' ) );
    $this->setAttribute( 'itemType', 'TypedData' );

    $this->type[] = 'xsList';
    parent::_typedData();
  }
  
  // To save the heavyweight object-per-data-point approach, allow a list type
  // to parse the buffer into a single array
  function set( & $buffer )
  {
    eval( '$type = new ' . $this->getAttribute( 'itemType' ) . '();' );
    $this->data = & $type->parse( $buffer );

/*    for( $i=0; trim( $buffer ) != "" && $i<$this->maxLength; $i++ )
    {
      eval( '$this->data[ $i ] = new ' . $this->getAttribute( 'itemType' ) . '();' );
      $this->data[ $i ]->set( $buffer );
    }*/
  }

  function getCount()
  {
    return count( $this->data );
  }
}

?>