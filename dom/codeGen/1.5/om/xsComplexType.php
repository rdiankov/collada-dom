<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsComplexType extends _elementSet
{
  function xsComplexType()
  {
    $this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsChoice', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsAttribute', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsSequence', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsAll', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsGroup', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsSimpleContent', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );
    $this->_addElement( 'xsComplexContent', array( 'minOccurs' => '1', 'maxOccurs' => '1' ) );

    $this->_addAttribute( 'name', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'mixed', array( 'type' => 'xs:string', 'default' => 'false' ) );

    $this->type[] = 'xsComplexType';
    parent::_elementSet();
  }

  function & generate( $element_context, & $global_elements )
  {
    $element_context[] = $this->getAttribute( "name" );
    print implode( ",", $element_context ) . "\n";
    
    // Get new factory
    $generator = new ElementMeta( $global_elements );
    $generator->setIsAComplexType( true );
    
    // Load the class name and a context pre-fix (in case we're inside another element)
    $generator->setName( $this->getAttribute( 'name' ) );
    $generator->setContext( $element_context );
    
    // Extract any documentation for this node
    $a = $this->getElementsByType( 'xsAnnotation' );
    if ( count( $a ) > 0 )
    {
      $d = $a[0]->getElementsByType( 'xsDocumentation' );
      if ( count( $d ) > 0 )
      {
        $generator->setDocumentation( $d[0]->get() );
      }
    }
    if ( $this->getAttribute( 'mixed' ) == 'true' )
    {
		$generator->setMixed( true );
    }
    
    $content = $this; // Should only be one
    $this->generateComplexType( $content, $generator, $element_context ); 
	
	if ( count( $generator->bag['elements'] ) == 0 ) {
		$generator->setIsEmptyContent( true );
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
		if ($content instanceof xsRestriction) {
			$generator->bag['baseTypeViaRestriction'] = $type;
			$generator->bag['base_type'] = $type;
		}
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
		if ($content instanceof xsRestriction)
			$generator->bag['baseTypeViaRestriction'] = $type;
		//print "setting extends to ". $type ."\n";
		$generator->bag['base_type'] = $type;
		//Generate the complex type this is derived from
		//*************CHANGE NEEDED HERE 8-25 !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
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
		if ( count( $generator->bag['elements'] ) == 0 ) {
			$generator->setIsEmptyContent( true );
		}
	}
  }
  
  function & generateType() {
    $vars = array();
    $e = $this->getElements();
    $generator = new TypeMeta();
    
    $generator->setType( $this->getAttribute( 'name' ) );
    $generator->setIsComplex( true );

    $meta = & $generator->getMeta();
    return $meta;
  }
}

?>