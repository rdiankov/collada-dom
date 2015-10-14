<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

  $_context = $bag['context'];
  for( $i=0; $i<count( $_context ); $i++ )
  {
    $_context[$i] = $_globals['prefix'] . ucfirst( $_context[$i] );//. "_element";
  }
?>
<?php
//}
  $keys = array_keys( $bag['inline_elements'] );
  if ( count( $keys ) > 0 )
  {
    foreach( $keys as $k )
    {
      $inner = $bag['inline_elements'][ $k ];
	  if ( !$inner['complex_type'] || $inner['isRestriction'] || $inner['isExtension'] ) {
		print applyTemplate( 'CPP_STATIC', $inner );
	  }
    }
  }
?>