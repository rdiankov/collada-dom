<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

  // INCLUDES
  $inc = array();
  global $includeList;
  for( $i=0; $i<count( $bag['ref_elements'] ); $i++ )
  {
	if ( !in_array( $_globals['prefix'] . ucfirst( $bag['ref_elements'][$i] ), $includeList ) ) {
		$inc[] = "#include <" . $_globals['prefix'] . "/" . $_globals['prefix'] . ucfirst( $bag['ref_elements'][$i] ) . ".h>"; 
		$includeList[] = $_globals['prefix'] . ucfirst( $bag['ref_elements'][$i] );
	}
  }
  if ( count( $inc ) ) { //only print if you have to include something
	print implode( "\n", $inc ) . "\n";
  }
  
  $keys = array_keys( $bag['inline_elements'] );
  if ( count( $keys ) > 0 )
  {
    foreach( $keys as $k )
    {
      print applyTemplate( 'INCLUDES', $bag['inline_elements'][ $k ] );
    }
  }
?>