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
#ifndef __DOM_CONSTANTS_H__
#define __DOM_CONSTANTS_H__

#include <dae/daeDomTypes.h>

<?php
	foreach ($bag as $name => $val ) {
		if ( is_int($name) ) {
			print $val;
			continue;
		}
		print "extern DLLSPEC daeString ". $name .";\n";
	}
	print "\n";
	
	foreach ($_globals['elementTypes'] as $num => $val )
	{
		print "extern DLLSPEC daeString COLLADA_TYPE_". getUniqueName($val, $_globals['elementTypes']) .";\n";
	}
	print "\n";
	
	foreach ($_globals['elementNames'] as $num => $val )
	{
		print "extern DLLSPEC daeString COLLADA_ELEMENT_". getUniqueName($val, $_globals['elementNames']) .";\n";
	}
?>

#endif //__DOM_CONSTANTS_H__

