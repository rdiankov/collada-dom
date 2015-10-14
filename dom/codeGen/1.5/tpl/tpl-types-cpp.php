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
#include <<?= $_globals['prefix'] . '/' . $_globals['prefix'] ?>Types.h>
#include <dae/daeDom.h>
#include <dom/domCOLLADA.h>

<?php

foreach( $bag as $type => $meta )
{
  if ( $meta['isComplex'] ) {
  ?>#include <<?= $_globals['prefix'] . '/' . $_globals['prefix'] . ucfirst( $type ) ?>.h>
<?php
  }
}

?>

void registerDomTypes(DAE& dae)
{
	daeAtomicType* type = NULL;
	daeAtomicTypeList& atomicTypes = dae.getAtomicTypes();

<?php

foreach( $bag as $type => $meta )
{
  if ( count( $meta['enum'] ) > 0 && !$meta['useConstStrings'] )
  {?>
	// ENUM: <?= ucfirst( $type ) ?>

	type = new daeEnumType(dae);
	type->_nameBindings.append("<?= ucfirst( $type ) ?>");
	((daeEnumType*)type)->_strings = new daeStringRefArray;
	((daeEnumType*)type)->_values = new daeEnumArray;
<?php
    foreach( $meta['enum'] as $val )
    {?>
	((daeEnumType*)type)->_strings->append("<?= $val ?>");
<?php $val = str_replace( '.', '_', $val ); ?>
	((daeEnumType*)type)->_values->append(<?= strtoupper($type) . "_" . $val ?>);
<?php
    }
    print "\tatomicTypes.append( type );\n\n";
  }
  elseif ( $meta['isComplex'] ) {
  ?>
	// COMPLEX TYPE: <?= ucfirst( $type ) ?>

	type = new daeElementRefType(dae);
	type->_nameBindings.append("<?= ucfirst( $type ) ?>");
	atomicTypes.append( type );

<?php
  }
  /*else if ( $meta['union_type'] ) { //union type
	?>
	// ENUM: <?= ucfirst( $type ) ?>
	
	type = new daeEnumType;
	type->_nameBindings.append("<?= ucfirst( $type ) ?>");
	((daeEnumType*)type)->_strings = new daeStringRefArray;
	((daeEnumType*)type)->_values = new daeEnumArray;
<?php
	$types = explode( ' ', $meta['union_members'] );
	foreach ( $types as $typeName ) {
		if ( isset( $bag[$typeName] ) && count($bag[$typeName]['enum']) > 0 ) {
			foreach( $bag[$typeName]['enum'] as $val )
    {?>
	((daeEnumType*)type)->_strings->append("<?= $val ?>");
<?php $val = str_replace( '.', '_', $val ); ?>
	((daeEnumType*)type)->_values->append(<?= strtoupper($type) . "_" . $val ?>);
<?php
			}
		}
    }
    print "\tatomicTypes.append( type );\n\n";
  }  */
  else if ( !$meta['useConstStrings'] ) { //standard typedef
	$base = strlen( $meta['base'] ) > 0 ? $meta['base'] : $meta['listType'];
	if ( preg_match( "/xs\:/", $base ) ) {
		$base = 'xs' . ucfirst( substr( $base, 3 ) );
	}
	else {
		$base = ucfirst( $base );
	}
  ?>
	// TYPEDEF: <?= ucfirst( $type ) ?>
	//check if this type has an existing base
<?php 
	//special casing urifragment to be a xsURI for automatic resolution
	if ( $type == 'urifragment' ) {
		print "\ttype = atomicTypes.get(\"xsAnyURI\");\n";
	}
	else {
		print "\ttype = atomicTypes.get(\"". $base ."\");\n";
	}
?>
	if ( type == NULL ) { //register as a raw type
		type = new daeRawRefType(dae);
		type->_nameBindings.append("<?= ucfirst( $type ) ?>");
		atomicTypes.append( type );
	}
	else { //add binding to existing type
		type->_nameBindings.append("<?= ucfirst( $type ) ?>");
	}
	
<?php
  }
}
?>
}

daeMetaElement* registerDomElements(DAE& dae)
{
	daeMetaElement* meta = domCOLLADA::registerElement(dae);
	// Enable tracking of top level object by default
	meta->setIsTrackableForQueries(true);
	return meta;	
}

daeInt DLLSPEC colladaTypeCount() {
	return <?php /* +1 for <any> */ print ($_globals['typeID']+1); ?>;
}
