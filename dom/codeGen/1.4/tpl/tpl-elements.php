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
#ifndef __DOM_ELEMENTS_H__
#define __DOM_ELEMENTS_H__

#include <<?= $_globals['prefix'] ?>/<?= $_globals['prefix'] ?>Types.h>

<?php
//Base elements
foreach( $bag as $name => $meta ) {
	$full_element_name = $_globals['prefix'] . ucfirst( $name );
	print "class " . $full_element_name . ";\n\n";
	print "typedef daeSmartRef<". $full_element_name ."> ". $full_element_name ."Ref;\n";
	print "typedef daeTArray<". $full_element_name ."Ref> ". $full_element_name ."_Array;\n\n";
}

?>

#endif //__DOM_ELEMENTS_H__

