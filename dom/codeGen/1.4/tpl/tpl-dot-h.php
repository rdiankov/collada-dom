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
?>#ifndef __<?= $_globals['prefix'] . ucfirst( $bag['element_name'] ) ?>_h__
#define __<?= $_globals['prefix'] . ucfirst( $bag['element_name'] ) ?>_h__

#include <dae/daeDocument.h>
#include <<?= $_globals['prefix'] . '/' . $_globals['prefix'] ?>Types.h>
#include <<?= $_globals['prefix'] . '/' . $_globals['prefix'] ?>Elements.h>

<?php
global $includeList;
$includeList = array();
print applyTemplate( 'INCLUDES', $bag ) ?>
class DAE;

<?= applyTemplate( 'CLASS', $bag ) ?>

#endif
