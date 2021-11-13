/*
 * Copyright 2006 Sony Computer Entertainment Inc.
 *
 * Licensed under the MIT Open Source License, for details please see license.txt or the website
 * http://www.opensource.org/licenses/mit-license.php
 *
 */
#include <cstdarg>
#include <iostream>
#include <iomanip>
#include <string>
#include <sstream>
#include <memory>
#include <map>
#include <vector>
#include <set>
#include <dae.h>
#include <dom/domConstants.h>
#include <dom/domCOLLADA.h>
#include <dom/domProfile_common.h>
#include <dae/daeSIDResolver.h>
#include <dom/domInstance_controller.h>
#include <dae/domAny.h>
#include <dae/daeErrorHandler.h>
#include <dae/daeUtils.h>
#include <dom/domImage.h>
#include <modules/stdErrPlugin.h>
#include <dom/domEllipsoid.h>
#include <dom/domInput_global.h>
#include <dom/domAsset.h>
#include <dom/domLimits_sub.h>
#include <dom/domAny.h>
#include "domTest.h"

// Windows memory leak checking
#if defined _MSC_VER && defined _DEBUG
#define _CRTDBG_MAP_ALLOC
#include <stdlib.h>
#include <crtdbg.h>
#endif

namespace fs = boost::filesystem;
using namespace std;
using namespace cdom;

float toFloat(const string& s) {
    istringstream stream(s);
    float f;
    stream >> f;
    return f;
}

#define CheckResultWithMsg(val, msg) \
    if (!(val)) { \
        return testResult(false, __FILE__, __LINE__, msg); \
    }

#define CompareDocs(dae, file1, file2) \
    { \
        domCOLLADA *root1 = (domCOLLADA*)(dae).getRoot(file1),  \
        *root2 = (domCOLLADA*)(dae).getRoot(file2); \
        daeElement::compareResult result = daeElement::compareWithFullResult(*root1, *root2); \
        if (result.compareValue != 0) { \
            return testResult(false, __FILE__, __LINE__, result.format()); \
        } \
    }

map<string, domTest*>& registeredTests() {
    static map<string, domTest*> tests;
    return tests;
}

fs::path& dataPath() {
    static fs::path dataPath_;
    return dataPath_;
}

fs::path& tmpPath() {
    static fs::path tmpPath_;
    return tmpPath_;
}

#define RunTest(testName) \
    { \
        map<string, domTest*>::iterator iter = registeredTests().find(# testName); \
        CheckResult(iter != registeredTests().end()); \
        CheckResult(iter->second->run()); \
    }


string lookupTestFile(const string& fileName) {
    return (dataPath() / fileName).string();
}

string getTmpFile(const string& fileName) {
    return (tmpPath() / fileName).string();
}


string chopWS(const string& s) {
    string ws = " \t\n\r";
    size_t beginPos = s.find_first_not_of(ws);
    size_t endPos = s.find_last_not_of(ws);
    if (beginPos == string::npos)
        return "";
    return s.substr(beginPos, endPos-beginPos+1);
}

DefineTest(chopWS) {
    CheckResult(chopWS("") == "");
    CheckResult(chopWS("") == "");
    CheckResult(chopWS(" ") == "");
    CheckResult(chopWS(" test") == "test");
    CheckResult(chopWS("test ") == "test");
    CheckResult(chopWS(" test ") == "test");
    CheckResult(chopWS(" a ") == "a");
    return testResult(true);
}


DefineTest(utils) {
    CheckResult(replace("abc", "abc", "def") == "def");
    CheckResult(replace("abc", "a", "1") == "1bc");
    CheckResult(replace("abc", "c", "1") == "ab1");
    CheckResult(replace("abc123", "bc12", "b") == "ab3");
    CheckResult(replace("abracadabra", "a", "") == "brcdbr");

    CheckResult(tokenize("1|2|3|4", "|")   == makeStringList("1", "2", "3", "4", NULL));
    CheckResult(tokenize("|1|", "|")       == makeStringList("1", NULL));
    CheckResult(tokenize("1|||2||3|", "|") == makeStringList("1", "2", "3", NULL));
    CheckResult(tokenize("1|||2||3|", "|", true) ==
                makeStringList("1", "|", "|", "|", "2", "|", "|", "3", "|", NULL));
    CheckResult(tokenize("this/is some#text", "/#", true) ==
                makeStringList("this", "/", "is some", "#", "text", NULL));
    CheckResult(tokenize("this/is some#text", "/# ", false) ==
                makeStringList("this", "is", "some", "text", NULL));

    CheckResult(toString(5) == "5");
    CheckResult(toFloat(toString(4.0f)) == 4.0f);

    return testResult(true);
}


DefineTest(elementAddFunctions) {
    DAE dae;
    const char* uri = "file.dae";

    // Test the new 'add' functions
    daeElement* root = dae.add(uri);
    CheckResult(root);
    daeElement* geomLib = root->add("library_geometries");
    CheckResult(geomLib);
    daeElement* effectLib = root->add("library_effects", 0);
    CheckResult(effectLib && root->getChildren()[0] == effectLib);
    root->addBefore(geomLib, effectLib);
    CheckResult(root->getChildren()[0] == geomLib);
    CheckResult(root->removeChildElement(geomLib));
    root->addAfter(geomLib, effectLib);
    CheckResult(root->getChildren()[1] == geomLib);
    CheckResult(root->removeChildElement(geomLib));
    root->add(geomLib);
    CheckResult(root->getDescendant("library_geometries"));
    daeElement* instanceGeom = root->add("library_nodes node instance_geometry");
    CheckResult(instanceGeom && instanceGeom->typeID() == domInstance_geometry::ID());
    CheckResult(root->add("library_materials material blah") == NULL);
    CheckResult(root->getDescendant("library_materials") == NULL);

    // Test the deprecated functions
    dae.close(uri);
    root = dae.add(uri);
    CheckResult(root);
    geomLib = root->createAndPlace("library_geometries");
    CheckResult(geomLib);
    effectLib = root->createAndPlaceAt(0, "library_effects");
    CheckResult(effectLib && root->getChildren()[0] == effectLib);
    root->placeElementBefore(effectLib, geomLib);
    CheckResult(root->getChildren()[0] == geomLib);
    CheckResult(root->removeChildElement(geomLib));
    root->placeElementAfter(effectLib, geomLib);
    CheckResult(root->getChildren()[1] == geomLib);
    CheckResult(root->removeChildElement(geomLib));
    root->placeElement(geomLib);
    CheckResult(root->getDescendant("library_geometries"));

    return testResult(true);
}


DefineTest(loadClipPlane) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("clipPlane.dae")));
    return testResult(true);
}


DefineTest(renderStates) {
    string memoryUri = "renderStates_create.dae";
    string file = getTmpFile("renderStates.dae");

    DAE dae;
    daeElement* root = dae.add(memoryUri);
    CheckResult(root);
    daeElement* pass = root->add("library_effects effect profile_CG technique pass");
    CheckResult(pass);

    domCg_pass::domEvaluate* evaluate = daeSafeCast<domCg_pass::domEvaluate>(pass->add("evaluate"));
    evaluate->add("color_clear")->setCharData("0 0 0 0");

    domCg_pass::domStates* states = daeSafeCast<domCg_pass::domStates>(pass->add("states"));
    states->add("depth_mask")->setAttribute("value", "true");
    states->add("cull_face_enable")->setAttribute("value", "true");
    states->add("blend_enable")->setAttribute("value", "true");
    states->add("blend_func_separate")->setAttribute("value", "true");
    states->add("cull_face")->setAttribute("value", "FRONT");
    states->add("polygon_offset_fill_enable")->setAttribute("value", "true");

    // Write the document to disk
    CheckResult(dae.writeTo(memoryUri, file));

    // Load up the saved document and see if it's the same as our in-memory document
    root = dae.open(file);
    CheckResult(root);
    CompareDocs(dae, memoryUri, file);

    // Check default attribute value suppression
    CheckResult(root->getDescendant("depth_mask")->isAttributeSet("value") == false);
    CheckResult(root->getDescendant("color_clear")->getCharData() != "");
    CheckResult(root->getDescendant("polygon_offset_fill_enable")->isAttributeSet("value"));

    return testResult(true);
}


DefineTest(compareElements) {
    string memoryUri = "file.dae";

    DAE dae;
    daeElement* root = dae.add(memoryUri);
    CheckResult(root);

    daeElement* technique = root->add("extra technique");
    CheckResult(technique);

    // Make sure attribute order doesn't matter
    daeElement* elt1 = technique->add("elt");
    daeElement* elt2 = technique->add("elt");
    CheckResult(elt1 && elt2);

    elt1->setAttribute("attr1", "val1");
    elt1->setAttribute("attr2", "val2");
    elt2->setAttribute("attr2", "val2");
    elt2->setAttribute("attr1", "val1");

    CheckResult(daeElement::compare(*elt1, *elt2) == 0);

    // Make sure that element comparison fails when appropriate
    elt2->setAttribute("attr3", "val3");
    CheckResult(daeElement::compare(*elt1, *elt2) < 0);

    return testResult(true);
}


DefineTest(writeCamera) {
    string memoryUri = "camera_create.dae";
    string file = getTmpFile("camera.dae");

    DAE dae;
    daeElement* elt = dae.add(memoryUri);
    CheckResult(elt);
    elt = elt->add("library_cameras camera optics technique_common perspective xfov");
    CheckResult(elt);
    elt->setCharData("1.0");

    CheckResult(dae.writeTo(memoryUri, file));
    domCOLLADA* root = (domCOLLADA*)dae.open(file);
    CheckResult(root);
    CompareDocs(dae, memoryUri, file);
    CheckResult(toFloat(root->getDescendant("xfov")->getCharData()) == 1.0f);

    return testResult(true);
}


string getRoundTripFile(const string& name) {
    return getTmpFile(fs::basename(fs::path(name)) + "_roundTrip.dae");
}

bool roundTrip(const string& file) {
    DAE dae;
    if (!dae.open(file))
        return false;
    return dae.writeTo(file, getRoundTripFile(file));
}

DefineTest(roundTripSeymour) {
    string file1 = lookupTestFile("Seymour.dae"),
           file2 = getRoundTripFile(file1);
    DAE dae;
    CheckResult(dae.open(file1));
    CheckResult(dae.writeTo(file1, file2));
    CheckResult(dae.open(file2));
    CompareDocs(dae, file1, file2);
    return testResult(true);
}


DefineTest(rawSupport) {
    string seymourOrig = lookupTestFile("Seymour.dae"),
           seymourRaw  = getTmpFile("Seymour_raw.dae");
    DAE dae;

    CheckResult(dae.open(seymourOrig));
    dae.getIOPlugin()->setOption("saveRawBinary", "true");
    CheckResult(dae.writeTo(seymourOrig, seymourRaw));
    dae.clear();

    // Make sure the .raw file is there
    CheckResult(fs::exists(fs::path(seymourRaw + ".raw")));

    daeElement* seymourRawRoot = dae.open(seymourRaw);
    CheckResult(seymourRawRoot);
    CheckResult(dae.getDatabase()->idLookup("l_hip_rotateY_l_hip_rotateY_ANGLE-input",
                                            seymourRawRoot->getDocument()));
    domAccessor* accessor = dae.getDatabase()->typeLookup<domAccessor>().at(0);
    daeURI& uri = accessor->getSource();
    CheckResult(uri.pathExt().find(".raw") != string::npos);
    CheckResult(uri.getElement());

    return testResult(true);
}

DefineTest(extraTypeTest) {
    DAE dae;
    string file = lookupTestFile("extraTest.dae");
    daeElement* root = dae.open(file);
    CheckResult(root);

    daeElement *technique = root->getDescendant("technique"),
    *expectedTypesElt = root->getDescendant("expected_types");
    CheckResult(technique && expectedTypesElt);

    // read expected type names from <expected_types> element
    istringstream expectedTypesStream(expectedTypesElt->getCharData());
    vector<string> expectedTypes;
    string tmp;
    while (expectedTypesStream >> tmp)
        expectedTypes.push_back(tmp);

    daeElementRefArray elements = technique->getChildren();

    // compare expected types with direct children of technique
    CheckResult(expectedTypes.size() == elements.getCount()-1);
    for (size_t i = 0; i < elements.getCount()-1; i++) {
        ostringstream msg;
        msg << "Actual type - " << elements[i]->getTypeName() << ", Expected type - " << expectedTypes[i];
        CheckResultWithMsg(expectedTypes[i] == elements[i]->getTypeName(), msg.str());
    }

    return testResult(true);
}

#if defined(TINYXML)
#include <dae/daeTinyXMLPlugin.h>
DefineTest(tinyXmlLoad) {
    string seymourOrig = lookupTestFile("Seymour.dae"),
           seymourTinyXml = getTmpFile("Seymour_tinyXml.dae");

    // Plan: Load Seymour with libxml, then save with TinyXml and immediately reload the
    // saved document, and make sure the results are the same.
    DAE dae;
    CheckResult(dae.open(seymourOrig));
    unique_ptr<daeTinyXMLPlugin> tinyXmlPlugin(new daeTinyXMLPlugin);
    dae.setIOPlugin(tinyXmlPlugin.get());
    CheckResult(dae.writeTo(seymourOrig, seymourTinyXml));
    CheckResult(dae.open(seymourTinyXml));
    CompareDocs(dae, seymourOrig, seymourTinyXml);

    return testResult(true);
}
#endif


string resolveResultToString(const string& sidRef, daeElement* refElt) {
    daeSidRef::resolveData rd = daeSidRef(sidRef, refElt).resolve();
    if (rd.scalar) return "scalar";
    else if (rd.array) return "array";
    else if (rd.elt) return "element";
    else return "failed";
}

DefineTest(sidResolve) {
    DAE dae;
    daeElement* root = dae.open(lookupTestFile("sidResolveTest.dae"));
    CheckResult(root);
    daeDatabase& database = *dae.getDatabase();
    daeDocument* doc = root->getDocument();

    daeElement *effect = database.idLookup("myEffect", doc),
    *effectExtra = database.idLookup("effectExtra", doc);
    CheckResult(effect && effectExtra);

    istringstream stream(effectExtra->getCharData());
    string sidRef, expectedResult;
    while (stream >> sidRef >> expectedResult) {
        string result = resolveResultToString(sidRef, effect);
        CheckResultWithMsg(result == expectedResult,
                           string("sid ref=") + sidRef + ", expectedResult=" + expectedResult + ", actualResult=" + result);
    }

    daeElement* nodeSidRefExtra = database.idLookup("nodeSidRefExtra", doc);
    CheckResult(nodeSidRefExtra);

    stream.clear();
    stream.str(nodeSidRefExtra->getCharData());
    while (stream >> sidRef >> expectedResult) {
        string result = resolveResultToString(sidRef, root);
        CheckResultWithMsg(result == expectedResult,
                           string("sid ref=") + sidRef + ", expectedResult=" + expectedResult + ", actualResult=" + result);
    }

    nodeSidRefExtra = database.idLookup("nodeSidRefExtra2", doc);
    CheckResult(nodeSidRefExtra);

    stream.clear();
    stream.str(nodeSidRefExtra->getCharData());
    while (stream >> sidRef >> expectedResult) {
        daeElement* elt = daeSidRef(sidRef, root).resolve().elt;
        string result = elt ? elt->getAttribute("id") : "failed";
        CheckResultWithMsg(result == expectedResult,
                           string("sid ref=") + sidRef + ", expectedResult=" + expectedResult + ", actualResult=" + result);
    }

    nodeSidRefExtra = database.idLookup("nodeSidRefExtra3", doc);
    CheckResult(nodeSidRefExtra);

    stream.clear();
    stream.str(nodeSidRefExtra->getCharData());
    string profile;
    while (stream >> sidRef >> profile >> expectedResult) {
        daeElement* elt = daeSidRef(sidRef, root, profile).resolve().elt;
        string result = elt ? elt->getAttribute("id") : "failed";
        CheckResultWithMsg(result == expectedResult,
                           string("sid ref=") + sidRef + ", profile=" + profile +
                           ", expectedResult=" + expectedResult + ", actualResult=" + result);
    }


    return testResult(true);
}

daeElement* findChildByName(daeElement* el, daeString name) {
    if (!el)
        return 0;

    daeElementRefArray children = el->getChildren();
    for (size_t i = 0; i < children.getCount(); i++)
        if (strcmp(children[i]->getElementName(), name) == 0)
            return children[i];

    return 0;
}

daeElement* findAncestorByType(daeElement* el, daeString type) {
    if (el == 0  ||  strcmp(el->getTypeName(), type) == 0)
        return el;
    return findAncestorByType(el->getParentElement(), type);
}

daeElement* resolveID(daeString id, daeDocument& document) {
    return document.getDatabase()->idLookup(id, &document);
}

daeElement* resolveSid(const string& sid, daeElement& refElt) {
    return daeSidRef(sid, &refElt).resolve().elt;
}

string getCharData(daeElement* el) {
    return el ? el->getCharData() : "";
}

daeURI* getTextureUri(const string& samplerSid, daeElement& effect) {
    daeElement* sampler = findChildByName(resolveSid(samplerSid, effect), "sampler2D");
    daeElement* instanceImage = findChildByName(sampler, "instance_image");
    xsAnyURI imageUrl = daeSafeCast<domInstance_image>(instanceImage)->getUrl();
    domImage* image = daeSafeCast<domImage>(imageUrl.getElement());
    if (image && image->getInit_from() && image->getInit_from()->getRef())
        return &image->getInit_from()->getRef()->getValue();
    return 0;
}

DefineTest(getTexture) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("Seymour.dae")));

    daeElement* effect = dae.getDatabase()->idLookup("face-fx").at(0);
    daeElement* texture = effect->getDescendant("texture");
    CheckResult(texture);

    daeURI* uri = getTextureUri(texture->getAttribute("texture"), *effect);
    CheckResult(uri);
    CheckResult(uri->pathFile() == "boy_10.tga");

    return testResult(true);
}


DefineTest(removeElement) {
    DAE dae;
    daeElement* collada = dae.open(lookupTestFile("Seymour.dae"));
    CheckResult(collada);

    daeElement *animLib = dae.getDatabase()->typeLookup(domLibrary_animations::ID()).at(0),
    *asset = dae.getDatabase()->typeLookup(domAsset::ID()).at(0);

    collada->removeChildElement(asset);
    daeElement::removeFromParent(animLib);

    CheckResult(collada->getDescendant("asset") == NULL);
    CheckResult(collada->getDescendant("library_animations") == NULL);

    CheckResult(dae.writeTo(lookupTestFile("Seymour.dae"),
                            getTmpFile("Seymour_removeElements.dae")));
    return testResult(true);
}

void nameArraySet(domList_of_names& names, size_t index, const char* name) {
    *(daeStringRef*)&names[index] = name;
}

void nameArrayAppend(domList_of_names& names, const char* name) {
    names.append(NULL);
    nameArraySet(names, names.getCount()-1, name);
}

DefineTest(nameArray) {
    domList_of_names names;
    for (int i = 0; i < 10; i++)
        nameArrayAppend(names, (string("name") + toString(i)).c_str());
    for (int i = 0; i < 10; i++) {
        CheckResult(string("name") + toString(i) == string(names[i]));
    }

    return testResult(true);
}

daeTArray<int> makeIntArray(int i, ...) {
    va_list args;
    va_start(args, i);
    daeTArray<int> result;
    while (i != INT_MAX) {
        result.append(i);
        i = va_arg(args, int);
    }
    va_end(args);
    return result;
}

DefineTest(arrayOps) {
    daeTArray<int> zeroToFour = makeIntArray(0, 1, 2, 3, 4, INT_MAX);

    // Test removeIndex
    daeTArray<int> array = zeroToFour;
    array.removeIndex(2);
    CheckResult(array == makeIntArray(0, 1, 3, 4, INT_MAX));

    // Insert several values into the middle of an array
    array = zeroToFour;
    array.insert(3, 5, 9); // Insert five copies of '9' at the third element of the array
    CheckResult(array == makeIntArray(0, 1, 2, 9, 9, 9, 9, 9, 3, 4, INT_MAX));

    // Insert several values beyond the end of an array
    array = zeroToFour;
    array.insert(7, 2, 5);
    CheckResult(array == makeIntArray(0, 1, 2, 3, 4, 5, 5, 5, 5, INT_MAX));

    return testResult(true);
}


void printMemoryToStringResult(daeAtomicType& type, daeMemoryRef value) {
    ostringstream buffer;
    type.memoryToString(value, buffer);
    cout << buffer.str() << endl;
}

string toString(daeAtomicType& type, daeMemoryRef value) {
    ostringstream buffer;
    type.memoryToString(value, buffer);
    return buffer.str();
}

DefineTest(atomicTypeOps) {
    DAE dae;
    daeUIntType UIntType(dae);
    daeIntType IntType(dae);
    daeLongType LongType(dae);
    daeShortType ShortType(dae);
    daeULongType ULongType(dae);
    daeFloatType FloatType(dae);
    daeDoubleType DoubleType(dae);
    daeStringRefType StringRefType(dae);
    daeElementRefType ElementRefType(dae);
    daeEnumType EnumType(dae);
    daeResolverType ResolverType(dae);
    daeIDResolverType IDResolverType(dae);
    daeBoolType BoolType(dae);
    daeTokenType TokenType(dae);

    EnumType._values = new daeEnumArray;
    EnumType._strings = new daeStringRefArray;
    EnumType._values->append(0);
    EnumType._strings->append("myEnumValue");

    daeUInt UInt(1);
    daeInt Int(2);
    daeLong Long(3);
    daeShort Short(4);
    daeULong ULong(5);
    daeFloat Float(6.123f);
    daeDouble Double(7.456);
    daeStringRef StringRef("StringRef");
    //	daeElementRef ElementRef(0x12345678);
    daeEnum Enum(0);
    daeURI uri(dae, "http://www.example.com/#fragment");
    daeIDRef IDRef("sampleID");
    daeBool Bool(false);
    daeStringRef Token("token");


    CheckResult(toString(UIntType, (daeMemoryRef)&UInt) == "1");
    CheckResult(toString(IntType, (daeMemoryRef)&Int) == "2");
    CheckResult(toString(LongType, (daeMemoryRef)&Long) == "3");
    CheckResult(toString(ShortType, (daeMemoryRef)&Short) == "4");
    CheckResult(toString(ULongType, (daeMemoryRef)&ULong) == "5");
    CheckResult(toString(FloatType, (daeMemoryRef)&Float) == "6.123");
    CheckResult(toString(DoubleType, (daeMemoryRef)&Double) == "7.456");
    CheckResult(toString(StringRefType, (daeMemoryRef)&StringRef) == "StringRef");
    //	CheckResult(toString(ElementRefType, (daeMemoryRef)&ElementRef) == "");
    CheckResult(toString(EnumType, (daeMemoryRef)&Enum) == "myEnumValue");
    CheckResult(toString(ResolverType, (daeMemoryRef)&uri) == "http://www.example.com/#fragment");
    CheckResult(toString(IDResolverType, (daeMemoryRef)&IDRef) == "sampleID");
    CheckResult(toString(BoolType, (daeMemoryRef)&Bool) == "false");
    CheckResult(toString(TokenType, (daeMemoryRef)&Token) == "token");

    return testResult(true);
}


DefineTest(clone) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("Seymour.dae")));

    daeElement* el = dae.getDatabase()->idLookup("l_ulna").at(0);
    daeElementRef clone = el->clone("-foo", "-bar");
    el->getParentElement()->placeElement(clone);

    CheckResult(dae.writeTo(lookupTestFile("Seymour.dae"), getTmpFile("cloneTest.dae")));

    return testResult(true);
}


DefineTest(genericOps) {
    string file = lookupTestFile("cube.dae");
    DAE dae;
    CheckResult(dae.open(file));
    daeDatabase& database = *dae.getDatabase();

    // Attribute getter/setter tests
    daeElement* el = database.idLookup("box-lib-positions-array").at(0);

    CheckResult(el->hasAttribute("digits"));
    CheckResult(el->getAttribute("count") == "24");
    CheckResult(el->setAttribute("blah", "hey") == false);
    CheckResult(el->setAttribute("magnitude", "30"));

    el = database.idLookup("Blue-fx").at(0);
    CheckResult(el->hasAttribute("name"));
    CheckResult(el->isAttributeSet("name") == false);
    CheckResult(el->isAttributeSet("hello") == false);

    // Character data getter/setter tests
    el = database.typeLookup(domAsset::domUp_axis::ID()).at(0);

    CheckResult(el->getCharData() == "Y_UP");
    el->setCharData("X_UP");

    el = database.idLookup("PerspCamera").at(0);
    CheckResult(!el->hasCharData());

    // <extra> tests using daeElement interface
    el = database.idLookup("my_test_element").at(0);
    CheckResult(chopWS(el->getCharData()) == "this is some text");

    daeElementRef clone = el->clone("-clone", "-clone");
    CheckResult(chopWS(el->getCharData()) == "this is some text");

    CheckResult(el->getAttribute("attr1") == "value1" &&
                el->getAttribute("attr2") == "value2");
    CheckResult(el->setAttribute("attr1", "value_1"));
    CheckResult(el->setAttribute("attr3", "value3"));

    CheckResult(chopWS(el->getCharData()) == "this is some text");
    el->setCharData("reset text");

    // <extra> tests using domAny interface
    el->getParentElement()->placeElementAfter(el, clone);
    domAny* any = (domAny*)clone.cast();
    CheckResult(any);
    CheckResult(any->getAttributeCount() == 3);
    CheckResult(string(any->getAttributeName(0)) == "id");
    CheckResult(string(any->getAttributeValue(1)) == "value1");
    CheckResult(chopWS(any->getValue()) == "this is some text");
    any->setValue("reset text 2");

    // Test for lots of attributes
    for (size_t i = 0; i < 50; i++) {
        ostringstream name, value;
        name << "attr" << static_cast<unsigned int>(i);
        value << "value" << static_cast<unsigned int>(i);
        any->setAttribute(name.str().c_str(), value.str().c_str());
    }

    CheckResult(dae.writeTo(file, getTmpFile(fs::basename(fs::path(file)) + "_genericOps.dae")));

    return testResult(true);
}


daeArray* getSkewArray(daeElement* node, const string& sid) {
    if (!node)
        return NULL;

    daeElement* skew = resolveSid(sid, *node);
    if (!skew || strcmp(skew->getTypeName(), COLLADA_TYPE_SKEW) )
        return NULL;

    return (daeArray*)skew->getCharDataObject()->get(skew);
}

DefineTest(badSkew) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("badSkew.dae")));

    daeElement* node = dae.getDatabase()->idLookup("my-node").at(0);

    daeArray* array1 = getSkewArray(node, "tooFew");
    daeArray* array2 = getSkewArray(node, "justRight");
    daeArray* array3 = getSkewArray(node, "tooMany");
    CheckResult(array1 && array2 && array3);

    CheckResult(array1->getCount() == 4);
    CheckResult(array2->getCount() == 7);
    CheckResult(array3->getCount() == 11);

    return testResult(true);
}


DefineTest(stringTable) {
    daeStringTable stringTable;
    stringTable.allocString("hello");
    // These next two lines used to cause an abort
    stringTable.clear();
    stringTable.allocString("goodbye");
    return testResult(true);
}


// We can only do this test if we have breps
#if 0
DefineTest(sidResolveSpeed) {
    DAE dae;
    string file = lookupTestFile("crankarm.dae");
    domCOLLADA* root = (domCOLLADA*)dae.open(file);
    CheckResult(root);

    vector<domSIDREF_array*> sidRefArrays = dae.getDatabase()->typeLookup<domSIDREF_array>();
    for (size_t i = 0; i < sidRefArrays.size(); i++) {
        domListOfNames& sidRefs = sidRefArrays[i]->getValue();
        for (size_t j = 0; j < sidRefs.getCount(); j++) {
            CheckResult(resolveSid(sidRefs[i], root));
        }
    }

    return testResult(true);
}
#endif


DefineTest(seymourSidResolve) {
    DAE dae;
    string file = lookupTestFile("Seymour.dae");
    CheckResult(dae.open(file));

    vector<daeElement*> nodes = dae.getDatabase()->typeLookup(domNode::ID());
    for (size_t i = 0; i < nodes.size(); i++) {
        daeElementRefArray children = nodes[i]->getChildren();
        for (size_t j = 0; j < children.getCount(); j++) {
            string sid = children[j]->getAttribute("sid");
            if (!sid.empty()) {
                CheckResult(daeSidRef(sid, nodes[i]).resolve().elt);
            }
        }
    }

    return testResult(true);
}


vector<string> getChildNames(daeElement* elt) {
    vector<string> result;
    if (!elt)
        return result;

    daeElementRefArray children = elt->getChildren();
    for (size_t i = 0; i < children.getCount(); i++)
        result.push_back(children[i]->getElementName());

    return result;
}

DefineTest(placeElement) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.dae")));

    daeElement* node = dae.getDatabase()->idLookup("Box").at(0);

    CheckResult(getChildNames(node) == makeStringArray(
                    "rotate", "rotate", "rotate", "instance_geometry", NULL));

    // Place a new <translate> after the first <rotate> using placeElementAfter, and
    // make sure the <translate> shows up in the right spot.
    node->placeElementAfter(node->getChildren()[0], node->createElement("translate"));
    CheckResult(getChildNames(node) == makeStringArray(
                    "rotate", "translate", "rotate", "rotate", "instance_geometry", NULL));

    node->placeElementBefore(node->getChildren()[0], node->createElement("scale"));
    CheckResult(getChildNames(node) == makeStringArray(
                    "scale", "rotate", "translate", "rotate", "rotate", "instance_geometry", NULL));

    return testResult(true);
};


DefineTest(nativePathConversion) {
    // Windows file path to URI
    CheckResult(cdom::nativePathToUri("C:\\myFolder\\myFile.dae", cdom::Windows) == "/C:/myFolder/myFile.dae");
    CheckResult(cdom::nativePathToUri("\\myFolder\\myFile.dae", cdom::Windows) == "/myFolder/myFile.dae");
    CheckResult(cdom::nativePathToUri("..\\myFolder\\myFile.dae", cdom::Windows) == "../myFolder/myFile.dae");
    CheckResult(cdom::nativePathToUri("\\\\otherComputer\\myFile.dae", cdom::Windows) == "//otherComputer/myFile.dae");

    // Linux/Mac file path to URI
    CheckResult(cdom::nativePathToUri("/myFolder/myFile.dae", cdom::Posix) == "/myFolder/myFile.dae");
    CheckResult(cdom::nativePathToUri("../myFolder/myFile.dae", cdom::Posix) == "../myFolder/myFile.dae");
    CheckResult(cdom::nativePathToUri("/my folder/my file.dae", cdom::Posix) == "/my%20folder/my%20file.dae");

    // URI to Windows file path
    CheckResult(cdom::uriToNativePath("../folder/file.dae", cdom::Windows) == "..\\folder\\file.dae");
    CheckResult(cdom::uriToNativePath("/C:/folder/file.dae", cdom::Windows) == "C:\\folder\\file.dae");
    CheckResult(cdom::uriToNativePath("file:///C:/folder/file.dae", cdom::Windows) == "C:\\folder\\file.dae");
    CheckResult(cdom::uriToNativePath("//otherComputer/file.dae", cdom::Windows) == "\\\\otherComputer\\file.dae");
    CheckResult(cdom::uriToNativePath("file://///otherComputer/file.dae", cdom::Windows) == "\\\\otherComputer\\file.dae");
    CheckResult(cdom::uriToNativePath("http://www.slashdot.org", cdom::Windows) == "");

    // URI to Linux/Mac file path
    CheckResult(cdom::uriToNativePath("../folder/file.dae", cdom::Posix) == "../folder/file.dae");
    CheckResult(cdom::uriToNativePath("/folder/file.dae", cdom::Posix) == "/folder/file.dae");
    CheckResult(cdom::uriToNativePath("file:///folder/file.dae", cdom::Posix) == "/folder/file.dae");
    CheckResult(cdom::uriToNativePath("http://www.slashdot.org", cdom::Posix) == "");

    return testResult(true);
}


DefineTest(libxmlUriBugWorkaround) {
    if (cdom::getSystemType() == cdom::Posix) {
        // libxml doesn't like file scheme uris that don't have an authority component
        CheckResult(cdom::fixUriForLibxml("file:/folder/file.dae") == "file:///folder/file.dae");
    }
    else if (cdom::getSystemType() == cdom::Windows) {
        // libxml doesn't like file scheme uris that don't have an authority component
        CheckResult(cdom::fixUriForLibxml("file:/c:/folder/file.dae") == "file:///c:/folder/file.dae");
        // libxml wants UNC paths that contain an empty authority followed by three slashes
        CheckResult(cdom::fixUriForLibxml("file://otherComputer/file.dae") == "file://///otherComputer/file.dae");
        // libxml wants absolute paths that don't contain a drive letter to have an
        // empty authority followed by two slashes.
        CheckResult(cdom::fixUriForLibxml("file:/folder/file.dae") == "file:////folder/file.dae");
    }

    return testResult(true);
}


// !!!steveT I want this to be a test of the DOM's ability to open files
// using all the various ways of referencing files: relative paths, absolute
// paths, absolute paths with no drive letter (Windows), UNC paths (Windows),
// http scheme URIs, zipped files, etc.
#if 0
DefineTest(uriOpen) {
    DAE dae;
    CheckResult(dae.open("file:/c:/models/cube.dae"));
    CheckResult(dae.open("/c:/models/cube.dae"));
    CheckResult(dae.open("/models/cube.dae"));
    CheckResult(dae.open("file:////models/cube.dae"));
    CheckResult(dae.open("file://isis/sceard/COLLADA/forsteve/cube.dae"));
    CheckResult(dae.open("file://///isis/sceard/COLLADA/forsteve/cube.dae"));
    return testResult(true);
}
#endif


DefineTest(uriOps) {
    DAE dae;

    // Check construction of absolute uris
    CheckResult(daeURI(dae, "file:///home/sthomas/file.txt").str() == "file:/home/sthomas/file.txt");
    CheckResult(daeURI(dae, "http://www.example.com/path").str() == "http://www.example.com/path");
    CheckResult(daeURI(dae, "file:home/sthomas/file.txt").str() == "file:home/sthomas/file.txt");
    CheckResult(daeURI(dae, "file:file.txt#fragment", true).str() == "file:file.txt");

    // Check construction of relative uri references
    {
        daeURI base(dae, "file:/home/sthomas/file.txt?baseQuery#baseFragment");
        CheckResult(base.str() == "file:/home/sthomas/file.txt?baseQuery#baseFragment");
        CheckResult(daeURI(base, "file:/home/sthomas").str() == "file:/home/sthomas");
        CheckResult(daeURI(base, "//authority").str() == "file://authority");
        CheckResult(daeURI(base, "//authority/path").str() == "file://authority/path");
        CheckResult(daeURI(base, "/home/johnny").str() == "file:/home/johnny");
        CheckResult(daeURI(base, "myFile.txt").str() == "file:/home/sthomas/myFile.txt");
        CheckResult(daeURI(base, "?query#fragment").str() == "file:/home/sthomas/file.txt?query#fragment");
        CheckResult(daeURI(base, "?query").str() == "file:/home/sthomas/file.txt?query");
        CheckResult(daeURI(base, "").str() == "file:/home/sthomas/file.txt?baseQuery");
        CheckResult(daeURI(daeURI(dae, "http://www.example.com/path"), "myFolder/file.txt").str() == "http://www.example.com/myFolder/file.txt");
        CheckResult(daeURI(daeURI(dae, "http://www.example.com/path/"), "myFolder/file.txt").str() == "http://www.example.com/path/myFolder/file.txt");
        CheckResult(daeURI(daeURI(dae, "http://www.example.com"), "myFolder/file.txt").str() == "http://www.example.com/myFolder/file.txt");
    }

    // More reference resolution tests. These are taken from http://tools.ietf.org/html/rfc3986#section-5.4
    {
        daeURI base(dae, "http://a/b/c/d;p?q");

        CheckResult(daeURI(base, "g:h").str() == "g:h");
        CheckResult(daeURI(base, "g").str() == "http://a/b/c/g");
        CheckResult(daeURI(base, "./g").str() == "http://a/b/c/g");
        CheckResult(daeURI(base, "g/").str() == "http://a/b/c/g/");
        CheckResult(daeURI(base, "/g").str() == "http://a/g");
        CheckResult(daeURI(base, "//g").str() == "http://g");
        CheckResult(daeURI(base, "?y").str() == "http://a/b/c/d;p?y");
        CheckResult(daeURI(base, "g?y").str() == "http://a/b/c/g?y");
        CheckResult(daeURI(base, "#s").str() == "http://a/b/c/d;p?q#s");
        CheckResult(daeURI(base, "g#s").str() == "http://a/b/c/g#s");
        CheckResult(daeURI(base, "g?y#s").str() == "http://a/b/c/g?y#s");
        CheckResult(daeURI(base, ";x").str() == "http://a/b/c/;x");
        CheckResult(daeURI(base, "g;x").str() == "http://a/b/c/g;x");
        CheckResult(daeURI(base, "g;x?y#s").str() == "http://a/b/c/g;x?y#s");
        CheckResult(daeURI(base, "").str() == "http://a/b/c/d;p?q");
        CheckResult(daeURI(base, ".").str() == "http://a/b/c/");
        CheckResult(daeURI(base, "./").str() == "http://a/b/c/");
        CheckResult(daeURI(base, "..").str() == "http://a/b/");
        CheckResult(daeURI(base, "../").str() == "http://a/b/");
        CheckResult(daeURI(base, "../g").str() == "http://a/b/g");
        CheckResult(daeURI(base, "../..").str() == "http://a/");
        CheckResult(daeURI(base, "../../").str() == "http://a/");
        CheckResult(daeURI(base, "../../g").str() == "http://a/g");

        CheckResult(daeURI(base, "../../../g").str() == "http://a/g");
        CheckResult(daeURI(base, "../../../../g").str() == "http://a/g");
        CheckResult(daeURI(base, "/./g").str() == "http://a/g");
        CheckResult(daeURI(base, "/../g").str() == "http://a/g");
        CheckResult(daeURI(base, "g.").str() == "http://a/b/c/g.");
        CheckResult(daeURI(base, ".g").str() == "http://a/b/c/.g");
        CheckResult(daeURI(base, "g..").str() == "http://a/b/c/g..");
        CheckResult(daeURI(base, "..g").str() == "http://a/b/c/..g");

        CheckResult(daeURI(base, "./../g").str() == "http://a/b/g");
        CheckResult(daeURI(base, "./g/.").str() == "http://a/b/c/g/");
        CheckResult(daeURI(base, "g/./h").str() == "http://a/b/c/g/h");
        CheckResult(daeURI(base, "g/../h").str() == "http://a/b/c/h");
        CheckResult(daeURI(base, "g;x=1/./y").str() == "http://a/b/c/g;x=1/y");
        CheckResult(daeURI(base, "g;x=1/../y").str() == "http://a/b/c/y");


        CheckResult(daeURI(base, "g?y/./x").str() == "http://a/b/c/g?y/./x");
        CheckResult(daeURI(base, "g?y/../x").str() == "http://a/b/c/g?y/../x");
        CheckResult(daeURI(base, "g#s/./x").str() == "http://a/b/c/g#s/./x");
        CheckResult(daeURI(base, "g#s/../x").str() == "http://a/b/c/g#s/../x");

        CheckResult(daeURI(base, "http:g").str() == "http:g");
    }

    // Check originalStr
    CheckResult(daeURI(dae, "relPath/file.txt").originalStr() == "relPath/file.txt");

    // Check main setters
    {
        daeURI uri(dae);
        uri.set("file:/path/file.txt");
        CheckResult(uri.str() == "file:/path/file.txt");
        uri.set("http", "www.example.com", "/path", "q", "f");
        CheckResult(uri.str() == "http://www.example.com/path?q#f");
    }

    // Check component accessors
    CheckResult(daeURI(dae, "file:/home/sthomas/file.txt").scheme() == "file");
    CheckResult(daeURI(dae, "http://www.example.com").authority() == "www.example.com");
    CheckResult(daeURI(dae, "file:/home/sthomas/file.txt").path() == "/home/sthomas/file.txt");
    CheckResult(daeURI(dae, "file:/home/sthomas/file.txt?query").query() == "query");
    CheckResult(daeURI(dae, "file:/home/sthomas/file.txt?query#fragment").fragment() == "fragment");
    CheckResult(daeURI(dae, "file:/home/sthomas/file.txt?query#fragment").id() == "fragment");

    // Check component setters
    {
        daeURI uri(dae);
        uri.scheme("file");
        uri.authority("myAuth");
        uri.path("/home/sthomas/file.txt");
        uri.query("q");
        uri.fragment("f");
        CheckResult(uri.str() == "file://myAuth/home/sthomas/file.txt?q#f");
        uri.id("id");
        CheckResult(uri.str() == "file://myAuth/home/sthomas/file.txt?q#id");
    }

    // Check path component accessors
    {
        daeURI uri(dae, "file:/home/sthomas/file.txt");
        CheckResult(uri.str() == "file:/home/sthomas/file.txt");
        string dir, base, ext;
        uri.pathComponents(dir, base, ext);
        CheckResult(dir == "/home/sthomas/");
        CheckResult(base == "file");
        CheckResult(ext == ".txt");
        CheckResult(uri.pathDir() == "/home/sthomas/");
        CheckResult(uri.pathFileBase() == "file");
        CheckResult(uri.pathExt() == ".txt");
        CheckResult(uri.pathFile() == "file.txt");
    }

    // Check path component setters
    {
        daeURI uri(dae, "file:");
        CheckResult(uri.str() == "file:");
        uri.path("/home/sthomas/", "file", ".txt");
        CheckResult(uri.str() == "file:/home/sthomas/file.txt");
        uri.pathDir("/home/johnny"); // A / should automatically be added to the end for us
        uri.pathFileBase("otherFile");
        uri.pathExt(".dae");
        CheckResult(uri.str() == "file:/home/johnny/otherFile.dae");
        uri.pathFile("file.txt");
        CheckResult(uri.str() == "file:/home/johnny/file.txt");
    }

    // Check path normalization
    CheckResult(daeURI(dae, "file:/d1/d2/d3/../../d4/./file.txt").str() == "file:/d1/d4/file.txt");

    // Check old C string methods
    CheckResult(strcmp(daeURI(dae, "file:/dir/file.txt").getURI(), "file:/dir/file.txt") == 0);
    CheckResult(strcmp(daeURI(dae, "dir/file.txt").getOriginalURI(), "dir/file.txt") == 0);
    {
        daeURI uri(dae), base(dae);
        base.setURI("http://www.example.com");
        uri.setURI("dir/file.txt", &base);
        CheckResult(uri.str() == "http://www.example.com/dir/file.txt");
        uri.setURI("http://www.example.com/dir/file.txt?q#f");
        CheckResult(strcmp(uri.getScheme(), "http") == 0);
        CheckResult(strcmp(uri.getProtocol(), "http") == 0);
        CheckResult(strcmp(uri.getAuthority(), "www.example.com") == 0);
        CheckResult(strcmp(uri.getPath(), "/dir/file.txt") == 0);
        CheckResult(strcmp(uri.getQuery(), "q") == 0);
        CheckResult(strcmp(uri.getFragment(), "f") == 0);
        CheckResult(strcmp(uri.getID(), "f") == 0);
        char buffer1[4], buffer2[32];
        CheckResult(!uri.getPath(buffer1, sizeof(buffer1)));
        CheckResult(uri.getPath(buffer2, sizeof(buffer2)));
        CheckResult(strcmp(buffer2, "/dir/file.txt") == 0);
    }

    // Check makeRelativeTo
    {
        daeURI base(dae, "file:/home/sthomas/");
        daeURI uri1(base, "folder1/file.dae");
        daeURI uri2(base, "folder2/file.dae");
        uri2.makeRelativeTo(&uri1);
        CheckResult(uri2.originalStr() == "../folder2/file.dae");
        CheckResult(uri2.str() == "file:/home/sthomas/folder2/file.dae");
    }

    // Make sure we can handle paths that start with '//'. Libxml uses such paths
    // to represent UNC paths and absolute paths without a drive letter on Windows.
    {
        string scheme, authority, path, query, fragment;
        cdom::parseUriRef("file:////models/cube.dae", scheme, authority, path, query, fragment);
        CheckResult(cdom::assembleUri(scheme, authority, path, query, fragment) == "file:////models/cube.dae");
    }

    return testResult(true);
}


DefineTest(uriBase) {
    DAE dae;
    daeURI uri(dae, cdom::nativePathToUri(lookupTestFile("uri.dae")));
    CheckResult(dae.open(uri.str()));
    domImage::domInit_from* initFrom = dae.getDatabase()->typeLookup<domImage::domInit_from>().at(0);
    CheckResult(initFrom->getRef()->getValue().pathDir() == uri.pathDir());
    return testResult(true);
}


DefineTest(xmlNavigation) {
    DAE dae;
    string file = lookupTestFile("cube.dae");
    domCOLLADA* root = (domCOLLADA*)dae.open(file);
    CheckResult(root);

    CheckResult(root->getChild("library_cameras"));
    CheckResult(root->getChild("contributor") == 0);
    CheckResult(root->getDescendant("steveT") == 0);
    daeElement* upAxis = root->getDescendant("up_axis");
    CheckResult(upAxis);
    CheckResult(upAxis->getParent());
    CheckResult(upAxis->getAncestor("asset"));
    CheckResult(upAxis->getAncestor("library_geometries") == 0);

    CheckResult(root->getChild(daeElement::matchType(domLibrary_cameras::ID())));
    CheckResult(root->getChild(daeElement::matchType(domAsset::domContributor::ID())) == 0);
    CheckResult(root->getDescendant(daeElement::matchType(-10)) == 0);
    upAxis = root->getDescendant(daeElement::matchType(domAsset::domUp_axis::ID()));
    CheckResult(upAxis);
    CheckResult(upAxis->getParent());
    CheckResult(upAxis->getAncestor(daeElement::matchType(domAsset::ID())));
    CheckResult(upAxis->getAncestor(daeElement::matchType(domLibrary_geometries::ID())) == 0);

    return testResult(true);
}


DefineTest(multipleDae) {
    // Basically we just want to make sure that having multiple DAE objects doesn't
    // crash the DOM.
    DAE dae1;
    DAE dae2;
    CheckResult(dae2.open(lookupTestFile("cube.dae")));
    CheckResult(dae1.open(lookupTestFile("duck.dae")));
    return testResult(true);
}


DefineTest(unusedTypeCheck) {
    DAE dae;

    // The following types are defined in the schema but aren't used anywhere in
    // Collada, so they should have a null meta entry:
    //   ellipsoid
    //   ellipsoid/size
    //   InputGlobal
    // Also, <any> doesn't use a single global meta, so it'll also show up in the
    // set of elements that don't have metas.
    //
    // That was the situation for 1.4.
    set<int> expectedUnusedTypes;
    expectedUnusedTypes.insert(domEllipsoid::ID());
    expectedUnusedTypes.insert(domEllipsoid::domSize::ID());
    expectedUnusedTypes.insert(domInput_global::ID());
    expectedUnusedTypes.insert(domAny::ID());

    // In 1.5 these unused elements have been introduced:
    expectedUnusedTypes.insert(domImage_source::ID());
    expectedUnusedTypes.insert(domFx_sampler::ID());
    expectedUnusedTypes.insert(domFx_rendertarget::ID());
    expectedUnusedTypes.insert(domGles2_newparam::ID());
    expectedUnusedTypes.insert(domLimits_sub::ID());
    expectedUnusedTypes.insert(domTargetable_float4::ID());
    expectedUnusedTypes.insert(domCommon_int_or_param::ID());

    // Collect the list of types that don't have a corresponding meta defined
    set<int> actualUnusedTypes;
    const daeMetaElementRefArray &metas = dae.getAllMetas();
    for (size_t i = 0; i < metas.getCount(); i++)
        if (!metas[i])
            actualUnusedTypes.insert((int)i);

    // Make sure the set of unused types matches what we expect
    return testResult(expectedUnusedTypes == actualUnusedTypes);
}


DefineTest(domFx_common_transparent) {

    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.dae")));

    domFx_common_transparent* transparent =
        dae.getDatabase()->typeLookup<domFx_common_transparent>().at(0);

    CheckResult(transparent->getColor() != NULL);
    CheckResult(transparent->getParam() == NULL);
    CheckResult(transparent->getTexture() == NULL);
    CheckResult(transparent->getOpaque() == FX_OPAQUE_A_ONE);

    return testResult(true);
};


DefineTest(autoResolve) {
    // When you load a document, daeIDRefs, xsIDREFS, and daeURIs should resolve automatically.
    // Make sure that works properly.
    DAE dae;
    daeDatabase& database = *dae.getDatabase();
    CheckResult(dae.open(lookupTestFile("Seymour.dae")));

    {
        // Make sure the IDREF_array element had all its daeIDRef objects resolved
        xsIDREFS& idRefs = database.typeLookup<domIdref_array>().at(0)->getValue();
        for (size_t i = 0; i < idRefs.getCount(); i++) {
            CheckResult(idRefs[i].getElement());
        }

        domInstance_controller& ic = *database.typeLookup<domInstance_controller>().at(0);
        CheckResult(ic.getUrl().getElement());

        // In COLLADA 1.5 there is no xsIDREF anymore
        //domImage::domInit_from & initFrom =
        //    *database.typeLookup<domImage::domInit_from>().at(0);
        //if (initFrom.getRef())
        //{
        //    CheckResult(initFrom.getRef()->getValue().getElement());
        //}
        //else
        //{
        //    CheckResult(false);
        //}
    }

    // When you're modifying a new document or creating a new one and you create some
    // new ID or URI refs, they should resolve automatically.
    dae.clear();
    domCOLLADA* root = (domCOLLADA*)dae.add("tmp.dae");
    CheckResult(root);

    // Create a <geometry> with an <IDREF_array>
    CheckResult(root->add("library_geometries geometry mesh source IDREF_array"));
    daeElement* geom = root->getDescendant("geometry");
    geom->setAttribute("id", "myGeom");
    xsIDREFS& idRefs = database.typeLookup<domIdref_array>().at(0)->getValue();
    idRefs.append(daeIDRef("myGeom"));

    // Create a <library_nodes> with a <node> that we'll instantiate via <instance_node>
    daeElement* node1 = root->add("library_nodes node");
    node1->setAttribute("id", "myNode");

    // Create a <node> with an <instance_geometry> and <instance_node> to test URIs
    daeElement* node2 = root->getDescendant("library_nodes")->add("node");
    domInstance_node& instanceNode = *daeSafeCast<domInstance_node>(node2->add("instance_node"));
    domInstance_geometry& instanceGeom = *daeSafeCast<domInstance_geometry>(
        node2->add("instance_geometry"));
    instanceNode.setUrl("#myNode");
    instanceGeom.setUrl("#myGeom");

    // Create a <surface> with an <init_from> to test ID refs
    domImage_source::domRef* ref = daeSafeCast<domImage_source::domRef>(
        root->add("library_images image init_from ref"));
    ref->setValue("myGeom");

    // Make sure everything resolves automatically
    CheckResult(idRefs[0].getElement() == geom);
    CheckResult(instanceGeom.getUrl().getElement() == geom);
    // In COLLADA 1.5 there is no xsIDREF anymore
    //CheckResult(ref->getValue().getElement() == geom);
    CheckResult(instanceNode.getUrl().getElement() == node1);

    return testResult(true);
}


DefineTest(baseURI) {
    DAE dae1, dae2;
    dae1.setBaseURI("http://www.example.com/");
    daeURI uri1(dae1, "myFolder/myFile.dae");
    daeURI uri2(dae2, "myFolder/myFile.dae");
    CheckResult(uri1.str() != uri2.str());
    CheckResult(uri1.str() == "http://www.example.com/myFolder/myFile.dae");
    return testResult(true);
}


DefineTest(databaseLookup) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.dae")));
    daeDatabase& database = *dae.getDatabase();
    daeDocument* doc = database.getDoc(0);
    CheckResult(doc);

    // Test the new functions
    CheckResult(database.idLookup("light-lib").size() == 1);
    CheckResult(database.idLookup("light-lib", doc));
    CheckResult(database.typeLookup(domNode::ID()).size() == 5);
    vector<daeElement*> elts;
    database.typeLookup(domRotate::ID(), elts, doc);
    CheckResult(elts.size() == 15);
    CheckResult(database.typeLookup<domNode>().size() == 5);
    vector<domRotate*> rotateElts;
    database.typeLookup(rotateElts);
    CheckResult(rotateElts.size() == 15);

    // Test the old functions
    CheckResult(database.getElementCount("light-lib") == 1);
    daeElement* elt = NULL;
    database.getElement(&elt, 0, "light-lib", NULL, doc->getDocumentURI()->getURI());
    CheckResult(elt);
    CheckResult(database.getElementCount(NULL, "node") == 5);
    database.getElement(&elt, 8, NULL, "rotate");
    CheckResult(elt);

    return testResult(true);
}


DefineTest(fileExtension) {
    // The DOM used to have a bug where it couldn't resolve URIs or ID refs of
    // documents with extensions other than .dae or .xml. This test ensures that
    // the DOM is extension-agnostic.
    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.cstm")));
    CheckResult(dae.getDatabase()->typeLookup<domAccessor>().at(0)->getSource().getElement());
    CheckResult(dae.writeTo(lookupTestFile("cube.cstm"), getTmpFile("cube_roundTrip.cstm")));
    return testResult(true);
}


DefineTest(zipFile) {
    // The DOM should be able to load a gzip/zlib-compressed dae via libxml.
    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.dae.gz")));
    CheckResult(dae.getDatabase()->typeLookup(domAsset::ID()).size() == 1);
    return testResult(true);
}


DefineTest(charEncoding) {
    // Basically we're just looking for crashes or memory leaks here.
    string file = getTmpFile("charEncoding.dae");
    DAE dae;
    dae.setCharEncoding(DAE::Latin1);
    daeElement* elt = dae.add(file)->add("asset contributor comments");
    CheckResult(elt);
    elt->setCharData("æ ø å ü ä ö");
    CheckResult(dae.writeAll());
    dae.clear();
    CheckResult(dae.open(file));
    return testResult(true);
}


DefineTest(getElementBug) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.dae")));

    // Check daeURI::getElement
    domInstance_geometry* geomInst = dae.getDatabase()->typeLookup<domInstance_geometry>().at(0);
    CheckResult(geomInst->getUrl().getElement());
    daeElement::removeFromParent(geomInst->getUrl().getElement());
    CheckResult(geomInst->getUrl().getElement() == 0);

    // Check daeIDRef::getElement
    daeIDRef idRef(*geomInst);
    idRef.setID("PerspCamera");
    CheckResult(idRef.getElement());
    daeElement::removeFromParent(idRef.getElement());
    CheckResult(idRef.getElement() == 0);

    // Check daeSidRef::resolve
    daeSidRefCache& cache = dae.getSidRefCache();
    daeElement* effect = dae.getDatabase()->typeLookup(domEffect::ID()).at(0);
    daeSidRef sidRef("common", effect);

    CheckResult(cache.empty() && cache.hits() == 0 && cache.misses() == 0);
    daeElement* technique = sidRef.resolve().elt;
    CheckResult(technique && cache.misses() == 1 && !cache.empty());
    sidRef.resolve();
    CheckResult(cache.misses() == 1 && cache.hits() == 1);
    daeElement::removeFromParent(technique);
    CheckResult(cache.empty() && cache.misses() == 0 && cache.hits() == 0);
    CheckResult(sidRef.resolve().elt == NULL);
    CheckResult(cache.empty() && cache.misses() == 1);

    return testResult(true);
}


DefineTest(externalRef) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("externalRef.dae")));
    domInstance_geometry* geomInst = dae.getDatabase()->typeLookup<domInstance_geometry>().at(0);
    daeURI& uri = geomInst->getUrl();
    CheckResult(uri.isExternalReference() == true);
    CheckResult(uri.getReferencedDocument() == NULL);
    CheckResult(uri.getElement());
    CheckResult(uri.getReferencedDocument());
    return testResult(true);
}


DefineTest(charEncodingSetting) {
    DAE dae;
    dae.setGlobalCharEncoding(DAE::Utf8);
    CheckResult(dae.getCharEncoding() == DAE::Utf8);
    dae.setCharEncoding(DAE::Latin1);
    CheckResult(dae.getCharEncoding() == DAE::Latin1);
    DAE dae2;
    CheckResult(dae2.getCharEncoding() == DAE::Utf8);
    return testResult(true);
}


DefineTest(uriCopy) {
    DAE dae;
    CheckResult(dae.open(lookupTestFile("cube.dae")));
    domInstance_geometry* geomInst = dae.getDatabase()->typeLookup<domInstance_geometry>().at(0);
    daeURI& uri = geomInst->getUrl();
    CheckResult(uri.getElement());
    daeURI uriCopy = geomInst->getUrl();
    CheckResult(uriCopy.getElement());
    return testResult(true);
}


DefineTest(badFileLoad) {
    DAE dae;
    CheckResult(!dae.open(lookupTestFile("badFile.dae")));
    return testResult(true);
}


DefineTest(spuriousQuotes) {
    DAE dae;
    CheckResult(!dae.open(lookupTestFile("quotesProblem.dae")));
    return testResult(true);
}


DefineTest(zaeLoading) {
    DAE dae;

    // check if root document is loaded
    std::string testFile = lookupTestFile("duck.zae");
    domCOLLADA* root = (domCOLLADA*)dae.open(testFile);
    CheckResult(root);

    // load sibling doc to root doc
    domInstance_with_extra* instanceVisualScene = daeSafeCast<domInstance_with_extra>(root->getDescendant("instance_visual_scene"));
    CheckResult(instanceVisualScene);
    daeURI visualSceneURI = instanceVisualScene->getUrl();
    domVisual_scene* visualScene = daeSafeCast<domVisual_scene>(visualSceneURI.getElement());
    CheckResult(visualScene);

    // load doc from internal archive
    domInstance_image* instanceImage = daeSafeCast<domInstance_image>(visualScene->getDocument()->getDomRoot()->getDescendant("instance_image"));
    CheckResult(instanceImage);
    daeURI imageURI = instanceImage->getUrl();
    domImage* image = daeSafeCast<domImage>(imageURI.getElement());
    CheckResult(image);

    // check for file in sub dir
    domImage_source::domRef* ref = daeSafeCast<domImage_source::domRef>(image->getDescendant("ref"));
    xsAnyURI imageFileURI = ref->getValue();
    bool imageFileExists = boost::filesystem::exists( cdom::uriToNativePath( imageFileURI.str() ) );
    CheckResult(imageFileExists);

    // load doc from sub dir in internal archive
    domInstance_geometry* instanceGeometry = daeSafeCast<domInstance_geometry>(visualScene->getDocument()->getDomRoot()->getDescendant("instance_geometry"));
    CheckResult(instanceGeometry);
    daeURI geometryURI = instanceGeometry->getUrl();
    domGeometry* geometry = daeSafeCast<domGeometry>(geometryURI.getElement());
    CheckResult(geometry);

    return testResult(true);
}

DefineTest(zaeIllegalArchive) {
    DAE dae;

    // check if root document is loaded
    std::string testFile = lookupTestFile("illegal_archive.zae");
    domCOLLADA* root = (domCOLLADA*)dae.open(testFile);
    CheckResult(!root);

    return testResult(true);
}

// DefineTest(hauntedHouse) {
//  DAE dae;
//  CheckResult(dae.open("/home/sthomas/models/hauntedHouse.dae"));
//  return testResult(true);
// }


// Returns true if all test names are valid
bool checkTests(const set<string>& tests) {
    bool invalidTestFound = false;
    for (set<string>::const_iterator iter = tests.begin(); iter != tests.end(); iter++) {
        if (registeredTests().find(*iter) == registeredTests().end()) {
            if (!invalidTestFound)
                cout << "Invalid arguments:\n";
            cout << "  " << *iter << endl;
            invalidTestFound = true;
        }
    }

    return !invalidTestFound;
}

// Returns the set of tests that failed
map<string, testResult> runTests(const set<string>& tests) {
    map<string, testResult> failedTests;
    for (set<string>::const_iterator iter = tests.begin(); iter != tests.end(); iter++) {
        testResult result = registeredTests()[*iter]->run();
        if (!result.passed)
            failedTests[*iter] = result;
    }
    return failedTests;
}

// Prints test results to the console.
// Returns true if all tests passed, false otherwise.
bool printTestResults(const map<string, testResult>& failedTests) {
    if (!failedTests.empty()) {
        cout << "Failed tests:\n";
        for (map<string, testResult>::const_iterator iter = failedTests.begin();
             iter != failedTests.end();
             iter++) {
            cout << "    " << iter->first;
            if (!iter->second.file.empty()) {
                cout << " (file " << fs::path(iter->second.file).leaf();
                if (iter->second.line != -1)
                    cout << ", line " << iter->second.line << ")";
                else
                    cout << ")";
            }
            cout << endl;
            if (!iter->second.msg.empty()) // Make sure to indent the message
                cout << "        " << replace(iter->second.msg, "\n", "\n        ") << "\n";
        }
        return false;
    }
    else {
        cout << "All tests passed.\n";
        return true;
    }
}

struct tmpDir {
    fs::path path;
    bool deleteWhenDone;

    tmpDir(fs::path& path, bool deleteWhenDone)
        : path(path),
        deleteWhenDone(deleteWhenDone) {
        fs::create_directories(path);
    }

    ~tmpDir() {
        if (deleteWhenDone)
            fs::remove_all(path);
    }
};


int main(int argc, char* argv[]) {
    // Windows memory leak checking
#if defined _MSC_VER && defined _DEBUG
    _CrtSetDbgFlag(_CRTDBG_ALLOC_MEM_DF | _CRTDBG_LEAK_CHECK_DF | _CRTDBG_CHECK_EVERY_1024_DF);
#endif

    if (argc == 1) {
        cout << "Usage:\n"
                "  -printTests - Print the names of all available tests\n"
                "  -all - Run all tests\n"
                "  -leaveTmpFiles - Don't delete the tmp folder containing the generated test files\n"
                "  test1 test2 ... - Run the named tests\n";
        return 0;
    }

    bool printTests = false;
    bool allTests = false;
    bool leaveTmpFiles = false;
    set<string> tests;
    for (int i = 1; i < argc; i++) {
        if (string(argv[i]) == "-printTests")
            printTests = true;
        else if (string(argv[i]) == "-all")
            allTests = true;
        else if (string(argv[i]) == "-leaveTmpFiles")
            leaveTmpFiles = true;
        else
            tests.insert(argv[i]);
    }

#ifdef __CELLOS_LV2__
    // The program crashes on PS3 if we try to delete the tmp directory when we're done.
    // That shouldn't be the case, but it's really not worth trying to fix it now.
    // Just leave the tmp folder.
    leaveTmpFiles = true;
#endif

    // Shut the DOM up
    daeErrorHandler::setErrorHandler(&quietErrorHandler::getInstance());

    dataPath() = (fs::path(argv[0]).branch_path()/"domTestData/").normalize();
    if (!fs::exists(dataPath()))
        dataPath() = (fs::path(argv[0]).branch_path()/"../../test/1.5/data/").normalize();
    tmpPath() = dataPath() / "tmp";
    tmpDir tmp(tmpPath(), !leaveTmpFiles);

    if (checkTests(tests) == false)
        return 0;

    // -printTest
    if (printTests) {
        map<string, domTest*>::iterator iter;
        for (iter = registeredTests().begin(); iter != registeredTests().end(); iter++)
            cout << iter->second->name << endl;
        return 0;
    }

    // -all
    if (allTests) {
        map<string, domTest*>::iterator iter;
        for (iter = registeredTests().begin(); iter != registeredTests().end(); iter++)
            tests.insert(iter->first);
    }

    // test1 test2 ...
    return printTestResults(runTests(tests)) ? 0 : 1;
}
