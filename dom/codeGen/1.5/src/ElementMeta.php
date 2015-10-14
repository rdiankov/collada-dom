<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

class ElementMeta
{
  var $pre_name;
  var $name;
  //var $extends;
  var $doc;
  
  var $bag;
  
  function ElementMeta( & $global_elements )
  {
    $bag = array(
      'has_id_attr' => false,
      'context' => '',
      'pre_name' => '',
      'content_type' => '',
      'base_type' => '',
      'documentation' => array(),
      'element_name' => '',
      'elements' => array(),
      'inline_elements' => array(),
      'ref_elements' => array(),
      'element_attrs' => array(),
      'attributes' => array(),
      'mixed' => false,
      'complex_type' => false,
      'abstract' => false,
      'substitution_group' => '',
      'element_documentation' => array(),
      'useXMLNS' => false,
      'hasChoice' => false,
      'isEmptyContent' => false,
      'groupElements' => array(),
      'isAComplexType' => false,
      'isAGroup' => false,
      'substitutableWith' => array(),
      'isExtension' => false,
      'isRestriction' => false,
      'simple_type' => NULL, 
      'parent_meta' => NULL,
      'has_any' => false,
      'content_model' => array()
    );

    $this->bag = & $bag;
    $this->bag['global_elements'] = & $global_elements;
  }
  
  function addGroup( & $e ) {
	$this->bag['groupElements'][] = $e->getAttribute('ref');
  }
  
  function setSubstitutionGroup( $subGroup ) {
	$this->bag['substitution_group'] = trim( $subGroup );
  }
  
  function setComplexType( $bool ) {
	$this->bag['complex_type'] = ( $bool == true );
  }
  
  function setAbstract( $bool ) {
	$this->bag['abstract'] = ( $bool == true );
  }
  
  function setHasChoice( $bool ) {
	$this->bag['hasChoice'] = $bool;
  }
  
  function setIsEmptyContent( $bool ) {
	$this->bag['isEmptyContent'] = $bool;
  }
  
  function setIsAComplexType( $bool ) {
	$this->bag['isAComplexType'] = ( $bool == true );
  }
  
  function setIsAGroup( $bool ) {
	$this->bag['isAGroup'] = ( $bool == true );
  }
  
  function & getMeta()
  {
    return $this->bag;
  }
  
  function setMixed( $bool )
  {
    $this->bag['mixed'] = ( $bool == true );
  }
  
  function setContentType( $type )
  {
    // Strip xs if we've got a built-in type
    /*if ( preg_match( "/xs\:/", $type ) )
    {
      $type = 'xs' . substr( $type, 3 );
    }*/
    // If type is non-empty, then go ahead and set it
    if ( preg_match( "/[^\s]+/", $type ) )
    {
      $this->bag['content_type'] = trim( $type );
    }
  }
  
  function setMinOccurs( $min )
  {
    if ( is_int( $min ) && $min >= 0 )
    {
      $this->bag['minOccurs'] = $min;
    }
  }
  
  function setMaxOccurs( $max )
  {
    if ( $max == 'unbounded' || (is_int( $max ) && $max >= 1 ) )
    {
      $this->bag['maxOccurs'] = $max;
    }
  }
  
  function setPreName( $pre )
  {
    $this->pre_name = $pre;
    $this->bag['pre_name'] = $pre;
  }
  
  function setName( $name )
  {
    $this->name = $name;
    $this->bag['element_name'] = $name;
  }

  function getName()
  {
    return $this->name;
  }
  
  function setHasID( $bool )
  {
    $this->bag['has_id_attr'] = $bool;
  }
  
  function setDocumentation( $doc )
  {
    $this->doc = $doc;
    $this->bag['documentation']['en'] = trim( $doc );
  }
  
  function setAppInfo( $ap ) {
	if (!strcmp( trim($ap), 'enable-xmlns' ) ) {
		//use the xmlns attribute
		$this->bag['useXMLNS'] = true;
	}
  }
  
  function setContext( $context )
  {
    $this->bag['context'] = $context;
  }
  
  function addElement( & $e, $context )
  {
    $name = 'undefined';
    $ref_element = false;
    $_attributes = array();    
    
    foreach( $e->getAttributes() as $k => $v )
    {
      $_attributes[ $k ] = $v;
        
      if ( $k == 'ref' )
      {
        $name = $v;
        $ref_element = true;
      } 
      else if ( $k == 'name' )
      {
        $name = $v;
      }
    }
    
    //check if this element already exists this only applies if in a sequence.
    foreach( $this->bag['elements'] as $nm ) {
		if ( $nm == $name ) {
			//print "found duplicate element upping max occurs";
			//if it does then update its max occurs and exit
			if ( !$this->bag['hasChoice'] || $_attributes['maxOccurs'] == 'unbounded' ) {
				if ( $this->bag['element_attrs'][$nm]['maxOccurs'] != 'unbounded' ) {
					$this->bag['element_attrs'][$nm]['maxOccurs']++;
				}
			}
			//print " to ". $this->bag['element_attrs'][$nm]['maxOccurs'] ."\n";
			return;
		}
	}
    
    // Track the attrs on each sub-element
    $this->bag['element_attrs'][ $name ] = & $_attributes;
    
    // Call the dom-recurse function on each new element
    if ( !$ref_element )
    {
		$this->bag['elements'][] = $name;
      $this->bag['inline_elements'][ $name ] = & $e->generate( $this->bag['context'], $this->bag['global_elements'] );
      $this->bag['element_documentation'][$name] = $this->bag['inline_elements'][ $name ]['documentation']['en'];
      $this->bag['inline_elements'][ $name ]['parent_meta'] = & $this->bag;
    }
    else {
		$this->bag['elements'][] = $name;
        $this->bag['ref_elements'][] = $name;
		//check for documentation
		$a = $e->getElementsByType( 'xsAnnotation' );
		if ( count( $a ) > 0 ) {
			$d = $a[0]->getElementsByType( 'xsDocumentation' );
			if ( count( $d ) > 0 )
			{
				$this->bag['element_documentation'][$name] = $d[0]->get();
			}
		}
    }

  }
  
  function addAttribute( & $a )
  {
    $name = '';
    $a_list = array();
    
    foreach( $a->getAttributes() as $k => $v )
    {
      $a_list[ $k ] = $v;
      if ( $k == 'name' )
      {
        $name = $v;
        if ( $name == 'id' ) { $this->bag['has_id_attr'] = true; }
      }
      else if ( $k == 'ref' ) {
		$name = $v;
		//printf( "found an attribute ref for ". $name ."\n"); 
		if ( strpos( $name, ':' ) !== FALSE ) {
			$name[strpos( $name, ':' )] = '_';
			//printf( "changed : to _ for ". $name ."\n" );
			$a_list[ 'type' ] = 'xs:anyURI';
		}
      }
    }
    //check for documentation
    $e = $a->getElementsByType( 'xsAnnotation' );
    if ( count( $e ) > 0 )
    {
      $d = $e[0]->getElementsByType( 'xsDocumentation' );
      if ( count( $d ) > 0 )
      {
         $a_list['documentation'] = $d[0]->get();
      }
    }
    
    $this->bag['attributes'][ $name ] = & $a_list;
    
    //print "adding attribute ". $name ."\n";
  }
  
  //For elements name is the element name, for sequence name = 0, choice = 1, group = 2, all = 3, any = 4, end = 5
  function addContentModel( $name, $minOccurs, $maxOccurs ) 
  {
	$this->bag['content_model'][] = array( 'name' => $name, 'minOccurs' => $minOccurs, 'maxOccurs' => $maxOccurs );
	print "adding content model name: ". $name ." minO: ". $minOccurs ." maxO: ". $maxOccurs ."\n";
  }
}

?>