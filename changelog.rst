Collada DOM Change Log
----------------------

2.5.1
=====

- remove unsafe xmlCleanupParser call
  
2.5.0
=====

- add new method writeToMemory that saves XML to std::vector<char> instead of file.

2.4.4
=====

- add using liburiparser library for parsing URIs

- daeIDResolverType::compare now compares with getID
  
2.4.3
=====

- fix DAE::open for collada 1.4.1

- fix usage of COLLADA_DOM_DAEFLOAT_IS64 when serializing float values to string.

2.4.2
=====

- fix libxml read filepath bug with %20

2.4.1
=====

- Using boost filesystem v3 for better temp directory creation

- fix local cmake libpcre compilation

2.4.0
=====

[New Features]

- All dom classes are now in their own namespaces making it possible to use 1.4.1 and 1.5.0 simultaneously! namespaces are ColladaDOM141 and ColladaDOM150::

  dae=DAE(NULL,NULL,"1.4.1");
  dae2=DAE(NULL,NULL,"1.5.0");

- New dom namespaces are not automatically put into the global scope, meaning that domCOLLADA should not be converted to ColladaDOM141::domCOLLADA or ColladaDOM150::domCOLLAdA

- Users can define COLLADA_DOM_USING_141 or COLLADA_DOM_USING_150 before any collada-dom includes in order to get an automatic "using namespace ColladaDOMXX".

- If COLLADA_DOM_NAMESPACE is not defined, will call **using namespace ColladaDOMXXX** on the highest version available. If not, it will not clutter the global namespace with dom definition.

- The DAE constructor takes in a string for the collada version to support. Current supported are "1.4.1" and "1.5.0"

- The following DAE methods need to be manually casted to the correct domCOLLADA version: DAE::add, DAE::open, DAE::openFromMemory, DAE::getRoot, DAE::setRoot, DAE::getDom, DAE::setDom. For backward compat, added DAE::add141, DAE::add150, etc. In otherwords::

  domCOLLADA* dom = (domCOLLADA*)dae->open();
  ColladaDOM150::domCOLLADA* dom = (ColladaDOM150::domCOLLADA*)dae->open();
  ColladaDOM150::domCOLLADA* dom = daeSafeCast<ColladaDOM150::domCOLLADA>(dae->open());
  ColladaDOM150::domCOLLADA* dom = dae->open150();

- Included a double-precision flag COLLADA_DOM_DAEFLOAT_IS64 to compile collada-dom without any floating point. When using cmake, do -DOPT_DOUBLE_PRECISION=ON

- DAE::writeX support writing to compressed ZAE files using zlib and minizip. Automatically enabled when extension is zae.

---------------
collada-dom 2.3
---------------

2.3.1
=====

[New Features]

- added cmake configuration scripts to find the collada-dom installs through cmake easier

- added cpack generation and debian source package install preparation for cmake

- windows compiles with cmake, forcing to use boost DLL, etc, all libraries are suffixed with VC version

- added pcre-8.02 and zlib 3rdparty library sources to be used for static linking

- added latest libxml2 library along with vc80, vc90, and vc100 DLLs.

2.3.0
=====

[New Features]

- Added cmake support. cmake now produces *.pc files direclty usable in Linux's package config: collada14dom, collada15dom. Use the cmake-modules/FindCOLLADA.cmake when searching for the collada installation.

- Added newer version of minizip v1.1. 

[Bug Fix]

- Fixed two problems in SID resolution which was prevent collada 1.5 robot files from being loaded correctly.


[Known Bugs/Restrictions]

- Currently it is not possible to use both 1.4 and 1.5 in the same executable. This issue might addressed in the future with namespaces. This means collada-dom has to offer two different pkg-config files, one for 1.4 and one for 1.5.

2.2.0
=====

- Added samples.doc to describe features of sample COLLADA documents

- Revised readme.txt for Linux, Mac support


[Bug Fix]

- Major memory leak fixes

- Numerous minor bug fixes 
