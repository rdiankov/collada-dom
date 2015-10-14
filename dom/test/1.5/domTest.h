/*
* Copyright 2006 Sony Computer Entertainment Inc.
*
* Licensed under the MIT Open Source License, for details please see license.txt or the website
* http://www.opensource.org/licenses/mit-license.php
*
*/ 
#ifndef domTest_h
#define domTest_h

#include <map>
#include <string>

struct domTest;
std::map<std::string, domTest*>& registeredTests();
boost::filesystem::path& dataPath();
boost::filesystem::path& tmpPath();

struct testResult {
	bool passed;
	std::string file; // If the test failed, the file it failed in.
	int line; // If the test failed, the line number it failed on, or -1 if the line
	          // number isn't available.
	std::string msg; // An error message for the user. Usually empty.

	testResult() : passed(true), line(-1) { }
	testResult(bool passed, const std::string& file = "", int line = -1, const std::string& msg = "")
	  : passed(passed),
			file(file),
	    line(line),
	    msg(msg) {
	}
};

struct domTest {
	std::string name;
	domTest(const std::string& name) : name(name) {
		registeredTests()[name] = this;
	}
	virtual ~domTest() { };
	virtual testResult run() = 0;
};

#define DefineTest(testName) \
	struct domTest_##testName : public domTest { \
		domTest_##testName() : domTest(#testName) { } \
		testResult run();	\
	}; \
	domTest_##testName domTest_##testName##Obj; \
	testResult domTest_##testName::run()

#define CheckResult(val) \
	if (!(val)) { \
		return testResult(false, __FILE__, __LINE__); \
	}

#define CheckTestResult(result) \
	{ \
		testResult res = result; \
		if (!res.passed) \
			return res; \
	}

#define SafeAdd(elt, name, var) \
	daeElement* var = elt->add(name); CheckResult(var);

std::string lookupTestFile(const std::string& fileName);
std::string getTmpFile(const std::string& fileName);

#endif
