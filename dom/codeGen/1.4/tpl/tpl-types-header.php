<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

if ( $_globals['copyright'] ) {
print $_globals['copyright_text'];
}
?>
#ifndef __DOM_TYPES_H__
#define __DOM_TYPES_H__

#include <dae/daeDomTypes.h>

<?php
//BASIC TYPES
foreach( $bag as $type => $meta )
{
  if ( count( $meta['enum'] ) == 0 && !$meta['isComplex'] )  {
	if ( strlen( $meta['base'] ) > 0 ) { //has a base type
		if ( preg_match( "/xs\:/", $meta['base'] ) ) {
			$base = substr( $meta['base'], 3 );
			$pre = 'xs';
		}
		else {
			$base = $meta['base'];
			$pre = $_globals['prefix'];
		}
		if ( isset( $meta['documentation']['en'] ) ) {
			print applyTemplate( 'DOXYGEN', $meta['documentation']['en'] );
		}
		//special casing URIFragmentType to be a xsURI for automatic resolution
		if ( $type == 'URIFragmentType' ) {
			print "typedef xsAnyURI\t\tdomURIFragmentType;\n";
		}
		else {
		    print "typedef " . $pre . ucfirst($base) . "\t\t" . $_globals['prefix'] . ucfirst( $type ) . ";\n";
		}
	}
	elseif ( strlen( $meta['listType'] ) > 0 ) { //is a list type
		if ( isset( $meta['documentation']['en'] ) ) {
			print applyTemplate( 'DOXYGEN', $meta['documentation']['en'] );
		}
		if ( preg_match( "/xs\:/", $meta['listType'] ) ) {
			$lt = substr( $meta['listType'], 3 );
			print "typedef xs" . ucfirst($lt) . "Array\t\t" . $_globals['prefix'] . ucfirst( $type ) . ";\n";
		}
		else {
			$lt = $meta['listType'];
			print "typedef daeTArray<" . $_globals['prefix'] . ucfirst($lt) . ">\t\t" . $_globals['prefix'] . ucfirst( $type ) . ";\n";
		}
	}
  }
}

print "\n";

//ENUMS
foreach( $bag as $type => $meta )
{
  if ( count( $meta['enum'] ) > 0 )
  {
    if ( !$meta['useConstStrings'] ) {
		//Decided to name mangle the enum constants so they are more descriptive and avoid collisions
		if ( isset( $meta['documentation']['en'] ) ) {
			print applyTemplate( 'DOXYGEN', $meta['documentation']['en'] );
		}
		print "enum " . $_globals['prefix'] . ucfirst( $type ) . " {\n";
		for( $i = 0; $i < count( $meta['enum'] ); $i++ ) {
			$val = $meta['enum'][$i];
			$val = str_replace( '.', '_', $val );
			print "\t" . strtoupper( $type ) . "_" . $val;
			if ( isset( $meta['enum_value'][$i] ) ) {
				print " = ". $meta['enum_value'][$i];
			}
			//else if ($i==0) {
			//	print " = 1";
			//} 
			print ",";
			if ( isset( $meta['enum_documentation'][$i] ) ) {
				print "\t\t/**< ". $meta['enum_documentation'][$i] ." */";
			}			
			print "\n";
		}
		$cnt = count($meta['enum']);
		//if ( !isset($meta['enum_value'][0]) ) {
		//	$cnt++;
		//}
		print "\t". strtoupper( $type ) . "_COUNT = ". $cnt;
		print "\n};\n\n";
	}
	else {
		for( $i = 0; $i < count( $meta['enum'] ); $i++ ) {
			if ( isset( $meta['enum_documentation'][$i] ) ) {
				$_globals['constStrings'][] = "/**\n * ". $meta['enum_documentation'][$i] ."\n */\n";
			}
			$conststrnm = strtoupper( $type ) . "_" . $meta['enum'][$i];
			$conststr = "\"". $meta['enum'][$i] ."\";\n";
			$_globals['constStrings'][$conststrnm] = $conststr;
		}
		$_globals['constStrings'][] = "\n";
	}
  }
}

//UNIONS
foreach( $bag as $type => & $meta )
{
  if ( $meta['union_type'] )
  {
	if ( isset( $meta['documentation']['en'] ) ) {
		print applyTemplate( 'DOXYGEN', $meta['documentation']['en'] );
	}
	print "enum " . $_globals['prefix'] . ucfirst( $type ) . " {\n";
    
    //tokenize memberTypes string
    $types = explode( ' ', $meta['union_members'] );
    //look up the members
    $cnt = 1;
    foreach ( $types as $typeName ) {
		if ( isset( $bag[$typeName] ) && count($bag[$typeName]['enum']) > 0 ) {
			//print all of their enum children
			for( $i = 0; $i < count( $bag[$typeName]['enum'] ); $i++ ) {
				$val = $bag[$typeName]['enum'][$i];
				$val = str_replace( '.', '_', $val );
				if ( in_array( $val, $meta['enum'] ) ) {
					continue;
				}
				$meta['enum'][] = $val;
				print "\t" . strtoupper( $type ) . "_" . $val;
				if ( isset( $bag[$typeName]['enum_value'][$i] ) ) {
					print " = ". $bag[$typeName]['enum_value'][$i];
				}
				else if ($i==0) {
					print " = 1";
				} 
				print ",";
				if ( isset( $bag[$typeName]['enum_documentation'][$i] ) ) {
					print "\t\t/**< ". $bag[$typeName]['enum_documentation'][$i] ." */";
				}			
				print "\n";
				$cnt++;
			}
		}	
    }
    print "\t". strtoupper( $type ) . "_COUNT = ". $cnt;
	print "\n};\n\n";
  }
}

?>
//Element Type Enum
namespace COLLADA_TYPE
{
	const int
		NO_TYPE = 0,
		ANY = 1<?php
	foreach( $_globals['elementTypes'] as $num => $val )
		print ",\n\t\t". getUniqueName($val, $_globals['elementTypes']) ." = ". ($num+2);
	print ";"
?>

}

// Returns the total number of schema types/dom* classes
daeInt DLLSPEC colladaTypeCount();

#endif
