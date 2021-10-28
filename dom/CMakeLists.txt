set(COLLADA_INTERNAL_INCLUDE ${CMAKE_CURRENT_SOURCE_DIR}/include)
include_directories(${COLLADA_INTERNAL_INCLUDE})
set(COLLADA_LIBS minizip ${liburiparser_LIBRARIES} ${libpcrecpp_LIBRARIES} ${ZLIB_LIBRARIES} ${Boost_FILESYSTEM_LIBRARY} ${Boost_SYSTEM_LIBRARY})

file(GLOB dae_files ${CMAKE_CURRENT_SOURCE_DIR}/src/dae/*.cpp)
if( LIBXML2_FOUND )
  file(GLOB libxmlplugin_files ${CMAKE_CURRENT_SOURCE_DIR}/src/modules/LIBXMLPlugin/*.cpp)
  set(COLLADA_LIBS ${COLLADA_LIBS} ${LIBXML2_LIBRARIES})
else()
  set(libxmlplugin_files)
endif()
file(GLOB stddatabase_files ${CMAKE_CURRENT_SOURCE_DIR}/src/modules/STLDatabase/*.cpp)
file(GLOB stderrplugin_files ${CMAKE_CURRENT_SOURCE_DIR}/src/modules/stdErrPlugin/*.cpp)

set(COLLADA_BASE_SOURCES ${dae_files} ${libxmlplugin_files} ${stddatabase_files} ${stderrplugin_files})
set(COLLADA_COMPILE_FLAGS "${liburiparser_CFLAGS_OTHERS} ${libpcrecpp_CFLAGS_OTHERS} ${EXTRA_COMPILE_FLAGS} ${Boost_CFLAGS}")
# create dynamic libraries
set(COLLADA_COMPILE_FLAGS "${COLLADA_COMPILE_FLAGS} -DDOM_DYNAMIC -DDOM_EXPORT")

if( OPT_COLLADA15 )
  add_subdirectory(src/1.5)
  set(COLLADA_LIBS ${COLLADA_LIBS} colladadom150)
  install(DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/include/1.5 DESTINATION ${COLLADA_DOM_INCLUDE_INSTALL_DIR} COMPONENT ${COMPONENT_PREFIX}-dev  PATTERN ".svn" EXCLUDE PATTERN ".~" EXCLUDE)
endif()
if( OPT_COLLADA14 )
  add_subdirectory(src/1.4)
  set(COLLADA_LIBS ${COLLADA_LIBS} colladadom141)
  install(DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/include/1.4 DESTINATION ${COLLADA_DOM_INCLUDE_INSTALL_DIR} COMPONENT ${COMPONENT_PREFIX}-dev  PATTERN ".svn" EXCLUDE PATTERN ".~" EXCLUDE)
endif()

add_library(collada-dom SHARED ${COLLADA_BASE_SOURCES})
target_include_directories(collada-dom PUBLIC ${COLLADA_INTERNAL_INCLUDE})
target_link_libraries(collada-dom ${COLLADA_LIBS})
set_target_properties(collada-dom PROPERTIES
  COMPILE_FLAGS "${COLLADA_COMPILE_FLAGS}"
  LINK_FLAGS "${libpcrecpp_LDFLAGS_OTHERS}"
  OUTPUT_NAME collada-dom${COLLADA_DOM_LIBRARY_SUFFIX}
  CLEAN_DIRECT_OUTPUT 1
  SOVERSION 0 # always have it 0 since we're including the soversion as part of the library name
  VERSION ${COLLADA_DOM_VERSION})
if( MSVC )
  install(TARGETS collada-dom EXPORT collada-dom-targets RUNTIME DESTINATION bin COMPONENT ${COMPONENT_PREFIX}-base LIBRARY DESTINATION bin COMPONENT ${COMPONENT_PREFIX}-base ARCHIVE DESTINATION lib${LIB_SUFFIX} COMPONENT ${COMPONENT_PREFIX}-base)
else()
  install(TARGETS collada-dom EXPORT collada-dom-targets DESTINATION lib${LIB_SUFFIX} COMPONENT ${COMPONENT_PREFIX}-base )
endif()

install(DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/include/dae DESTINATION ${COLLADA_DOM_INCLUDE_INSTALL_DIR} COMPONENT ${COMPONENT_PREFIX}-dev PATTERN ".svn" EXCLUDE PATTERN ".~" EXCLUDE)
install(DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/include/modules DESTINATION ${COLLADA_DOM_INCLUDE_INSTALL_DIR} COMPONENT ${COMPONENT_PREFIX}-dev  PATTERN ".svn" EXCLUDE PATTERN ".~" EXCLUDE)
install(FILES ${CMAKE_CURRENT_SOURCE_DIR}/include/dae.h ${CMAKE_CURRENT_SOURCE_DIR}/include/dom.h DESTINATION ${COLLADA_DOM_INCLUDE_INSTALL_DIR} COMPONENT ${COMPONENT_PREFIX}-dev )

if( OPT_COMPILE_TESTS )
  add_subdirectory(test)
endif()
