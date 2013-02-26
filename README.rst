COLLADA Document Object Model (DOM) C++ Library
++++++++++++++++++++++++++++++++++++++++++++++++

Contents of COLLADA Package 
===========================

This package includes the COLLADA DOM.  Everything in this package can be built on any platform using CMake.  For additional 
information on tools and plugins that support COLLADA, or to get help from the Internet community of COLLADA users, please visit www.collada.org  

This project has been forked off of the bulkier `Sourceforce Collada Repository <http://sourceforge.net/projects/collada-dom/>`_ and maintains only the parser. 

The COLLADA DOM is a set of libraries for loading and saving COLLADA documents that can contain 2D, 3D, physics and other types of content. It allows developers 
to create applications that can exchange COLLADA documents with commercial content creation tools such as Maya, Max or Softimage.  

+--dom                      The DOM library for Parsing COLLADA Documents
|  +--codeGen
|  +--external-libs         Open source libraries used by DOM
|  +--include
|  +--license
|  +--src
|  +--...
|
+--License_Folder           Licenses for open source software included in COLLADA Viewer
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
