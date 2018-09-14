/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

#include <dae/daeStringTable.h>

daeStringTable::daeStringTable(int stringBufferSize)
{
	_empty = "";
}

daeString daeStringTable::allocString(daeString string)
{
	if ( string == NULL ) return _empty;
	size_t stringSize = strlen(string) + 1;
	char* s = (char*) aligned_alloc(sizeof(void*), stringSize);
	memcpy(s, string, stringSize);
	vstr.push_back(s);
	return s;
}

void daeStringTable::clear()
{
	for (size_t i = 0; i < vstr.size(); ++i) {
		free((void*) vstr[i]);
	}
	vstr.clear();
}
