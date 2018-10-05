/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 

#include <dae/daeStringTable.h>

daeStringTable::daeStringTable(int stringBufferSize):_stringBufferSize(stringBufferSize), _empty( "" )
{
	_stringBufferIndex = _stringBufferSize;
	//allocate initial buffer
	//allocateBuffer();
}

daeString daeStringTable::allocateBuffer()
{
	_stringBuffersList.emplace_back(_stringBufferSize);
	_stringBufferIndex = 0;
	return _stringBuffersList.back().data();
}

daeString daeStringTable::allocString(daeString string)
{
	if ( string == NULL ) return _empty;
	size_t stringSize = strlen(string) + 1;
	size_t sizeLeft = _stringBufferSize - _stringBufferIndex;
	daeString buf;
	if (sizeLeft < stringSize)
	{
		if (stringSize > _stringBufferSize)
			_stringBufferSize = ((stringSize / _stringBufferSize) + 1) * _stringBufferSize ;
		buf = allocateBuffer();
	}
	else
	{
		buf = _stringBuffersList.back().data();
	}
	daeChar *str = (char*)buf + _stringBufferIndex;
	memcpy(str,string,stringSize);
	_stringBufferIndex += stringSize;

	int align = sizeof(void*);
	_stringBufferIndex = (_stringBufferIndex+(align-1)) & (~(align-1));

	return str;
}

void daeStringTable::clear()
{
	_stringBuffersList.clear();
	_stringBufferIndex = _stringBufferSize;
}
