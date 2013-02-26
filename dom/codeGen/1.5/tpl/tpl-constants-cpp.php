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
#include <dom/domConstants.h>

<?php
	foreach ($bag as $name => $val ) {
		if ( is_int($name) ) {
			print $val;
			continue;
		}
		print "DLLSPEC daeString ". $name ." = ". $val;
	}
	print "\n";
	
	foreach ($_globals['elementTypes'] as $num => $val )
	{
		print "DLLSPEC daeString COLLADA_TYPE_". getUniqueName($val, $_globals['elementTypes']) ." = \"". $val ."\";\n";
	}
	print "\n";
	
	foreach ($_globals['elementNames'] as $num => $val )
	{
		print "DLLSPEC daeString COLLADA_ELEMENT_". getUniqueName($val, $_globals['elementNames']) ." = \"". $val ."\";\n";
	}
?>
