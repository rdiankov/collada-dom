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
	_stringBufferIndex = 0;
	_listIndex = -1;
	getNewBuffer();
}

daeString daeStringTable::getNewBuffer()
{
	_stringBufferIndex = 0;
	_listIndex++;
	if (_listIndex + 1 >= _stringBuffersList.size()) {
		_stringBuffersList.emplace_back(_stringBufferSize);
	}

	return _stringBuffersList[_listIndex].data();
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
		buf = getNewBuffer();
	}
	else
	{
		buf = _stringBuffersList[_listIndex].data();
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
	// Reuse the allocated memory
	_stringBufferIndex = 0;
	_listIndex = 0;
}
