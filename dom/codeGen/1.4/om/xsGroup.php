<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class xsGroup extends _elementSet
{
  function xsGroup()
  {
    $this->_addElement( 'xsAnnotation', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsElement', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsAttribute', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsChoice', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsSequence', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    $this->_addElement( 'xsGroup', array( 'minOccurs' => '0', 'maxOccurs' => 'unbounded' ) );
    
    $this->_addAttribute( 'ref', array( 'type' => 'xs:string' ) );
    $this->_addAttribute( 'name', array( 'type' => 'xs:string' ) );
    
    $this->type[] = "xsGroup";
    parent::_elementSet();
  }

  function addChoiceElement( & $e )
  {
    $this->addElement( $e );
  }  

  function & generate( $element_context, & $global_elements )
  {
    $element_context[] = $this->getAttribute( "name" );
    print implode( ",", $element_context ) . "\n";
    
    // Get new factory
    $generator = new ElementMeta( $global_elements );
    $generator->setIsAGroup( true );
    
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
    
    // Inspect the semantic structure of this node and extract the elements/attributes
    $this->flatten( $this, $generator, $element_context, $this->getAttribute( 'maxOccurs' ) );
    
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
}

?>