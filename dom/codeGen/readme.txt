Code generator usage
--------------------

php gen.php schema [cprt]

schema: File name of the COLLADA schema document
cprt:   Generate the files with an SCEA shared source copyright notice

You'll need to download PHP for your platform. php 5.2.5 on Windows
is known to work while version 5.2.6 is known to cause problems. If you 
get a ton of errors when you run the code generator on
Windows, try deleting C:\Program Files\PHP\php.ini if it's present.

The code generator is branched between Collada 1.4 and 1.5. Use the code
generator branch that matches the schema version you're using.

The code generator for Collada 1.5 requires some preprocessing of the
schema. This preprocessing is implemented as some perl search/replace one-liners
in a bash script called 'cleanSchema'. You'll need to have perl
installed. Although only a bash script is provided, it should be trivial to
adapt to a Windows batch file if that's what you need. Run cleanSchema like
this: 'cleanSchema collada15Schema.xsd'. It outputs a file named
collada15Schema_cleaned.xsd, which should then be run through the code generator
to create the 1.5 DOM sources.
