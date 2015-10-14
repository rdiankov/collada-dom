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
#include <dae.h>
#include <<?= $_globals['meta_prefix'] ?>/daeDom.h>
#include <<?= $_globals['prefix'] ?>/<?= $_globals['prefix'] . ucfirst( $bag['element_name'] ) . ".h" ?>>
#include <<?= $_globals['meta_prefix'] ?>/daeMetaCMPolicy.h>
#include <<?= $_globals['meta_prefix'] ?>/daeMetaSequence.h>
#include <<?= $_globals['meta_prefix'] ?>/daeMetaChoice.h>
#include <<?= $_globals['meta_prefix'] ?>/daeMetaGroup.h>
#include <<?= $_globals['meta_prefix'] ?>/daeMetaAny.h>
#include <<?= $_globals['meta_prefix'] ?>/daeMetaElementAttribute.h>

<?= applyTemplate( 'CPP_METHODS', $bag ) ?>
<?= applyTemplate( 'CPP_STATIC', $bag ) ?>