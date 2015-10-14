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
$needsContents = false;


// XXX all occurences of 'baseTypeViaRestriction' are ugly hacks to get 
// a working dom for 1.5
if(isset($bag['baseTypeViaRestriction'])) {
	print '#include <dom/' . 
		$_globals['prefix'] . ucfirst( $bag['baseTypeViaRestriction'] ) . 
		'.h>'."\n";
}


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
$baseClass = 'daeElement';
if($bag['substitution_group'] != '')
	$baseClass = $_globals['prefix'] . ucfirst($bag['substitution_group']);
if($bag['isExtension'])
	$baseClass = $_globals['prefix'] . ucfirst($bag['base_type']);
if(isset($bag['baseTypeViaRestriction']))
	$baseClass = $_globals['prefix'] . ucfirst($bag['baseTypeViaRestriction']);

print $indent ."class ". $full_element_name . " : public " . $baseClass . "\n".$indent."{\n";
print $indent ."public:\n";	
print $indent ."\tvirtual COLLADA_TYPE::TypeEnum getElementType() const { return COLLADA_TYPE::". strtoupper($bag['element_name']) ."; }\n";
print $indent ."\tstatic daeInt ID() { return ". $_globals['typeID']++ ."; }\n";
print $indent ."\tvirtual daeInt typeID() const { return ID(); }\n";

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
if ( ( count( $bag['attributes'] ) > 0 || $bag['useXMLNS'] ) /*&& !isset($bag['baseTypeViaRestriction'])*/ )
{
	print $indent ."protected:  // Attribute". (count( $bag['attributes'] ) > 1 ? 's' : '') ."\n";

	if ( $bag['useXMLNS'] ) {
		print $indent ."\t/**\n". $indent ."\t * This element may specify its own xmlns.\n". $indent ."\t */\n";
		print $indent ."\txsAnyURI attrXmlns;\n";
	}
	foreach( $bag['attributes'] as $attr_name => & $a_list )
	{
		$type = $a_list['type'];
		$pre = '';
		getTypeNameAndPrefix($type, $pre);
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
printElements($bag, $needsContents, $indent);

printAccessorsAndMutators($bag, $needsContents, $indent);

//VALUE
// NOTE: special casing any element with 'mixed' content model to ListOfInts type _value
if ( ( ($bag['content_type'] != '' || $bag['mixed']) && !$bag['abstract'] ) && !isset($bag['baseTypeViaRestriction']) )
{
	print $indent ."protected:  // Value\n";
	  
	$content_type = $bag['content_type'];
	$pre = '';
	getTypeNameAndPrefix($content_type, $pre);
	if ( $bag['parent_meta']['inline_elements'] != NULL && array_key_exists( $content_type, $bag['parent_meta']['inline_elements'] ) ) {
		$pre = '::' . $pre;
	}
	print $indent ."\t/**\n". $indent ."\t * The " . $pre . ucfirst( $content_type ) ." value of the text data of this element. ";
	print "\n". $indent ."\t */\n";
	$valueType = $pre . ucfirst($content_type);
	if($meta[$content_type]['isAComplexType'])
		$valueType = $valueType . "Ref";
	print $indent ."\t". $valueType ." _value;\n";
}

//CONSTRUCTORS  
printConstructors( $full_element_name, $bag, $baseClass, $indent );
		
print "\n".$indent ."public: // STATIC METHODS\n";
print $indent ."\t/**\n". $indent ."\t * Creates an instance of this class and returns a daeElementRef referencing it.\n";
print $indent ."\t * @return a daeElementRef referencing an instance of this object.\n". $indent ."\t */\n";
print $indent ."\tstatic DLLSPEC ". $_globals['meta_prefix'] ."ElementRef create(DAE& dae);\n";
print $indent ."\t/**\n". $indent ."\t * Creates a daeMetaElement object that describes this element in the meta object reflection framework.";
print "\n". $indent ."\t * If a daeMetaElement already exists it will return that instead of creating a new one. \n";
print $indent ."\t * @return A daeMetaElement describing this COLLADA element.\n". $indent ."\t */\n";
print $indent ."\tstatic DLLSPEC ". $_globals['meta_prefix'] ."MetaElement* registerElement(DAE& dae);\n";
print $indent ."};\n\n";
