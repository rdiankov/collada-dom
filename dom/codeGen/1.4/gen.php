<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

// COMMAND LINE: bin/php.exe gen.php collada.xsd [minimal]
// Note: must be run from home directory of code generator (i.e. where gen.php lives)

ini_set("memory_limit", "256M");

if ( file_exists( $argv[1] ) ) { $collada_file = $argv[1]; }
else
{
  die( "Can't find COLLADA file '" . $argv[1] . "'\n" );
}


require_once( 'src/SchemaParser.php' );
require_once( 'om/object-model.php' );
require_once( 'tpl/template-engine.php' );

// Returns either a capitalized or non-capitalized version of the name, thus helping us
// avoid name clashes. For example, say we have an element called 'rgb' and an element 
// called 'RGB'. They're both going to map to the name constant 'COLLADA_ELEMENT_RGB'. 
// Instead, use COLLADA_ELEMENT_rgb for the 'rgb' element.
function getUniqueName($name, $array) {
	$uniqueName = strtoupper($name);
	if (array_search($uniqueName, $array) !== FALSE) {
		$uniqueName = $name;
	}
	return $uniqueName;
}


if ( preg_match( "/min/i", $argv[2] ) || preg_match( "/min/i", $argv[3] ) ) { 
	$_globals['full_code'] = false; 
}
if ( preg_match( "/cprt/i", $argv[2] ) || preg_match( "/cprt/i", $argv[3] ) ) { 
	$_globals['copyright'] = true; 
}

$_globals['accessorsAndMutators'] = true;

$p = new SchemaParser();
$p->parse( $collada_file );


initGen( $collada_file );


$pop = $p->root_elements[0];

//Grab the collada version number
$_globals['constStrings']['COLLADA_VERSION'] = "\"". $pop->getAttribute('version') . "\";\n";
//Grab the collada namespace
$_globals['constStrings']['COLLADA_NAMESPACE'] = "\"". $pop->getAttribute('xmlns') . "\";\n\n";

// Grab simple types and collect meta-data for code-gen
$t_list = $pop->getElementsByType( 'xsSimpleType' );


$typemeta = array();

for( $i=0; $i<count( $t_list ); $i++ )
{
  $local_meta = & $t_list[$i]->generate();
  $typemeta[ $local_meta['type'] ] = & $local_meta;
  //print "Type: ". $local_meta['type'] ." created\n";
}

function propogateArrayTypes( &$lmeta ) {
	global $typemeta;
	if ( $lmeta['isArray'] ) {
		return;
	}
	if( isset( $typemeta[$lmeta['base']] ) ) {
		propogateArrayTypes( $typemeta[$lmeta['base']] );
		$lmeta['isArray'] = $typemeta[$lmeta['base']]['isArray'];
	}
	//print $lmeta['type'] ." isArray = ". $lmeta['isArray'] ."\n";
}
foreach( $typemeta as $k => &$local_meta ) {
	propogateArrayTypes( $local_meta );
}

//Grab global complex types and make them available for all who need them

$_globals['complex_types'] = $pop->getElementsByType( 'xsComplexType' );

//generate type meta data
//print applyTemplate( 'TYPES_HEADER_FILE', $typemeta );
//print applyTemplate( 'TYPES_CPP_FILE', $typemeta );

$element_context = array();
$meta = array();

print "COMPLEX TYPES\n";
for( $i=0; $i<count( $_globals['complex_types'] ); $i++ )
{
  $local_meta = & $_globals['complex_types'][$i]->generate( $element_context, $_globals['global_elements'] );
  $meta[ $local_meta['element_name'] ] = & $local_meta;
}

//collect element meta-data for code-gen

//Grab global groups and make them available for all who need them
$_globals['groups'] = $pop->getElementsByType( 'xsGroup' );
//collect meta-data for code-gen
print "GROUPS\n";
for( $i=0; $i<count( $_globals['groups'] ); $i++ )
{
  $local_meta = & $_globals['groups'][$i]->generate( $element_context, $_globals['global_elements'] );
  $meta[ $local_meta['element_name'] ] = & $local_meta;
}

// Grab global elements and collect meta-data for code-gen
$e_list = $pop->getElementsByType( 'xsElement' );

print "ELEMENTS\n";

for( $i=0; $i<count( $e_list ); $i++ )
{
  $local_meta = & $e_list[$i]->generate( $element_context, $_globals['global_elements'] );
  $meta[ $local_meta['element_name'] ] = & $local_meta;
}

//propogate the substitutableWith lists and attributes inherited by type
foreach( $meta as $k => &$local_meta ) {
	if ( $local_meta['substitution_group'] != '' ) {
		$meta[$local_meta['substitution_group']]['substitutableWith'][] = $k;
		//$meta[$local_meta['substitution_group']]['ref_elements'][] = $k;
		//print $local_meta['substitution_group'] ." sub with ". $k ."\n";
	}
}

$indentNum = 0;
// Generate header files
$includeList = array();
foreach( $meta as $k => &$local_meta )
{
  // Generate the dom
  print applyTemplate( 'HEADER_FILE', $local_meta );
  print applyTemplate( 'CPP_FILE', $local_meta );
}

print applyTemplate( 'TYPES_HEADER_FILE', $typemeta );
print applyTemplate( 'TYPES_CPP_FILE', $typemeta );

print applyTemplate( 'ELEMENTS_FILE', $meta );
print applyTemplate( 'CONSTANTS_FILE', $_globals['constStrings'] );
print applyTemplate( 'CONSTANTS_CPP_FILE', $_globals['constStrings'] );
cleanupGen();

?>
