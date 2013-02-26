<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

	global $meta;
	global $typemeta;

  $_context = $bag['context'];
  for( $i=0; $i<count( $_context ); $i++ )
  {
    $_context[$i] = $_globals['prefix'] . ucfirst( $_context[$i] );
  }
  $scoped_element = implode( '::', $_context );
  
  /*if ( $bag['has_any'] ) {
	foreach ( $meta as $nm => $lm ) {
		if ( !$lm['isAGroup'] && !$lm['isAComplexType'] && !$lm['abstract'] ) {
			print "#include <". $_globals['prefix'] ."/". $_globals['prefix'] . ucfirst($nm).".h>\n";
		}
	}
	print "\n";
  }*/
  
  if ( $scoped_element == "domCOLLADA" ) {
	print "extern daeString COLLADA_VERSION;\n";
	print "extern daeString COLLADA_NAMESPACE;\n\n";
  }
?><?= $_globals['meta_prefix'] ?>ElementRef
<?= $scoped_element ?>::create(DAE& dae)
{
	<?= $scoped_element ?>Ref ref = new <?= $scoped_element ?>(dae);
<?php
	if ( $bag['useXMLNS'] ) {
		print "\tref->attrXmlns.setContainer( (". $scoped_element ."*)ref );\n";
	}
	foreach( $bag['attributes'] as $attr_name => & $a_list ) {
		if ( $a_list['type'] == 'xs:anyURI' || $a_list['type'] == 'urifragment' ) {
			print "\tref->attr". ucfirst($attr_name) .".setContainer( (". $scoped_element ."*)ref );\n";
		}
	}
	if ( $bag['content_type'] == 'xs:anyURI' || $bag['content_type'] == 'urifragment' ) {
		print "\tref->_value.setContainer( (". $scoped_element ."*)ref );\n";
	}
	if ( $scoped_element == "domCOLLADA" ) {
		print "\tref->_meta = dae.getMeta(domCOLLADA::ID());\n";
		print "\tref->setAttribute(\"version\", COLLADA_VERSION );\n";
		print "\tref->setAttribute(\"xmlns\", COLLADA_NAMESPACE );\n";
		print "\tref->_meta = NULL;\n";
	}
?>
	return ref;
}

<?php
	if( ( $bag['complex_type'] && !$bag['isRestriction'] ) || isset($bag['baseTypeViaRestriction']) ) {
		//print "element ". $bag['element_name'] ." is of base ". $bag['base_type'] ."\n";
		//import content model from type
		$bag['elements'] = array_merge( $meta[$bag['base_type']]['elements'], $bag['elements'] );
		$bag['element_attrs'] = array_merge( $meta[$bag['base_type']]['element_attrs'], $bag['element_attrs'] );
		$bag['content_type'] = $meta[$bag['base_type']]['content_type'];
		$bag['attributes'] = array_merge( $meta[$bag['base_type']]['attributes'], $bag['attributes'] );
		$tempArray = array();
		if ( count( $bag['content_model'] ) > 0 ) {
			//we have an addition to the content model - need to add a starting sequence
			$tempArray[] = array( 'name' => 0, 'minOccurs' => 1, 'maxOccurs' => 1 );
		}
		$tempArray = array_merge( $tempArray, $meta[$bag['base_type']]['content_model'] );
		array_pop( $tempArray ); //remove the last END token
		$tempArray = array_merge( $tempArray, $bag['content_model'] );
		if ( count( $bag['content_model'] ) > 0 ) {
			//we have an addition to the content model - need to add a starting sequence
			$tempArray[] = array( 'name' => 5, 'minOccurs' => 1, 'maxOccurs' => 1 );
		}
		$bag['content_model'] = $tempArray;
	}
	
	for( $i=0; $i<count( $bag['elements'] ); $i++ )	{
		if ( isset( $meta[$bag['elements'][$i]] ) ) {
			$cnt = count( $meta[$bag['elements'][$i]]['substitutableWith']);
			for ( $c = 0; $c < $cnt; $c++ ) {
				$subwith = $meta[$bag['elements'][$i]]['substitutableWith'][$c];
				print $prefix ."#include <". $_globals['prefix'] ."/". $_globals['prefix'] . ucfirst( $subwith ) .".h>\n";
			}
		}
	}
?>

<?= $_globals['meta_prefix'] ?>MetaElement *
<?= $scoped_element ?>::registerElement(DAE& dae)
{
	<?= $_globals['meta_prefix'] ?>MetaElement* meta = dae.getMeta(ID());
	if ( meta != NULL ) return meta;

	meta = new daeMetaElement(dae);
	dae.setMeta(ID(), *meta);
	meta->setName( "<?= $bag['element_name'] ?>" );
	meta->registerClass(<?= $scoped_element ?>::create);

<?php
	if ( $bag['isAGroup'] ) {
		print "\tmeta->setIsTransparent( true );\n";
	}
	if ( $bag['abstract'] ) {
		print "\tmeta->setIsAbstract( true );\n";
	}
	if ( isset( $bag['parent_meta'] ) ) {
		print "\tmeta->setIsInnerClass( true );\n";
	}
	  
	if ( count( $bag['elements'] ) > 0 || $bag['has_any'] )
	{
		print "\tdaeMetaCMPolicy *cm = NULL;\n";
		if ( !$bag['has_any'] ) {
			print "\tdaeMetaElementAttribute *mea = NULL;\n";
		}
	
	  $needsContents = false;
	  $cmTree = array();
	  $currentCM = NULL;
	  $currentOrd = 0;
	  $level = 0;
	  $choiceNum = 0;

		// !!!steveT Hack alert. In the 1.5 schema there's a single element named
		// 'sampler_states_type' that contains a group ref that isn't contained in
		// an xs:sequence or xs:choice the way all the other group refs are. The
		// code generator handles this case incorrectly. It outputs code that causes
		// the DOM to crash when you try to create a DAE object.
		//
		// Unfortunately I don't know what the proper solution is, so this hack just
		// detects that specific group ref type and treats it like it's in an
		// xs:sequence. I have no idea if that's right, but it causes the DOM not to
		// crash anyway.
		$containsGroup = false;
		$containsOther = false;
		for( $i=0; $i<count( $bag['content_model'] ) - 1; $i++ ) {
			$cm = $bag['content_model'][$i];
			if(is_int($cm['name']) && $cm['name'] == 2)
				$containsGroup = true;
			if(is_int($cm['name']) && $cm['name'] != 2)
				$containsOther = true;
		}
		$hack = $containsGroup && !$containsOther;

	  for( $i=0; $i<count( $bag['content_model'] ) - 1; $i++ )
	  {
		$cm = $bag['content_model'][$i]; 
		if ( $cm['maxOccurs'] == "unbounded" ) 
		{
			$cm['maxOccurs'] = -1; 
		}
		if ( is_int( $cm['name'] ) )
		{
			if ( $cm['name'] == 0 || ($i == 0 && $hack)) //sequence
			{
				//if ( $level > 0 ) {
				//	$needsContents = true;
				//}

				// !!!steveT Horrible hack here. For some reason the wrong value gets generated for 
				// the third parameter
				if (strcmp($scoped_element, "domCamera::domOptics::domTechnique_common::domPerspective") == 0)
					print "\tcm = new daeMetaSequence( meta, cm, 0, ". $cm['minOccurs'] .", ". $cm['maxOccurs'] ." );\n\n";
				else 
					print "\tcm = new daeMetaSequence( meta, cm, ". $currentOrd .", ". $cm['minOccurs'] .", ". $cm['maxOccurs'] ." );\n\n";

				$level++;
				$currentCM = array( 'cm' => $currentCM['cm'], 'ord' => $currentOrd );
				array_push( $cmTree, $currentCM );
				$currentCM = array( 'cm' => $cm, 'ord' => $currentOrd );
				$currentOrd = 0;
			}
			else if ( $cm['name'] == 1 ) //choice
			{
				print "\tcm = new daeMetaChoice( meta, cm, ". $choiceNum .", ". $currentOrd .", ". $cm['minOccurs'] .", ". $cm['maxOccurs'] ." );\n\n";
				$level++;
				$needsContents = true;
				$currentCM = array( 'cm' => $currentCM['cm'], 'ord' => $currentOrd );
				array_push( $cmTree, $currentCM );
				$currentCM = array( 'cm' => $cm, 'ord' => $currentOrd );
				$currentOrd = 0;
				$choiceNum++;
			}
			else if ( $cm['name'] == 2 ) //group
			{
				$i++; //groups actually add two parts to the content model. The first is the group the second an element
				$groupName = $bag['content_model'][$i]['name'];
				$arrayOrNot = $bag['element_attrs'][ $groupName ]['maxOccurs'];
				if ( $arrayOrNot == 'unbounded' || $arrayOrNot > 1 ) {
					$arrayOrNot = true;
				}
				else {
					$arrayOrNot = false;
				}
?>
	mea = new daeMetaElement<?= $arrayOrNot ? 'Array' : '' ?>Attribute( meta, cm, <?= $currentOrd ?>, <?= $cm['minOccurs'] ?>, <?= $cm['maxOccurs'] ?> );
	mea->setName( "<?= $groupName ?>" );
	mea->setOffset( daeOffsetOf(<?= $scoped_element ?>,elem<?= ucfirst( $groupName ) ?><?= $arrayOrNot ? '_array' : '' ?>) );
	mea->setElementType( <?= $_globals['prefix'] . ucfirst( $groupName ) ?>::registerElement(dae) );
	cm->appendChild( new daeMetaGroup( mea, meta, cm, <?= $currentOrd ?>, <?= $cm['minOccurs'] ?>, <?= $cm['maxOccurs'] ?> ) );

<?php		
				if ( $currentCM['cm']['name'] == 0 ) {
					$currentOrd++;
				}		
			}
			else if ( $cm['name'] == 3 ) //all
			{
				//print "\tcm = new daeMetaAll( meta, cm, ". $cm['minOccurs'] .", ". $cm['maxOccurs'] ." );\n";
				$level++;
				$needsContents = true;
				$currentCM = array( 'cm' => $currentCM['cm'], 'ord' => $currentOrd );
				array_push( $cmTree, $currentCM );
				$currentCM = array( 'cm' => $cm, 'ord' => $currentOrd );
				$currentOrd = 0;
			}
			else if ( $cm['name'] == 4 ) //any
			{
				$level++;
				print "\tcm = new daeMetaAny( meta, cm, ". $currentOrd .", ". $cm['minOccurs'] .", ". $cm['maxOccurs'] ." );\n\n";
				if ( $currentCM['cm']['name'] == 0 ) {
					$currentOrd++;
				}
			}
			else if ( $cm['name'] == 5 ) //end
			{
				$level--;
				if ( $level > 0 )
				{
?>
	cm->setMaxOrdinal( <?= ($currentOrd-1 >= 0)? $currentOrd-1 : 0 ?> );
	cm->getParent()->appendChild( cm );
	cm = cm->getParent();

<?php
				}
				//----------------------
				if ( $currentCM['cm']['name'] == 0 ) {
					$tempMaxO = $currentCM['cm']['maxOccurs'];
					$currentCM = array_pop( $cmTree );
					if ( $tempMaxO == -1 ) {
						$currentOrd = $currentCM['ord'] + 3000;
					}
					else {
						$currentOrd = $currentCM['ord'] + $tempMaxO*$currentOrd;
					}
				}
				else {
					$tempMaxO = $currentCM['cm']['maxOccurs'];
					if ( $tempMaxO == -1 ) $tempMaxO = 3001;
					$currentCM = array_pop( $cmTree );
					$currentOrd = $currentCM['ord'] + $tempMaxO;
				}
			}
		}
		else //got an element name
		{
			$arrayOrNot = $bag['element_attrs'][ $cm['name'] ]['maxOccurs'];
			if ( $arrayOrNot == 'unbounded' || $arrayOrNot > 1 ) {
				$arrayOrNot = true;
			}
			else {
				$arrayOrNot = false;
			}
			$typeClass = $_globals['prefix'] . ucfirst( $cm['name'] );

			if ( !in_array( $cm['name'], $bag['ref_elements'] ) && !$bag['complex_type'] ) {
				$typeClass = $scoped_element ."::". $typeClass;
			}
			if ( isset( $bag['element_attrs'][ $cm['name'] ]['type'] ) &&
				isset( $meta[$bag['element_attrs'][ $cm['name'] ]['type']] ) ){
			
				$typeClass = $_globals['prefix'] . ucfirst( $bag['element_attrs'][ $cm['name'] ]['type'] );
			}
?>
	mea = new daeMetaElement<?= $arrayOrNot ? 'Array' : '' ?>Attribute( meta, cm, <?= $currentOrd ?>, <?= $cm['minOccurs'] ?>, <?= $cm['maxOccurs'] ?> );
	mea->setName( "<?= $cm['name'] ?>" );
	mea->setOffset( daeOffsetOf(<?= $scoped_element ?>,elem<?= ucfirst( $cm['name'] ) ?><?=  $arrayOrNot ? '_array' : '' ?>) );
	mea->setElementType( <?= $typeClass ?>::registerElement(dae) );
	cm->appendChild( mea );

<?php
			if ( isset( $meta[$cm['name']] ) ) {
				$cnt = count( $meta[$cm['name']]['substitutableWith']);
				for ( $c = 0; $c < $cnt; $c++ ) {
					$subwith = $meta[$cm['name']]['substitutableWith'][$c];
?>    
	mea = new daeMetaElement<?= $arrayOrNot ? 'Array' : '' ?>Attribute( meta, cm, <?= $currentOrd ?>, <?= $cm['minOccurs'] ?>, <?= $cm['maxOccurs'] ?> );
	mea->setName( "<?= $subwith ?>" );
	mea->setOffset( daeOffsetOf(<?= $scoped_element ?>,elem<?= ucfirst( $cm['name'] ) ?><?= $arrayOrNot ? '_array' : '' ?>) );
	mea->setElementType( <?= $_globals['prefix'] . ucfirst( $subwith ) ?>::registerElement(dae) );
	cm->appendChild( mea );

<?php
					$needsContents = true;
				}
			}
			if ( $currentCM['cm']['name'] == 0 ) {
				$currentOrd++;
			}
		}
	  }
?>
	cm->setMaxOrdinal( <?= ($currentOrd-1 >= 0)? $currentOrd-1 : 0 ?> );
	meta->setCMRoot( cm );	
<?php
	  
	  if ( $bag['has_any'] ) {
		$needsContents = true;
		print "\tmeta->setAllowsAny( true );\n";
	  }
	  
      // For elements that allow more than one type of sub-element, _contents keeps an order for those sub-elements
	  if ( $bag['hasChoice'] || $needsContents ) {
?>
	// Ordered list of sub-elements
	meta->addContents(daeOffsetOf(<?= $scoped_element ?>,_contents));
	meta->addContentsOrder(daeOffsetOf(<?= $scoped_element ?>,_contentsOrder));

<?php
		if ( $choiceNum > 0 )
		{
?>
	meta->addCMDataArray(daeOffsetOf(<?= $scoped_element ?>,_CMData), <?= $choiceNum ?>);<?php
		}
      }
    }

	// TAKE CARE OF THE ENUM IF IT HAS ONE!!
	if ( $bag['simple_type'] != NULL ) {
		$typeMeta = $bag['simple_type']->getMeta();
		
		if ( count( $typeMeta['enum'] ) > 0 && !$typeMeta['useConstStrings'] )
		{
?>
	// ENUM: <?= ucfirst( $typeMeta['type'] ) ?>_type
	daeAtomicType *type;
	type = new daeEnumType;
	type->_nameBindings.append("<?= ucfirst( $typeMeta['type'] ) ?>_type");
	((daeEnumType*)type)->_strings = new daeStringRefArray;
	((daeEnumType*)type)->_values = new daeEnumArray;
<?php
			foreach( $typeMeta['enum'] as $val )
			{
?>
	((daeEnumType*)type)->_strings->append("<?= $val ?>");
	((daeEnumType*)type)->_values->append(<?= strtoupper($typeMeta['type']) . "_" . $val ?>);    
<?php
			}
			print "\tdaeAtomicType::append( type );\n\n";
		}
	}
	
	// NOTE: special casing any element with 'mixed' content model to ListOfInts type _value
	$pre = '';
	if (($bag['content_type'] != '' || $bag['mixed']) && !$bag['abstract'] ) {
?>
	//	Add attribute: _value
	{
<?php
	$content_type = ( $bag['mixed'] ? 'ListOfInts' : $bag['content_type'] );
	if ( preg_match( "/xs\:/", $content_type ) ) {
		$content_type = substr( $content_type, 3 );
		$pre = 'xs';
	}
	//print "\t\tdaeMetaAttribute* ma = daeMetaAttribute::makeAttrForType(\"". ucfirst($content_type) ."\");\n";
	if ( (isset( $typemeta[$content_type] ) && $typemeta[$content_type]['isArray']) || $content_type == 'IDREFS' ) {
		print "\t\tdaeMetaAttribute *ma = new daeMetaArrayAttribute;\n";
	}
	else {
		print "\t\tdaeMetaAttribute *ma = new daeMetaAttribute;\n";
	}
?>
		ma->setName( "_value" );
<?php
		//if ( $bag['mixed'] ) {
		//	print "#ifdef POLYGONS_MIXED_CONTENT_MODEL_HOLES\n\t\tma->setType( daeAtomicType::get(\"ListOfStrings\"));\n";
		//	print "#else\n\t\tma->setType( daeAtomicType::get(\"ListOfInts\"));\n#endif\n";
		//}
		//else {
			print "\t\tma->setType( dae.getAtomicTypes().get(\"". $pre. ucfirst($content_type) ."\"));\n";
		//}
?>
		ma->setOffset( daeOffsetOf( <?= $scoped_element ?> , _value ));
		ma->setContainer( meta );
		meta->appendAttribute(ma);
	}
<?php
    }
    
    if ( $bag['useXMLNS'] ) {
    ?>
	//	Add attribute: xmlns
	{
		daeMetaAttribute* ma = new daeMetaAttribute;
		ma->setName( "xmlns" );
		ma->setType( dae.getAtomicTypes().get("xsAnyURI"));
		ma->setOffset( daeOffsetOf( <?= $scoped_element ?> , attrXmlns ));
		ma->setContainer( meta );
		//ma->setIsRequired( true );
		meta->appendAttribute(ma);
	}
    <?php
    }

	foreach( $bag['attributes'] as $attr_name => $attr_attrs )
	{
		$_type = $attr_attrs['type'];
		$printType;
		if ( preg_match( "/xs\:/", $_type ) ) { 
			$_type = 'xs' . ucfirst( substr( $_type, 3 ) ); 
			$printType = $_type;
		}
		else {
			$printType = ucfirst( $_type );
		}
?>

	//	Add attribute: <?= $attr_name . "\n" ?>
	{
<?php
		/*print "\t//". $_type ." is set ";
		if ( isset( $typemeta[$_type] ) ) print "true\n";
		else print "false\n";
		
		print "\t//is array ";
		if ( $typemeta[$_type]['isArray'] ) print "true\n";
		else print "false\n";*/
		
		if ( isset( $typemeta[$_type] ) && $typemeta[$_type]['isArray'] ) {
		print "\t\tdaeMetaAttribute *ma = new daeMetaArrayAttribute;\n";
	}
	else {
		print "\t\tdaeMetaAttribute *ma = new daeMetaAttribute;\n";
	}
?>
		ma->setName( "<?= $attr_name ?>" );
		ma->setType( dae.getAtomicTypes().get("<?= $printType ?>"));
		ma->setOffset( daeOffsetOf( <?= $scoped_element ?> , attr<?= ucfirst($attr_name) ?> ));
		ma->setContainer( meta );
<?php
		if ( isset( $attr_attrs['default'] ) )
		{
?>		ma->setDefaultString( "<?= $attr_attrs['default'] ?>");
<?php
		}
		
		if ( isset( $attr_attrs['use'] ) ) {
			$required = $attr_attrs['use'] == 'required' ? 'true' : 'false';

?>		ma->setIsRequired( <?= $required ?> );
<?php
	    }
?>	
		meta->appendAttribute(ma);
	}
<?php
    }
?>

	meta->setElementSize(sizeof(<?= $scoped_element ?>));
	meta->validate();

	return meta;
}

<?php
  $_keys = array_keys( $bag['inline_elements'] );
  if ( count( $_keys ) > 0 )
  {
    foreach( $_keys as $_k )
    {
      $inner = $bag['inline_elements'][ $_k ];
	  if ( !$inner['complex_type'] || $inner['isRestriction'] || $inner['isExtension'] ) {
		print applyTemplate( 'CPP_METHODS', $inner );
	  }
    }
  }
?>