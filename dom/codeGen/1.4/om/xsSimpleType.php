<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

require_once( 'src/TypeMeta.php' );

class xsSimpleType extends _elementSet
{
  function xsSimpleType()
  {
    $this->_addElement( 'xsRestriction', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsExtension', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsList', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsUnion', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    
    $this->_addElement( 'xsAnnotation', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );

    $this->_addAttribute( 'name', array( 'type' => 'xs:string' ) );

    $this->type[] = 'xsSimpleType';
    parent::_elementSet();
  }
  
  function & generate()
  {
    $vars = array();
    $e = $this->getElements();
    $generator = new TypeMeta();
    
    $generator->setType( $this->getAttribute( 'name' ) );
    
    $a = $this->getElementsByType( 'xsAnnotation' );
    if ( count( $a ) > 0 )
    {
      //print "found annotation for ". $this->getAttribute( 'name' ) ."!\n";
      $d = $a[0]->getElementsByType( 'xsDocumentation' );
      if ( count( $d ) > 0 )
      {
		//print "found documentation for ". $this->getAttribute( 'name' ) ."!\n";
        $generator->setDocumentation( $d[0]->get() );
      }
      $ap = $a[0]->getElementsByType( 'xsAppinfo' );
	  if ( count( $ap ) > 0 )
	  {
	  	$generator->setAppInfo( $ap[0]->get() );
	  }
    }
    
    $idx = 0;
    if ( $e[$idx]->getType() == 'xsAnnotation' ) {
		$idx = 1;
    }
    if ( $e[$idx]->getType() == 'xsRestriction' || $e[$idx]->getType() == 'xsExtension' )
    {
      $generator->setIsExtension( $e[$idx]->getType() == 'xsExtension' );
    
      // Set base class
      $generator->setBase( $e[$idx]->getAttribute( 'base' ) );
      
      // Look for enums
      $enums = $e[$idx]->getElementsByType( 'xsEnumeration' );
      for( $i=0; $i<count( $enums ); $i++ )
      {
        $generator->addEnum( $enums[$i]->getAttribute( 'value' ) );
        $an = $enums[$i]->getElementsByType('xsAnnotation');
        if ( count( $an ) > 0 ) {
			$doc = $an[0]->getElementsByType( 'xsDocumentation' );
			if ( count( $doc ) > 0 ) {
				$generator->addEnumDoc( $i, $doc[0]->get() );
			}
			$ap = $an[0]->getElementsByType( 'xsAppinfo' );
			if ( count( $ap ) > 0 )
			{
	  			$generator->addEnumAppInfo( $i, $ap[0]->get() );
			}
        }
      }
      
      // Look for max/mins
      $array_limits = array();
      $min = $e[$idx]->getElementsByType( 'xsMinLength' );
      $max = $e[$idx]->getElementsByType( 'xsMaxLength' );
      $minIn = $e[$idx]->getElementsByType( 'xsMinInclusive' );
      $maxIn = $e[$idx]->getElementsByType( 'xsMaxInclusive' );
      $minEx = $e[$idx]->getElementsByType( 'xsMinExclusive' );
      $maxEx = $e[$idx]->getElementsByType( 'xsMaxExclusive' );
      
      if ( count( $min ) > 0 )
      {
        $generator->setRestriction( 'minLength', $min[0]->getAttribute( 'value' ) );
      }
      
      if ( count( $max ) > 0 )
      {
        $generator->setRestriction( 'maxLength', $max[0]->getAttribute( 'value' ) );
      }
      
      if ( count( $minIn ) > 0 )
      {
        $generator->setRestriction( 'minInclusive', $minIn[0]->getAttribute( 'value' ) );
      }
      
      if ( count( $maxIn ) > 0 )
      {
        $generator->setRestriction( 'maxInclusive', $maxIn[0]->getAttribute( 'value' ) );
      }
      
      if ( count( $minEx ) > 0 )
      {
        $generator->setRestriction( 'minExclusive', $minEx[0]->getAttribute( 'value' ) );
      }
      
      if ( count( $maxEx ) > 0 )
      {
        $generator->setRestriction( 'maxExclusive', $maxEx[0]->getAttribute( 'value' ) );
      }
    } else if ( $e[$idx]->getType() == 'xsList' )
    {
      //$extends = "xsList";
      $itemType = $e[$idx]->getAttribute( 'itemType' );
      $generator->setListType( $itemType );
      $generator->bag['isArray'] = true;
    } 
    else if ( $e[$idx]->getType() == 'xsUnion' ) {
		$generator->setUnionMembers( $e[$idx]->getAttribute( 'memberTypes' ) );
    }
    else
    {
      $this->log( "WARN: unexpected element in xsSimpleType code generation" );
    }

    $meta = & $generator->getMeta();
    return $meta;
  }
}


?>