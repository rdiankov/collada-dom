<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

	global $meta;
	global $typemeta;
	
	// shorthand:
	$full_element_name = $_globals['prefix'] . ucfirst( $bag['element_name'] );
	//COLLADA TYPE list
	if ( array_search( $bag['element_name'], $_globals['elementTypes'] ) === FALSE ) 
	{
		$_globals['elementTypes'][] = $bag['element_name'];
	}
	//COLLADA ELEMENT list
	for( $i=0; $i<count( $bag['elements'] ); $i++ )
	{
		if ( array_search( $bag['elements'][$i], $_globals['elementNames'] ) === FALSE ) 
		{
			$_globals['elementNames'][] = $bag['elements'][$i];
		}
	}
	if ( $bag['substitution_group'] != '' )
	{
		//also add this element to the list of elements. 
		if ( array_search( $bag['element_name'], $_globals['elementNames'] ) === FALSE ) 
		{
			$_globals['elementNames'][] = $bag['element_name'];
		}
	}
  
	$indent = "";
	for ($i = 0; $i < $GLOBALS['indentNum']; $i++ ) {
		$indent .= "\t";
	}
	if ( $GLOBALS['indentNum'] > 0 ) { //only print these for the inner classes.. the main classes will have
									//them defined in a seperate file to avoid circular includes.
		print $indent."class " . $full_element_name . ";\n\n";
		print $indent ."typedef daeSmartRef<". $full_element_name ."> ". $full_element_name ."Ref;\n";
		print $indent ."typedef daeTArray<". $full_element_name ."Ref> ". $full_element_name ."_Array;\n\n";
	}

	// DOCUMENTATION
	if ( isset( $bag['documentation'][ $_globals['language'] ] ) )
	{
		print applyTemplate( 'DOXYGEN', $bag['documentation'][ $_globals['language'] ] );
	}

	// SUBSTITION GROUP/INHERITANCE
	$baseClasses = array();
	if ( $bag['isAComplexType'] ) {
		if ( $bag['complex_type'] )
			$baseClasses[] = $_globals['prefix'] . ucfirst( $bag['base_type'] ) . "_complexType";
		print $indent ."class ". $full_element_name ."_complexType ".
		      getInheritanceStatement($baseClasses) ."\n".$indent."{\n";
	}
	else {
		$baseClasses[] = ($bag['substitution_group'] != '' ? $_globals['prefix'] . ucfirst($bag['substitution_group']) : 'daeElement');
		if ( $bag['complex_type'] )
			$baseClasses[] = $_globals['prefix'] . ucfirst( $bag['base_type'] ) . "_complexType";
		print $indent ."class ". $full_element_name . getInheritanceStatement($baseClasses) . "\n".$indent."{\n";
		print $indent ."public:\n";
		print $indent ."\tvirtual COLLADA_TYPE::TypeEnum getElementType() const { return COLLADA_TYPE::". strtoupper($bag['element_name']) ."; }\n";
		print $indent ."\tstatic daeInt ID() { return ". $_globals['typeID']++ ."; }\n";
		print $indent ."\tvirtual daeInt typeID() const { return ID(); }\n";
	}

  // INTERNAL CLASSES
  $result = '';
  $inlines = array_keys( $bag['inline_elements'] );
  for( $i=0; $i<count( $inlines ); $i++ )
  {
	$inner = $bag['inline_elements'][ $inlines[$i] ];
	if ( !$inner['complex_type'] || $inner['isRestriction'] || $inner['isExtension'] ) {
		$GLOBALS['indentNum']++;
		$result .= applyTemplate( 'CLASS', $inner );
		$GLOBALS['indentNum']--;
    }
  }
  if ( strlen( $result ) > 0 ) { print $indent ."public:\n$result\n"; }

	//ENUM
	if ( $bag['simple_type'] != NULL ) {
		$typeMeta = $bag['simple_type']->getMeta();
		//print $typeMeta['type'];
		if ( count( $typeMeta['enum'] ) > 0 )
		{
		//print "has enums";
		print $indent ."public: //ENUM\n";
			if ( !$typeMeta['useConstStrings'] ) {
				//Decided to name mangle the enum constants so they are more descriptive and avoid collisions
				if ( isset( $typeMeta['documentation']['en'] ) ) {
					print applyTemplate( 'DOXYGEN', $typeMeta['documentation']['en'] );
				}
				print "enum " . $_globals['prefix'] . ucfirst( $typeMeta['type'] ) . "_type {\n";
				//print "\t" . strtoupper( $typeMeta['type'] ) . "_" . $typeMeta['enum'][0] ." = 1";
				for( $i = 0; $i < count( $typeMeta['enum'] ); $i++ ) {
					//print ",";
					print "\t" . strtoupper( $typeMeta['type'] ) . "_" . $typeMeta['enum'][$i] .",";
					if ( isset( $typeMeta['enum_documentation'][$i] ) ) {
						print "\t\t/**< ". $typeMeta['enum_documentation'][$i] ." */";
					}			
					//print "\n\t" . strtoupper( $typeMeta['type'] ) . "_" . $typeMeta['enum'][$i];
					print "\n";
				}
				//if ( isset( $typeMeta['enum_documentation'][count( $typeMeta['enum'] )-1] ) ) {
				//	print "\t\t/**< ". $typeMeta['enum_documentation'][count( $typeMeta['enum'] )-1] ." */";
				//}
				print "\t". strtoupper( $typeMeta['type'] ) . "_COUNT";
				print "\n};\n\n";
			}
			else {
				//if ( isset( $typeMeta['documentation']['en'] ) ) {
				//	$_globals['constStrings'][] = applyTemplate( 'DOXYGEN', $typeMeta['documentation']['en'] );
				//}
				for( $i = 0; $i < count( $typeMeta['enum'] ); $i++ ) {
					//print "static const daeString ". strtoupper( $typeMeta['type'] ) . "_" . $typeMeta['enum'][$i];
					//print " = \"". $typeMeta['enum'][$i] ."\";\n";
					if ( isset( $typeMeta['enum_documentation'][$i] ) ) {
						$_globals['constStrings'][] = "/**\n * ". $typeMeta['enum_documentation'][$i] ."\n */\n";
					}
					$conststrnm = strtoupper( $typeMeta['type'] ) . "_" . $typeMeta['enum'][$i];
					$conststr = "\"". $typeMeta['enum'][$i] ."\";\n";
					$_globals['constStrings'][$conststrnm] = $conststr;
				}
				$_globals['constStrings'][] = "\n";
			}
		}
	}
	
  // ATTRIBUTES
  if ( count( $bag['attributes'] ) > 0 || $bag['useXMLNS'] )
  {
	print $indent ."protected:  // Attribute". (count( $bag['attributes'] ) > 1 ? 's' : '') ."\n";

	if ( $bag['useXMLNS'] ) {
		print $indent ."\t/**\n". $indent ."\t * This element may specify its own xmlns.\n". $indent ."\t */\n";
		print $indent ."\txsAnyURI attrXmlns;\n";
	}
    foreach( $bag['attributes'] as $attr_name => & $a_list )
    {
      $type = $a_list['type'];
      if ( preg_match( "/xs\:/", $type ) ) { 
		$type = substr( $type, 3 );
		$pre = "xs";
	  }
	  else {
		$pre = $_globals['prefix'];
	  }
	  if ( $type == '' )
	  {
		$type = "String";
	  }
	  if ( isset( $a_list['documentation'] ) ) {
		print applyTemplate( 'DOXYGEN', $a_list['documentation'] );
      }
      print $indent ."\t" . $pre . ucfirst( $type ) . " attr" . ucfirst($attr_name) .";\n";
    }
  }
  
  // ELEMENTS
  if ( count( $bag['attributes'] > 0 ) ) { print "\n"; }
  
  if ( (count( $bag['elements'] ) > 0 && !$bag['isRestriction']) || $bag['has_any'] )
  {
		
	print $indent ."protected:  // Element". (count( $bag['elements'] ) > 1 ? 's' : '') ."\n";
	$needsContents = false;
    for( $i=0; $i<count( $bag['elements'] ); $i++ )
    {
      $maxOccurs = $bag['element_attrs'][ $bag['elements'][$i] ]['maxOccurs'];
//      $minOccurs = $bag['element_attrs'][ $bag['elements'][$i] ]['minOccurs'];
//      print "   // minOccurs=$minOccurs, maxOccurs=$maxOccurs\n";
      $maxOccurs = ($maxOccurs == 'unbounded' || $maxOccurs > 1);
      if ( isset( $bag['element_documentation'][ $bag['elements'][$i] ] ) ) {
		$bag['element_documentation'][ $bag['elements'][$i] ] .= " @see " . $_globals['prefix'] . ucfirst( $bag['elements'][$i] );
		print applyTemplate( 'DOXYGEN', $bag['element_documentation'][ $bag['elements'][$i] ] );
      }
      if ( isset( $bag['element_attrs'][ $bag['elements'][$i] ]['type'] ) &&
			isset( $meta[$bag['element_attrs'][ $bag['elements'][$i] ]['type']] ) ){
        print $indent ."\t" . $_globals['prefix'] . ucfirst( $bag['element_attrs'][ $bag['elements'][$i] ]['type'] ) . ($maxOccurs ? "_Array" : "Ref") . " elem" . ucfirst($bag['elements'][$i]) . ($maxOccurs ? "_array" : "") . ";\n";
      }
      else {
		print $indent ."\t" . $_globals['prefix'] . ucfirst( $bag['elements'][$i] ) . ($maxOccurs ? "_Array" : "Ref") . " elem" . ucfirst($bag['elements'][$i]) . ($maxOccurs ? "_array" : "") . ";\n";
      }
      if ( isset( $meta[$bag['elements'][$i]] ) ) {
			if( count( $meta[$bag['elements'][$i]]['substitutableWith']) > 0 ) {			
				$needsContents = true;
			}
		}
    }
    if ( $bag['hasChoice'] || $needsContents || $bag['has_any'] )
	{
		print $indent ."\t/**\n". $indent ."\t * Used to preserve order in elements that do not specify strict sequencing of sub-elements.";
		print "\n". $indent ."\t */\n";
		print $indent ."\tdaeElementRefArray _contents;\n";
		print $indent ."\t/**\n". $indent ."\t * Used to preserve order in elements that have a complex content model.";
		print "\n". $indent ."\t */\n";
		print $indent ."\tdaeUIntArray       _contentsOrder;\n\n";
	}
	if ( $bag['hasChoice'] )
	{
		print $indent ."\t/**\n". $indent ."\t * Used to store information needed for some content model objects.\n";
		print $indent ."\t */\n". $indent ."\tdaeTArray< daeCharArray * > _CMData;\n\n";
	}
  }
  
  //VALUE
  // NOTE: special casing any element with 'mixed' content model to ListOfInts type _value
  if ( ($bag['content_type'] != '' || $bag['mixed']) && !$bag['abstract'] )
  {
	  print $indent ."protected:  // Value\n";
	  
		$content_type = $bag['content_type'];
		if ( preg_match( "/xs\:/", $content_type ) ) {
			$content_type = substr( $content_type, 3 );
			$pre = "xs";
		}
		else {
			$pre = $_globals['prefix'];
		}
		//if ( !strcmp( $pre . ucfirst( $content_type ), $full_element_name ) ) {
		if ( $bag['parent_meta']['inline_elements'] != NULL && array_key_exists( $content_type, $bag['parent_meta']['inline_elements'] ) ) {
			$pre = '::' . $pre;
		}
		print $indent ."\t/**\n". $indent ."\t * The " . $pre . ucfirst( $content_type ) ." value of the text data of this element. ";
		print "\n". $indent ."\t */\n";
		print $indent ."\t".$pre . ucfirst( $content_type ) ." _value;\n";
  }
  
  if ( $bag['complex_type'] && !$bag['isAComplexType'] ) 
  {
	$bag2 = $bag;
	$bag2['attributes'] = array_merge( $meta[$bag['base_type']]['attributes'], $bag['attributes'] );
	printAttributes( $bag2, $typemeta, $indent, true );
  }
  
  if ( $_globals['accessorsAndMutators'] && ( $bag['useXMLNS'] || count($bag['attributes'])>0 ||
		count($bag['elements'])>0 ||( ($bag['content_type'] != '' || $bag['mixed']) && !$bag['abstract'] ) ) ) {
		
		//generate accessors and mutators for everything
		print "\n". $indent ."public:\t//Accessors and Mutators\n";
		printAttributes( $bag, $typemeta, $indent, !$bag['isAComplexType'] );
		
		$needsContents = false;
		for( $i=0; $i<count( $bag['elements'] ); $i++ )	{
			$maxOccurs = $bag['element_attrs'][ $bag['elements'][$i] ]['maxOccurs'];
			$maxOccurs = ($maxOccurs == 'unbounded' || $maxOccurs > 1);
			$type = '';
			if ( isset( $bag['element_attrs'][ $bag['elements'][$i] ]['type'] ) &&
				isset( $meta[$bag['element_attrs'][ $bag['elements'][$i] ]['type']] ) ){
				
				$type = $_globals['prefix'] . ucfirst( $bag['element_attrs'][ $bag['elements'][$i] ]['type'] ) . ($maxOccurs ? "_Array" : "Ref");
			}
			else {
				$type = $_globals['prefix'] . ucfirst( $bag['elements'][$i] ) . ($maxOccurs ? "_Array" : "Ref");
			}
			$name = ucfirst($bag['elements'][$i]) . ($maxOccurs ? "_array" : "");
			if ( $maxOccurs ) {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the ". $bag['elements'][$i] ." element array.\n";
				print $indent ."\t * @return Returns a reference to the array of ". $bag['elements'][$i] ." elements.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\t". $type ." &get". $name ."() { return elem". $name ."; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the ". $bag['elements'][$i] ." element array.\n";
				print $indent ."\t * @return Returns a constant reference to the array of ". $bag['elements'][$i] ." elements.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tconst ". $type ." &get". $name ."() const { return elem". $name ."; }\n";
				//print $indent ."\tvoid set". $name ."( ". $type ." *e". $name ." ) { elem". $name ." = *e". $name ."; }\n\n";
			}
			else {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the ". $bag['elements'][$i] ." element.\n";
				print $indent ."\t * @return a daeSmartRef to the ". $bag['elements'][$i] ." element.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tconst ". $type ." get". $name ."() const { return elem". $name ."; }\n";
				//print $indent ."\tvoid set". $name ."( ". $type ." &e". $name ." ) { elem". $name ." = e". $name ."; }\n\n";
			}
			
			if ( isset( $meta[$bag['elements'][$i]] ) ) {
				if( count( $meta[$bag['elements'][$i]]['substitutableWith']) > 0 ) {			
					$needsContents = true;
				}
			}
		}
	    
		if ( $bag['hasChoice'] || $needsContents || $bag['has_any'] )
		{
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the _contents array.\n";
			print $indent ."\t * @return Returns a reference to the _contents element array.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tdaeElementRefArray &getContents() { return _contents; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the _contents array.\n";
			print $indent ."\t * @return Returns a constant reference to the _contents element array.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tconst daeElementRefArray &getContents() const { return _contents; }\n\n";
		}
		
		if ( ($bag['content_type'] != '' || $bag['mixed']) && !$bag['abstract'] )
		{
			$type = $bag['content_type'];
			if ( preg_match( "/xs\:/", $type ) ) {
				$type = substr( $type, 3 );
				$pre = "xs";
			}
			else {
				$pre = $_globals['prefix'];
			}
			$baseStringTypes = "xsDateTime xsID xsNCName xsNMTOKEN xsName xsToken xsString";	
			$baseType = $pre . ucfirst( $type );
			if ( isset( $typemeta[$type] ) ) {
				$typeInfo = $typemeta[$type];
				while ( $typeInfo['base'] != '' && isset( $typemeta[$typeInfo['base']] ) ) {
					$typeInfo = $typemeta[$typeInfo['base']];
					if ( preg_match( "/xs\:/", $typeInfo['type'] ) ) { 
						$baseType = "xs" . ucfirst( substr( $typeInfo['type'], 3 ) );
					}
					else {
						$baseType = $_globals['prefix'] . ucfirst( $typeInfo['type'] );
					}
				}
			}
			//if ( !strcmp( $pre . ucfirst( $type ), $full_element_name ) ) {
			if ( $bag['parent_meta']['inline_elements'] != NULL && array_key_exists( $type, $bag['parent_meta']['inline_elements'] ) ) {
				$pre = '::' . $pre;
			}
			if ( (isset( $typemeta[$content_type] ) && $typemeta[$content_type]['isArray']) || $content_type == 'IDREFS' ) {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the _value array.\n";
				print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." reference of the _value array.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\t".$pre . ucfirst( $type ) ." &getValue() { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the _value array.\n";
				print $indent ."\t * @return Returns a constant ". $pre . ucfirst( $type ) ." reference of the _value array.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tconst ".$pre . ucfirst( $type ) ." &getValue() const { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Sets the _value array.\n";
				print $indent ."\t * @param val The new value for the _value array.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tvoid setValue( const ". $pre . ucfirst( $type ) ." &val ) { _value = val; }\n\n";
				//print $indent ."\t _meta->getValueAttribute()->setIsValid(true); }\n\n";
			}
			else if ( ucfirst($type) == 'AnyURI' ) {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the value of this element.\n";
				print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." of the value.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\t".$pre . ucfirst( $type ) ." &getValue() { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the value of this element.\n";
				print $indent ."\t * @return Returns a constant ". $pre . ucfirst( $type ) ." of the value.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tconst ".$pre . ucfirst( $type ) ." &getValue() const { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Sets the _value of this element.\n";
				print $indent ."\t * @param val The new value for this element.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tvoid setValue( const ". $pre . ucfirst( $type ) ." &val ) { _value = val; }\n";
				// We add a setter that takes a plain string to help with backward compatibility
				//comment
				print $indent ."\t/**\n". $indent ."\t * Sets the _value of this element.\n";
				print $indent ."\t * @param val The new value for this element.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tvoid setValue( xsString val ) { _value = val; }\n\n";
			}
			else if( ucfirst($type) == 'IDREF' ) {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the value of this element.\n";
				print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." of the value.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\t".$pre . ucfirst( $type ) ." &getValue() { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the value of this element.\n";
				print $indent ."\t * @return Returns a constant ". $pre . ucfirst( $type ) ." of the value.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tconst ".$pre . ucfirst( $type ) ." &getValue() const { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Sets the _value of this element.\n";
				print $indent ."\t * @param val The new value for this element.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tvoid setValue( const ". $pre . ucfirst( $type ) ." &val ) { _value = val; }\n\n";
				//print $indent ."\t _meta->getValueAttribute()->setIsValid(true); }\n\n";
			}
			else if ( strstr( $baseStringTypes, $baseType ) !== FALSE && count( $typemeta[$type]['enum'] ) == 0 ) {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the value of this element.\n";
				print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." of the value.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\t".$pre . ucfirst( $type ) ." getValue() const { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Sets the _value of this element.\n";
				print $indent ."\t * @param val The new value for this element.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tvoid setValue( ". $pre . ucfirst( $type ) ." val ) { *(daeStringRef*)&_value = val; }\n\n";				
			}
			else {
				//comment
				print $indent ."\t/**\n". $indent ."\t * Gets the value of this element.\n";
				print $indent ."\t * @return a ". $pre . ucfirst( $type ) ." of the value.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\t".$pre . ucfirst( $type ) ." getValue() const { return _value; }\n";
				//comment
				print $indent ."\t/**\n". $indent ."\t * Sets the _value of this element.\n";
				print $indent ."\t * @param val The new value for this element.\n";
				print $indent ."\t */\n";
				//code
				print $indent ."\tvoid setValue( ". $pre . ucfirst( $type ) ." val ) { _value = val; }\n\n";
				//print $indent ."\t _meta->getValueAttribute()->setIsValid(true); }\n\n";
			}
		}
	}

  //CONSTRUCTORS  
	if ( !$bag['isAComplexType'] ) {
		printConstructors( $full_element_name, $bag, $baseClasses, $indent );
	}
	else {
		printConstructors( $full_element_name ."_complexType", $bag, $baseClasses, $indent );
		
		print $indent ."};\n\n";
		print $indent ."/**\n". $indent ." * An element of type ". $full_element_name ."_complexType.\n". $indent ." */\n";
		print $indent ."class ". $full_element_name ." : public daeElement, public ". $full_element_name ."_complexType\n";
		print $indent ."{\n";
		print $indent ."public:\n";
		print $indent ."\tvirtual COLLADA_TYPE::TypeEnum getElementType() const { return COLLADA_TYPE::". strtoupper($bag['element_name']) ."; }\n";
		print $indent ."\tstatic daeInt ID() { return ". $_globals['typeID']++ ."; }\n";
		print $indent ."\tvirtual daeInt typeID() const { return ID(); }\n";

		if ( $_globals['accessorsAndMutators'] && ( $bag['useXMLNS'] || count($bag['attributes'])>0 ) ) {
			//generate accessors and mutators for everything
			print "\n". $indent ."public:\t//Accessors and Mutators\n";
			printAttributes( $bag, $typemeta, $indent, true );
		}
		
		$dummy = array();
		printConstructors( $full_element_name, $dummy, array("daeElement", $full_element_name . "_complexType"), $indent );
	}
	
	print "\n".$indent ."public: // STATIC METHODS\n";
	print $indent ."\t/**\n". $indent ."\t * Creates an instance of this class and returns a daeElementRef referencing it.\n";
	print $indent ."\t * @return a daeElementRef referencing an instance of this object.\n". $indent ."\t */\n";
	print $indent ."\tstatic DLLSPEC ". $_globals['meta_prefix'] ."ElementRef create(DAE& dae);\n";
	print $indent ."\t/**\n". $indent ."\t * Creates a daeMetaElement object that describes this element in the meta object reflection framework.";
	print "\n". $indent ."\t * If a daeMetaElement already exists it will return that instead of creating a new one. \n";
	print $indent ."\t * @return A daeMetaElement describing this COLLADA element.\n". $indent ."\t */\n";
	print $indent ."\tstatic DLLSPEC ". $_globals['meta_prefix'] ."MetaElement* registerElement(DAE& dae);\n";
	print $indent ."};\n\n";
