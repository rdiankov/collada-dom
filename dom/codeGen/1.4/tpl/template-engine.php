<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

$_globals = array();
// FLAG: Full Code - set true to output verbose mode, minimal otherwise (does not include//                   inline elements in minimal)
$_globals['full_code'] = true;
$_globals['copyright'] = false;
$_globals['copyright_text'] = "/*\n" .
" * Copyright 2006 Sony Computer Entertainment Inc.\n" .
" *\n" .
" * Licensed under the SCEA Shared Source License, Version 1.0 (the \"License\"); you may not use this\n" .
" * file except in compliance with the License. You may obtain a copy of the License at:\n" .
" * http://research.scea.com/scea_shared_source_license.html\n" .
" *\n" .
" * Unless required by applicable law or agreed to in writing, software distributed under the License\n" .
" * is distributed on an \"AS IS\" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or\n" .
" * implied. See the License for the specific language governing permissions and limitations under the\n" .
" * License.\n" .
" */\n\n";

$_globals['depth'] = 0;
$_globals['meta_prefix'] = 'dae';
$_globals['prefix'] = 'dom';
$_globals['language'] = 'en';
$_globals['dom_dir'] = 'gen/dom/';
$_globals['tmp_dir'] = 'tmp/';
$_globals['log_file'] = 'gen/gen.log';

$_globals['register_list'] = array();
$_globals['include_list'] = array();
$_globals['complex_types'] = array();
$_globals['groups'] = array();
$_globals['constStrings'] = array(); //used to store all the constant strings needed to be put in the file.
$_globals['elementTypes'] = array();
$_globals['elementNames'] = array();
$_globals['elementNames'][] = "COLLADA"; //needed because no elements have this as a child.
$_globals['typeID'] = 0;

$_globals['target_dir'] = '';

$_globals['global_elements'] = array();

$_globals['templates'] = array(
  'DOXYGEN' => 'tpl/tpl-doxygen.php',
  'TYPES_HEADER_FILE' => 'tpl/tpl-types-header-file.php',
  'TYPES_HEADER' => 'tpl/tpl-types-header.php',
  'TYPES_CPP_FILE' => 'tpl/tpl-types-cpp-file.php',
  'TYPES_CPP' => 'tpl/tpl-types-cpp.php',
  'INCLUDES' => 'tpl/tpl-includes.php',
  'HEADER' => 'tpl/tpl-dot-h.php',
  'HEADER_FILE' => 'tpl/tpl-header.php',
  'INCLUDE_LIST' => 'tpl/tpl-include-list.php',
  'CPP_FILE' => 'tpl/tpl-cpp.php',
  'CPP' => 'tpl/tpl-cpp-body.php',
  'CPP_STATIC' => 'tpl/tpl-cpp-static.php',
  'CPP_METHODS' => 'tpl/tpl-cpp-methods.php',
  'CLASS' => 'tpl/tpl-class-def.php',
  'ELEMENTS_FILE' => 'tpl/tpl-elements-file.php',
  'ELEMENTS' => 'tpl/tpl-elements.php',
  'CONSTANTS_FILE' => 'tpl/tpl-constants-file.php',
  'CONSTANTS' => 'tpl/tpl-constants.php',
  'CONSTANTS_CPP_FILE' => 'tpl/tpl-constants-cpp-file.php',
  'CONSTANTS_CPP' => 'tpl/tpl-constants-cpp.php'
);

function applyTemplate( $template, & $bag )
{
  global $_globals;
  
  $_result = '';
  if ( array_key_exists( $template, $_globals['templates'] ) )
  {
    ob_start();
    include( $_globals['templates'][ $template ] );

    $_result = ob_get_contents();
    ob_end_clean();
  }
  return $_result;
}

function initGen( $file )
{
  global $_globals;

  // A few defns
  $_globals['gen_start_time'] = date( "M d Y H:i:s" );
  $_globals['file_name'] = $file;

  // Verify target dirs exist, create if not
  makeGenDir( getcwd() . "/gen" );
  makeGenDir( getcwd() . "/" . $_globals['dom_dir'] );
  makeGenDir( getcwd() . "/" . $_globals['dom_dir'] . 'include/' );
  makeGenDir( getcwd() . "/" . $_globals['dom_dir'] . 'src/' );

  // Start buffering output
  ob_start();
}

function makeGenDir( $dir )
{
  if ( !is_dir( $dir ) )
  {
    if ( !mkdir( $dir ) )
    {
      die( "Could not create directory $dir\n" );
    }
  }
}

function cleanupGen()
{
  global $_globals;

  // Get output buffer
  $_result = ob_get_contents();
  ob_end_clean();
  // Assemble report
  ob_start();
  print "========================================\n\n";
  print "           Code Generation\n\n";
  print "----------------------------------------\n";
  print "COLLADA File: " . $_globals['file_name'] . "\n";
  print "Start time:   " . $_globals['gen_start_time'] . "\n";
  print "End time:     " . date( "M d Y H:i:s" ) . "\n";
  print "----------------------------------------\n\n";
  print $_result;
  print "\r\n\r\nend\r\n";
  file_put_contents( $_globals['log_file'], ob_get_contents() );
  ob_end_clean();
  print "Generation complete\n";
}

function saveTemplate( $file, $template, & $bag )
{
  $bytes = file_put_contents( $file, applyTemplate( $template, $bag ) );
}

function printAllSubChildren( & $elem, $prefix, $suffix ) {
	//print "subchild test count = ". count( $elem['elements'] ) ."\n";
	//print "subchild test name = ". $elem['element_name'] ."\n";
	global $_globals;
	global $meta;
	for ( $i = 0; $i < count( $elem['elements'] ); $i++ ) {
		if ( isset( $meta[$elem['elements'][$i]] ) ) {
			if ( $meta[$elem['elements'][$i]]['isAGroup'] ) {
				
				printAllSubChildren( $meta[$elem['elements'][$i]], $prefix, $suffix );
			}
			else if ( !$meta[$elem['elements'][$i]]['abstract'] ) {
				print $prefix ."_Meta->children.append(\"". $elem['elements'][$i] ."\");". $suffix;
				print ");\n";
			}
			for( $c = 0; $c < count( $meta[$elem['elements'][$i]]['substitutableWith']); $c++ ) {
				$subwith = $meta[$elem['elements'][$i]]['substitutableWith'][$c];
				print $prefix ."_Meta->children.append(\"". $subwith ."\");". $suffix;
				print ");\n";
			}
		}
		else {
			print $prefix . $elem['elements'][$i] . $suffix;
			if ( isset($meta[$elem['element_attrs'][ $elem['elements'][$i] ]['type']]) &&
					$meta[$elem['element_attrs'][ $elem['elements'][$i] ]['type']]['isAComplexType'] ) {
				print ", \"". $elem['element_attrs'][ $elem['elements'][$i] ]['type'] ."\"";
			}
			print ");\n";
		}
	}
}

function getInheritanceStatement($baseClasses) {
	if (count($baseClasses) == 0)
		return "";
	$statement = " : public " . $baseClasses[0];
	for ($i = 1; $i < count($baseClasses); $i++)
		$statement .= ", public " . $baseClasses[$i];
	return $statement;
}

function beginConstructorInitializer(& $initializerListStarted) {
	print $initializerListStarted ? ", " : " : ";
	$initializerListStarted = true;
}

function printBaseClassInitializers($elemName, $baseClasses, & $initializerListStarted) {
	$elt = strpos($elemName, "_complexType") === false ? "this" : "elt";
	for ($i = 0; $i < count($baseClasses); $i++) {
		beginConstructorInitializer($initializerListStarted);
		print $baseClasses[$i] .
		      (strpos($baseClasses[$i], "_complexType") !== false ? "(dae, " . $elt . ")" : "(dae)");
	}
}

function printConstructors( $elemName, & $bag, $baseClasses, $indent ) {
	//print the protected ctor and copy stuff
	print $indent ."protected:\n";
	print $indent ."\t/**\n". $indent ."\t * Constructor\n". $indent ."\t */\n";
	print $indent ."\t". $elemName ."(DAE& dae";
	if ($bag['isAComplexType'])
		print ", daeElement* elt";
	print ")";
	$initializerListStarted = false;
	$eltVar = $bag['isAComplexType'] ? "*elt" : "*this";

	printBaseClassInitializers($elemName, $baseClasses, $initializerListStarted);

	if ($bag['useXMLNS']) {
		beginConstructorInitializer($initializerListStarted);
		print "attrXmlns(dae, " . $eltVar . ")";
	}
	
	// Constructor initialization of attributes
	if (count($bag['attributes']) > 0) {
		foreach( $bag['attributes'] as $attr_name => & $a_list ) {
			beginConstructorInitializer($initializerListStarted);

			$attr_name = ucfirst($attr_name);
			$type = $a_list['type'];
			print "attr" . $attr_name . "(";
			if ($type == 'xs:anyURI' || $type == 'URIFragmentType')
				print "dae, " . $eltVar;
			else if ($type == 'xs:IDREF')
				print $eltVar;
			else if ($type == 'xs:IDREFS')
				print "new xsIDREF(" . $eltVar . ")";
			print ")";
		}
	}
	
	// Constructor initialization of elements
	for( $i=0; $i<count( $bag['elements'] ); $i++ ) {
		$maxOccurs = $bag['element_attrs'][ $bag['elements'][$i] ]['maxOccurs'];
		$maxOccurs = ($maxOccurs == 'unbounded' || $maxOccurs > 1);
		beginConstructorInitializer($initializerListStarted);
		print "elem" . ucfirst($bag['elements'][$i]) . ($maxOccurs ? "_array" : "") . "()";
	}

	if ( ($bag['content_type'] != '' || $bag['mixed']) && !$bag['abstract'] ) {
		beginConstructorInitializer($initializerListStarted);
		if ($bag['content_type'] == 'xs:anyURI' || $bag['content_type'] == 'URIFragmentType')
			print "_value(dae, " . $eltVar . ")";
		else if ($bag['content_type'] == 'xs:IDREF')
			print "_value(" . $eltVar . ")";
		else if ($bag['content_type'] == 'xs:IDREFS')
			print "_value(new xsIDREF(" . $eltVar . "))";
		else
			print "_value()";
	}	
	print " {}\n";
	
	print $indent ."\t/**\n". $indent ."\t * Destructor\n". $indent ."\t */\n";
	print $indent ."\tvirtual ~". $elemName ."() {";
	if ( $bag['hasChoice'] ) {
		print " daeElement::deleteCMDataArray(_CMData); ";
	}
	print "}\n";

	print $indent ."\t/**\n". $indent ."\t * Overloaded assignment operator\n". $indent ."\t */\n";
	print $indent ."\tvirtual ".$elemName ." &operator=( const ".$elemName ." &cpy ) { (void)cpy; return *this; }\n";
}

function printAttributes( & $bag, & $typemeta, & $indent, $vaa ) {
	global $_globals;

	$attrCnt = 0;
	if ( $bag['useXMLNS'] ) {
		//comment
		print $indent ."\t/**\n". $indent ."\t * Gets the xmlns attribute.\n";
		print $indent ."\t * @return Returns a xsAnyURI reference of the xmlns attribute.\n";
		print $indent ."\t */\n";
		//code
		print $indent ."\txsAnyURI &getXmlns() { return attrXmlns; }\n";
		//comment
		print $indent ."\t/**\n". $indent ."\t * Gets the xmlns attribute.\n";
		print $indent ."\t * @return Returns a constant xsAnyURI reference of the xmlns attribute.\n";
		print $indent ."\t */\n";
		//code
		print $indent ."\tconst xsAnyURI &getXmlns() const { return attrXmlns; }\n";
		//comment
		print $indent ."\t/**\n". $indent ."\t * Sets the xmlns attribute.\n";
		print $indent ."\t * @param xmlns The new value for the xmlns attribute.\n";
		print $indent ."\t */\n";	
		//code
		print $indent ."\tvoid setXmlns( const xsAnyURI &xmlns ) { attrXmlns = xmlns;";
		if ( $vaa ) {
			print $indent ."\n\t _validAttributeArray[". $attrCnt ."] = true;";
		}
		print " }\n\n";
		
		$attrCnt++;
	}
	
	foreach( $bag['attributes'] as $attr_name => & $a_list ) {
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
		
		if ( (isset( $typemeta[$type] ) && $typemeta[$type]['isArray']) || $type == 'IDREFS' ) {
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." array attribute.\n";
			print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." reference of the ". $attr_name ." array attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\t" . $pre . ucfirst( $type ) . " &get" . ucfirst($attr_name) ."() { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." array attribute.\n";
			print $indent ."\t * @return Returns a constant ". $pre . ucfirst( $type ) ." reference of the ". $attr_name ." array attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tconst " . $pre . ucfirst( $type ) . " &get" . ucfirst($attr_name) ."() const { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Sets the ". $attr_name ." array attribute.\n";
			print $indent ."\t * @param at". ucfirst($attr_name)." The new value for the ". $attr_name ." array attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tvoid set". ucfirst( $attr_name ) ."( const ". $pre . ucfirst( $type ) ." &at";
			print ucfirst($attr_name) ." ) { attr". ucfirst($attr_name) ." = at". ucfirst($attr_name) .";";
			if ( $vaa ) {
				print " _validAttributeArray[". $attrCnt ."] = true;";
			}
			print " }\n\n";
		}
		else if ( ucfirst($type) == 'AnyURI' || ucfirst($type) == 'URIFragmentType' ) {
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." reference of the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\t" . $pre . ucfirst( $type ) . " &get" . ucfirst($attr_name) ."() { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @return Returns a constant ". $pre . ucfirst( $type ) ." reference of the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tconst " . $pre . ucfirst( $type ) . " &get" . ucfirst($attr_name) ."() const { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Sets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @param at". ucfirst($attr_name)." The new value for the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tvoid set". ucfirst( $attr_name ) ."( const ". $pre . ucfirst( $type ) ." &at";
			print ucfirst($attr_name) ." ) { attr". ucfirst($attr_name) ." = at". ucfirst($attr_name) .";";
			if ( $vaa ) {
				print " _validAttributeArray[". $attrCnt ."] = true;";
			}
			print " }\n";
			// We add a setter that takes a plain string to help with backward compatibility
			//comment
			print $indent ."\t/**\n". $indent ."\t * Sets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @param at". ucfirst($attr_name)." The new value for the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tvoid set". ucfirst( $attr_name ) ."( xsString at";
			print ucfirst($attr_name) ." ) { attr". ucfirst($attr_name) ." = at" . ucfirst($attr_name) . ";";
			if ( $vaa ) {
				print " _validAttributeArray[". $attrCnt ."] = true;";
			}
			print " }\n\n";
		}
		else if( ucfirst($type) == 'IDREF' ) {
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." reference of the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\t" . $pre . ucfirst( $type ) . " &get" . ucfirst($attr_name) ."() { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @return Returns a constant ". $pre . ucfirst( $type ) ." reference of the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tconst " . $pre . ucfirst( $type ) . " &get" . ucfirst($attr_name) ."() const{ ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Sets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @param at". ucfirst($attr_name)." The new value for the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tvoid set". ucfirst( $attr_name ) ."( const ". $pre . ucfirst( $type ) ." &at";
			print ucfirst($attr_name) ." ) { attr". ucfirst($attr_name) ." = at". ucfirst($attr_name) .";";
			if ( $vaa ) {
				print " _validAttributeArray[". $attrCnt ."] = true;";
			}
			print " }\n\n";
		}
		else if ( strstr( $baseStringTypes, $baseType ) !== FALSE && count( $a_list['enum'] ) == 0 ) {
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." of the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\t" . $pre . ucfirst( $type ) . " get" . ucfirst($attr_name) ."() const { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Sets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @param at". ucfirst($attr_name)." The new value for the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tvoid set". ucfirst( $attr_name ) ."( ". $pre . ucfirst( $type ) ." at";
			print ucfirst($attr_name) ." ) { *(daeStringRef*)&attr". ucfirst($attr_name) ." = at". ucfirst($attr_name) .";";
			if ( $vaa ) {
				print " _validAttributeArray[". $attrCnt ."] = true; ";
			}
			if ( $attr_name == "id" )
			{
				print "\n". $indent ."\t\tif( _document != NULL ) _document->changeElementID( this, attrId );\n". $indent ."\t";
			}
			
			print "}\n\n";
		}
		else {
			//comment
			print $indent ."\t/**\n". $indent ."\t * Gets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @return Returns a ". $pre . ucfirst( $type ) ." of the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\t" . $pre . ucfirst( $type ) . " get" . ucfirst($attr_name) ."() const { ";
			print "return attr". ucfirst($attr_name) ."; }\n";
			//comment
			print $indent ."\t/**\n". $indent ."\t * Sets the ". $attr_name ." attribute.\n";
			print $indent ."\t * @param at". ucfirst($attr_name)." The new value for the ". $attr_name ." attribute.\n";
			print $indent ."\t */\n";
			//code
			print $indent ."\tvoid set". ucfirst( $attr_name ) ."( ". $pre . ucfirst( $type ) ." at";
			print ucfirst($attr_name) ." ) { attr". ucfirst($attr_name) ." = at". ucfirst($attr_name) .";";
			if ( $vaa ) {
				print " _validAttributeArray[". $attrCnt ."] = true;";
			}
			print " }\n\n";
		}
		$attrCnt++;
	}
}

?>