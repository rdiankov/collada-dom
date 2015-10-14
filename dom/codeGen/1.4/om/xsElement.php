<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

require_once( 'src/ElementMeta.php' );
require_once( 'src/TypeMeta.php' );

class xsElement extends _elementSet
{
  function xsElement()
  {
    $this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsComplexType', array( 'minOccurs' => '0', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsSimpleType',  array( 'minOccurs' => '0', 'maxOccurs' => '1' ) );

    $this->_addAttribute( 'ref', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'name', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'type', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'abstract', array( 'type' => 'xs:bool' ) );
    $this->_addAttribute( 'substitutionGroup', array( 'type' => 'xs:string' ) );
//    $this->_addAttribute( 'maxOccurs', array( 'type' => 'xs:integer' ) );
//    $this->_addAttribute( 'minOccurs', array( 'type' => 'xs:integer' ) );

    $this->type[] = 'xsElement';
    parent::_elementSet();
  }
  
  function & generate( $element_context, & $global_elements )
  {
    $element_context[] = $this->getAttribute( "name" );
    print implode( ",", $element_context ) . "\n";
    
    // Get new factory
    $generator = new ElementMeta( $global_elements );
    
    // Load the class name and a context pre-fix (in case we're inside another element)
    $generator->setName( $this->getAttribute( 'name' ) );
    $generator->setContext( $element_context );
    $subGroup = $this->getAttribute( 'substitutionGroup' );
    if ( $subGroup != '' ) {
		//print "found a subGroup ". $subGroup ."!\n";
		$generator->setSubstitutionGroup( $subGroup );
		$generator->bag['ref_elements'][] = $subGroup;
    }
    $abstract = $this->getAttribute( 'abstract' );
    if ( $abstract != '' ) {
		$generator->setAbstract( $abstract );
    }
    
    // Extract any documentation for this node
    $a = $this->getElementsByType( 'xsAnnotation' );
    if ( count( $a ) > 0 )
    {
      $d = $a[0]->getElementsByType( 'xsDocumentation' );
      if ( count( $d ) > 0 )
      {
        $generator->setDocumentation( $d[0]->get() );
      }
      $ap = $a[0]->getElementsByType( 'xsAppinfo' );
	  if ( count( $ap ) > 0 )
	  {
	  	$generator->setAppInfo( $ap[0]->get() );
	  }
    }
    
    //******************************************************************************************/
    //$generator->setContentType( $this->getAttribute( 'type' ) );
    $type = $this->getAttribute( 'type' );
    $generator->bag['base_type'] = $type;
    //check if this type equals a complex type    
	//print "element ". $this->getAttribute( 'name' ) ." is of type ". $type ."!\n";
	if ( $type != "" ) {
		//print "complex types: " . count($GLOBALS['_globals']['complex_types']) . "\n";
		for ( $i = 0; $i < count( $GLOBALS['_globals']['complex_types'] ); $i++ ) {
			if ( !strcmp($type, $GLOBALS['_globals']['complex_types'][$i]->getAttribute('name') ) ) {
				//print "found a match for ". $type ."\n";
				$generator->setComplexType( true );
				$generator->bag['ref_elements'][] = $type;
				break;
			}
		}
		if ( !$generator->bag['complex_type'] ) {
			//wasn't a complex type that means it needs a content type
			$generator->setContentType( $type );
		}
	}
	//*******************************************************************************************/
		
    // Inspect the semantic structure of this node and extract the elements/attributes
    $temp = $this->getElementsByType( 'xsComplexType' );
    
    if ( count( $temp ) > 0 )
    {
      if ( $temp[0]->getAttribute( 'mixed' ) == 'true' )
      {
        $generator->setMixed( true );
        $generator->setContentType( 'ListOfInts' );
      }
      
      $content = $temp[0]; // Should only be one
      $this->generateComplexType( $content, $generator, $element_context ); 
	  if ( count( $generator->bag['elements'] ) == 0 ) {
		$generator->setIsEmptyContent( true );
	  }
	}
	else if ( count( $this->getElementsByType( 'xsSimpleType' ) ) > 0 ) {
		//inline simple type definition. right now handle as string but needs to be fixed
		$generator->bag['simple_type'] = new TypeMeta();
		$temp = $this->getElementsByType( 'xsSimpleType' );
		$this->generateSimpleType( $temp[0], $generator->bag['simple_type'] );
		if ( count( $generator->bag['simple_type']->bag['enum'] ) >0 ) {
			$generator->setContentType( $this->getAttribute( 'name' ) ."_type" );
		}
		else {
			$generator->setContentType( $generator->bag['simple_type']->bag['base'] );
		}
	}
		
    $meta = & $generator->getMeta();
    
    if ( count( $element_context ) == 1 )
    {
      $global_elements[ $element_context[0] ] = & $meta;
    }
    
    return $meta;
  }

  // Flatten choice/all/sequence groups into a single list of contained elements
  function flatten( & $element, & $generator, & $context, $maxOccurs )
  {
    //print "in flatten ";
    $e_list = $element->getElements();
    for( $i=0; $i<count( $e_list ); $i++ )
    {
      switch( $e_list[$i]->getType() )
      {
        case 'xsChoice':
			$generator->setHasChoice( true );
			$generator->addContentModel( 1, $e_list[$i]->getAttribute( 'minOccurs' ), $e_list[$i]->getAttribute( 'maxOccurs' ) );
			// Propagate the maxOccurs down through choice hierarchy (while flattening)
          $local_max = $e_list[$i]->getAttribute( 'maxOccurs' );
          if ( $maxOccurs == 'unbounded' || (is_int( $local_max ) && ($maxOccurs > $local_max)) )
          {
            $this->flatten( $e_list[$i], $generator, $context, $maxOccurs );
          } else
          {
            $this->flatten( $e_list[$i], $generator, $context, $local_max );
          }
          break;
        case 'xsSequence':
			$generator->addContentModel( 0, $e_list[$i]->getAttribute( 'minOccurs' ), $e_list[$i]->getAttribute( 'maxOccurs' ) );
			// Propagate the maxOccurs down through choice hierarchy (while flattening)
          $local_max = $e_list[$i]->getAttribute( 'maxOccurs' );
          if ( $maxOccurs == 'unbounded' || (is_int( $local_max ) && ($maxOccurs > $local_max)) )
          {
            $this->flatten( $e_list[$i], $generator, $context, $maxOccurs );
          } else
          {
            $this->flatten( $e_list[$i], $generator, $context, $local_max );
          }
          break;
        case 'xsAll':
			$generator->addContentModel( 3, $e_list[$i]->getAttribute( 'minOccurs' ), $e_list[$i]->getAttribute( 'maxOccurs' ) );
          // Propagate the maxOccurs down through choice hierarchy (while flattening)
          $local_max = $e_list[$i]->getAttribute( 'maxOccurs' );
          if ( $maxOccurs == 'unbounded' || (is_int( $local_max ) && ($maxOccurs > $local_max)) )
          {
            $this->flatten( $e_list[$i], $generator, $context, $maxOccurs );
          } else
          {
            $this->flatten( $e_list[$i], $generator, $context, $local_max );
          }
          break;
        case 'xsGroup':
			$generator->addContentModel( 2, $e_list[$i]->getAttribute( 'minOccurs' ), $e_list[$i]->getAttribute( 'maxOccurs' ) );
			$generator->addGroup( $e_list[$i] );
        case 'xsElement':
			$nm = $e_list[$i]->getAttribute( 'name' );
			if ( $nm == '' ) { $nm = $e_list[$i]->getAttribute( 'ref' ); }
			$generator->addContentModel( $nm, $e_list[$i]->getAttribute( 'minOccurs' ), $e_list[$i]->getAttribute( 'maxOccurs' ) );
          //print "found element!\n";
          // If a containing element/group has a maxOccurs > 1, then inherit it (will flag as array in code gen)
          if ( $maxOccurs == 'unbounded' || $maxOccurs > 1 )
          {
            $e_list[$i]->setAttribute( 'maxOccurs', $maxOccurs );
          }
          $generator->addElement( $e_list[$i], $context );
          break;
        case 'xsAttribute':
			//print "found attribute!\n";
          $generator->addAttribute( $e_list[$i] );
          break;
        case 'xsAny':
			print "found an any\n";
			$generator->addContentModel( 4, $e_list[$i]->getAttribute( 'minOccurs' ), $e_list[$i]->getAttribute( 'maxOccurs' ) );
          $generator->bag['has_any'] = true;
          break;
        default:
          break;
      }
    }
    $generator->addContentModel( 5, 0, 0 ); //END content model - There will be one extra on every element
  }
  
  //function that reads complex types.  will recurse complex type derived heirarchies.
  function generateComplexType( $content, & $generator, & $context ) {
	//print "in generatecomplextype\n";
	if ( count( $content->getElementsByType( 'xsSimpleContent' ) ) > 0 ) {
		//print "found simpleContent!\n";
		$temp = $content->getElementsByType( 'xsSimpleContent' );
		$content = $temp[0]; // Should only be one - now we now find out element's parent class
		$temp = & $content->getElements();
		$content = $temp[0]; // Should either be an xsExtension or xsRestriction
		$type = $content->getAttribute( 'base' );
		//print "setting extends to ". $type ."\n";
		$generator->setContentType( $type );
		$temp = & $content->getElementsByType( 'xsAttribute' );
		for( $i=0; $i<count( $temp ); $i++ ) {
			$generator->addAttribute( $temp[$i] );
		}
	} else if ( count( $content->getElementsByType( 'xsComplexContent' ) ) > 0 ) {
		//print "found complexContent!\n";
		//ComplexContent specified means type is derived
		$temp = $content->getElementsByType( 'xsComplexContent' );
		$content = $temp[0]; // Should only be one - now we now find out element's parent class
		$temp = & $content->getElements();
		$content = $temp[0]; // Should either be an xsExtension or xsRestriction
		if ( $content->getType() == 'xsExtension' ) {
			$generator->bag['isExtension'] = true;
		}
		if ( $content->getType() == 'xsRestriction' ) {
			$generator->bag['isRestriction'] = true;
		}
		$type = $content->getAttribute( 'base' );
		//print "setting extends to ". $type ."\n";
		$generator->bag['base_type'] = $type;
		//Generate the complex type this is derived from
		for ( $i = 0; $i < count( $GLOBALS['_globals']['complex_types'] ); $i++ ) {
			if ( $type == $GLOBALS['_globals']['complex_types'][$i]->getAttribute('name') ) {
				$generator->setComplexType( true );
				$generator->bag['ref_elements'][] = $type;
				//$this->generateComplexType( $GLOBALS['_globals']['complex_types'][$i], $generator, $context );
				break;
			}
		}
		
		// Parse element context
		$this->flatten( $content, $generator, $element_context, $content->getAttribute( 'maxOccurs' ) );

	} else {
		//print "found nothing so doing complex content flatten\n";
		// The alternative to xsSimpleContent is xsComplexContent - if it is not specified, it is implied
		// Parse element context
		$this->flatten( $content, $generator, $element_context, $content->getAttribute( 'maxOccurs' ) );
		
	}
  }
  
  //function that generates the inline simpleType
  function generateSimpleType( $content, & $generator ) {
	
	$e = $content->getElements();
    $generator->setType( $this->getAttribute( 'name' ) );
    //print $this->getAttribute( 'name' ) ." has a simpletype\n";
    $a = $content->getElementsByType( 'xsAnnotation' );
    if ( count( $a ) > 0 ) {
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
        //print $enums[$i]->getAttribute( 'value' );
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
    } else
    {
      $this->log( "WARN: unexpected element in xsSimpleType code generation" );
    }
  }
}
?>