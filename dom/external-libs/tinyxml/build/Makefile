# Linux, Mac, MinGW, Cygwin: 'make'
# PS3: 'make platform=ps3'

platform := linux
ifneq ($(shell uname | grep -i darwin),)
platform := mac
else ifneq ($(or $(shell uname | grep -i cygwin),$(shell uname | grep -i mingw)),)
platform := mingw
endif

ifneq ($(platform), ps3)
compiler := g++
linker := ar
else
compiler := ppu-lv2-g++
linker := ppu-lv2-ar
endif
outPath := $(platform)/

compilerFlags += -Wall -c -O2
ifeq ($(platform),mac)
compilerFlags += -arch i386 -arch ppc
endif

linkerFlags += rcs
libName := tinyxml
sources := tinyxml.cpp tinyxmlparser.cpp tinyxmlerror.cpp tinystr.cpp
sourcePath := ./
tmpPath := $(outPath)tmp/

sources := $(addprefix $(sourcePath), $(sources))
objPath := $(tmpPath)$(libName)/
objs := $(addsuffix .o, $(addprefix $(objPath), $(basename $(notdir $(sources)))))
lib := $(outPath)lib$(libName).a

vpath %.cpp $(sourcePath)

all: makeDirs $(objs) $(lib)

makeDirs:
	@mkdir -p $(outPath)
	@mkdir -p $(objPath)

$(objPath)%.o : %.cpp
	$(compiler) $(compilerFlags) -o $@ $<

# ar rcs lib.a obj1 obj2 ...
$(lib) : $(objs)
	$(linker) $(linkerFlags) $(lib) $(objs)

clean:
	@rm -rf $(objPath)
	@rm -f $(lib)