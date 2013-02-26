include_directories(${COLLADA_INTERNAL_INCLUDE}/1.5)
add_executable(domTest150 domTest.cpp export.cpp integrationExample.cpp)
set_target_properties(domTest150 PROPERTIES COMPILE_FLAGS "${COLLADA_DOM_EXTERNAL_FLAGS} -DCOLLADA_DOM_USING_150")
target_link_libraries(domTest150 collada-dom)

# have to create a symlink to the data or otherwise tests will not be able to find files
execute_process(COMMAND ${CMAKE_COMMAND} -E create_symlink ${CMAKE_CURRENT_SOURCE_DIR}/data ${CMAKE_CURRENT_BINARY_DIR}/data)
