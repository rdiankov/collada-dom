<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

// Grab the file name and save the output
$_file = $_globals['prefix'] . "Constants.h";
$_bytes = file_put_contents( ($_globals['target_dir'] . $_globals['dom_dir']. 'include/' . $_file), applyTemplate( 'CONSTANTS', $bag ) );

//if ( $_bytes > 0 ) { print "Wrote $_bytes bytes to file '$_file'\n"; }

?>