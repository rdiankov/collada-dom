<?php
/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

require_once( 'om/object-model.php' );

class SchemaParser
{
  var $parse_stack = array();
  var $root_elements = array();
  var $parser;

  function SchemaParser()
  {
    $this->parser = xml_parser_create();

    xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, false );
    xml_set_object( $this->parser, $this );
    xml_set_element_handler( $this->parser, "startElement", "endElement" );
    xml_set_character_data_handler( $this->parser, "characterData" );
    
    //xml_parser_free( $this->parser );
  }
  
  function startElement( $parser, $name, $attrs )
  {
    if ( preg_match( "/xs\:/", $name ) )
    {
      $class_name = substr( $name, 3 );
      $class_name = 'xs' . ucfirst( $class_name );

      eval( '$e = new ' . $class_name . '();' );
      foreach( $attrs as $k => $v ) { $e->setAttribute( $k, $v ); }
    
      if ( count( $this->parse_stack ) > 0 )
      {
        $this->parse_stack[ count( $this->parse_stack ) - 1 ]->addElement( $e );
      } else
      {
        $this->root_elements[] = & $e;
      }
      $this->parse_stack[] = & $e;
    }
  }

  function endElement( $parser, $name )
  {
    $pop = & array_pop( $this->parse_stack );
  }

  function characterData( $parser, $data )
  {
    if ( count( $this->parse_stack ) > 0 )
    {
        $this->parse_stack[ count( $this->parse_stack ) - 1 ]->append( $data );
    }
  }

  function parse( $file )
  {
    if ( file_exists( $file ) )
    {
      if ( !xml_parse( $this->parser, file_get_contents( $file ) ) )
      {
        // Got parse error
      }
    } else
    {
      // Bad file
    }
  }
  
}

?> 