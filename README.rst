COLLADA Document Object Model (DOM) C++ Library
++++++++++++++++++++++++++++++++++++++++++++++++

Contents of COLLADA Package
===========================

The COLLADA Document Object Model (DOM) is an application programming interface (API) that provides a C++ object representation of a COLLADA XML instance document.

This project is a library for loading and saving COLLADA documents that can contain 2D, 3D, physics and other types of content. It allows developers 
to create applications that can exchange COLLADA documents with commercial content creation tools such as Maya, Max or Softimage.  

This project is a very lightweight version of the `Sourceforce Collada Repository <http://sourceforge.net/projects/collada-dom/>`_. It maintains only the base collada parser with minimal dependencies.

Everything in this package can be built on any platform using CMake.  For additional information on
tools and plugins that support COLLADA, or to get help from the Internet community of COLLADA users,
please visit www.collada.org


`Online Documentation <http://collada.org/mediawiki/index.php/Portal:COLLADA_DOM>`_

Questions, bug reports and feature requests should go to our `GitHub site <https://github.com/rdiankov/collada-dom>`_

The binary packages only contain release builds. If you need to debug the DOM you should download
the source and build it on your machine. The Visual Studio packages include debug builds because you
can't link a release DOM into a debug app with that compiler. The debug info has been stripped
though to keep the download size down.

+--dom                      The DOM library for Parsing COLLADA Documents
|  +--codeGen
|  +--external-libs         Open source libraries used by DOM
|  +--include
|  +--license
|  +--src
|  +--...
|
+--licenses           Licenses for open source software included in COLLADA Viewer
|  +--other
|  +--license_e.txt
|
+--README.rst               This file


Building the COLLADA package
============================

Using CMake, so can build on Linux, Windows, and MAC OSX::

  mkdir -p build
  cd build
  cmake ..
  make
  make install

By default both 1.5 and 1.4 libraries will be installed.

Copyright Notices
=================

Original PLAYSTATION(R)3 COLLADA Package (up to version 2.2) is:

            Copyright (C) 2009 Sony Computer Entertainment Inc.
                    All Rights Reserved.

Special thanks to the following people for their contributions
==============================================================

Heinrich Fink (Mac support)
JT Anderson (Tinyxml support)
Michel Briand (Linux shared library support)
Kai Klesatschke (bug reporting, Windows character encoding fixes)
Guy Rabiller (bug reporting)
Rodrigo Hernandez (MinGW support)
Alex De Pereyra (bug reporting)
Alberto Luaces (bug reporting)
Michael Wojcik (Visual Studio 2010 Support)
Rosen Diankov (patches for 1.5, cmake support, collada namespaces, github maintenance)

Apologies if I missed anyone (please let me know!).

steven_thomas@playstation.sony.com
2008 Sony Computer Entertainment, Inc
