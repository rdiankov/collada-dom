/*
 * Copyright 2006 Sony Computer Entertainment Inc.
 *
 * Licensed under the MIT Open Source License, for details please see license.txt or the website
 * http://www.opensource.org/licenses/mit-license.php
 *
 */

#include <dae.h>
#include <dae/daeDatabase.h>
#include <dae/daeDom.h>
#include <dae/daeIDRef.h>
#include <dae/daeMetaElement.h>
#include <modules/daeSTLDatabase.h>
#include <dae/daeErrorHandler.h>
#include <dae/daeRawResolver.h>
#include <dae/daeStandardURIResolver.h>

#ifdef DOM_INCLUDE_LIBXML
#include <modules/daeLIBXMLPlugin.h>
#endif

#ifdef DOM_INCLUDE_TINYXML
#include <dae/daeTinyXMLPlugin.h>
#endif

using namespace std;

// Don't include domConstants.h because it varies depending on the dom version,

daeInt DAEInstanceCount = 0;
DAE::charEncoding DAE::globalCharEncoding = DAE::Utf8;

void
DAE::cleanup()
{
    //Contributed by Nus - Wed, 08 Nov 2006
    daeStringRef::releaseStringTable();
    //----------------------

#ifndef NO_BOOST
    try
    {
        boost::filesystem::remove_all(cdom::getSafeTmpDir());
    }
    catch (...)
    {
        daeErrorHandler::get()->handleWarning("Could not remove temporary directory in DAE::cleanup()\n");
    }
#endif
}

void DAE::init(daeDatabase* database_, daeIOPlugin* ioPlugin, const char* version) {
    database = NULL;
    plugin = NULL;
    defaultDatabase = false;
    defaultPlugin = false;

    metas.setCount(GetColladaTypeCount(version));
    initializeDomMeta(*this,version);
    COLLADA_VERSION = GetCOLLADA_VERSION(version);
    COLLADA_NAMESPACE = GetCOLLADA_NAMESPACE(version);
    DAEInstanceCount++;

    // The order of the URI resolvers is significant, so be careful
    uriResolvers.list().append(new daeRawResolver(*this));
    uriResolvers.list().append(new daeStandardURIResolver(*this));

    idRefResolvers.addResolver(new daeDefaultIDRefResolver(*this));

    setDatabase(database_);
    setIOPlugin(ioPlugin);
}

DAE::~DAE()
{
    if (defaultDatabase)
        delete database;
    if (defaultPlugin)
        delete plugin;
    if ( --DAEInstanceCount <= 0 )
        cleanup();
}

// Database setup
daeDatabase* DAE::getDatabase()
{
    return database;
}

daeInt DAE::setDatabase(daeDatabase* _database)
{
    if (defaultDatabase)
        delete database;
    if (_database)
    {
        defaultDatabase = false;
        database = _database;
    }
    else
    {
        //create default database
        database = new daeSTLDatabase(*this);
        defaultDatabase = true;
    }
    database->setMeta(getMeta(getDomCOLLADAID(COLLADA_VERSION)));
    return DAE_OK;
}

// IO Plugin setup
daeIOPlugin* DAE::getIOPlugin()
{
    return plugin;
}

daeInt DAE::setIOPlugin(daeIOPlugin* _plugin)
{
    if (defaultPlugin)
        delete plugin;
    if (_plugin) {
        defaultPlugin = false;
        plugin = _plugin;
    }
    else {
        plugin = NULL;
        defaultPlugin = true;

        //create default plugin
#ifdef DOM_INCLUDE_LIBXML
        plugin = new daeLIBXMLPlugin(*this);
#else
#ifdef DOM_INCLUDE_TINYXML
        plugin = new daeTinyXMLPlugin;
#endif
#endif

        if (!plugin) {
            daeErrorHandler::get()->handleWarning("No IOPlugin Set");
            plugin = new daeIOEmpty;
            return DAE_ERROR;
        }
    }

    int res = plugin->setMeta(getMeta(getDomCOLLADAID(COLLADA_VERSION)));
    if (res != DAE_OK) {
        if (defaultPlugin) {
            defaultPlugin = false;
            delete plugin;
        }
        plugin = NULL;
    }
    return res;
}


// Take a path (either a URI ref or a file system path) and return a full URI,
// using the current working directory as the base URI if a relative URI
// reference is given.
string DAE::makeFullUri(const string& path) {
    daeURI uri(*this, cdom::nativePathToUri(path));
    return uri.str();
}


daeElement* DAE::add(const string& path) {
    close(path);
    string uri = makeFullUri(path);
    database->insertDocument(uri.c_str());
    return getRoot(uri);
}

domCOLLADAProxy* DAE::openCommon(const string& path, daeString buffer) {
    close(path);
    string uri = makeFullUri(path);
    plugin->setDatabase(database);
    if (plugin->read(daeURI(*this, uri.c_str()), buffer) != DAE_OK)
        return NULL;
    return getRoot(path);
}

domCOLLADAProxy* DAE::open(const string& path) {
    return openCommon(path, NULL);
}

domCOLLADAProxy* DAE::openFromMemory(const string& path, daeString buffer) {
    return openCommon(path, buffer);
}

bool DAE::writeCommon(const string& docPath, const string& pathToWriteTo, bool replace) {
    string docUri = makeFullUri(docPath),
    uriToWriteTo = makeFullUri(pathToWriteTo);
    plugin->setDatabase(database);
    if (daeDocument* doc = getDoc(docUri))
        return plugin->write(daeURI(*this, uriToWriteTo.c_str()), doc, replace) == DAE_OK;
    return false;
}

bool DAE::write(const string& path) {
    return writeCommon(path, path, true);
}

bool DAE::writeTo(const string& docPath, const string& pathToWriteTo) {
    return writeCommon(docPath, pathToWriteTo, true);
}

bool DAE::writeToMemory(const string& docPath, std::vector<char>& output)
{
    string docUri = makeFullUri(docPath);
    plugin->setDatabase(database);
    if (daeDocument* doc = getDoc(docUri)) {
        return plugin->writeToMemory(output, doc) == DAE_OK;
    }
    return false;
}

bool DAE::writeAll() {
    for (int i = 0; i < getDocCount(); i++)
        if (save((daeUInt)i, true) != DAE_OK)
            return false;
    return true;
}

void DAE::close(const string& path) {
    database->removeDocument(getDoc(makeFullUri(path).c_str()));
}

daeInt DAE::clear() {
    database->clear();
    rawRefCache.clear();
    sidRefCache.clear();
    return DAE_OK;
}


// Deprecated methods
daeInt DAE::load(daeString uri, daeString docBuffer) {
    return openCommon(uri, docBuffer) ? DAE_OK : DAE_ERROR;
}

daeInt DAE::save(daeString uri, daeBool replace) {
    return writeCommon(uri, uri, replace) ? DAE_OK : DAE_ERROR;
}

daeInt DAE::save(daeUInt documentIndex, daeBool replace) {
    if ((int)documentIndex >= getDocCount())
        return DAE_ERROR;

    // Save it out to the URI it was loaded from
    daeString uri = getDoc((int)documentIndex)->getDocumentURI()->getURI();
    return writeCommon(uri, uri, replace) ? DAE_OK : DAE_ERROR;
}

daeInt DAE::saveAs(daeString uriToSaveTo, daeString docUri, daeBool replace) {
    return writeCommon(docUri, uriToSaveTo, replace) ? DAE_OK : DAE_ERROR;
}

daeInt DAE::saveAs(daeString uriToSaveTo, daeUInt documentIndex, daeBool replace) {
    if ((int)documentIndex >= getDocCount())
        return DAE_ERROR;

    daeString docUri = getDoc((int)documentIndex)->getDocumentURI()->getURI();
    return writeCommon(docUri, uriToSaveTo, replace) ? DAE_OK : DAE_ERROR;
}

daeInt DAE::unload(daeString uri) {
    close(uri);
    return DAE_OK;
}


int DAE::getDocCount() {
    return (int)database->getDocumentCount();
}

daeDocument* DAE::getDoc(int i) {
    return database->getDocument(i);
}

daeDocument* DAE::getDoc(const string& path) {
    return database->getDocument(makeFullUri(path).c_str(), true);
}

domCOLLADAProxy* DAE::getRoot(const string& path) {
    if (daeDocument* doc = getDoc(path))
        return (domCOLLADAProxy*)doc->getDomRoot();
    return NULL;
}

bool DAE::setRoot(const string& path, domCOLLADAProxy* root) {
    if (daeDocument* doc = getDoc(path))
        doc->setDomRoot(root);
    else
        database->insertDocument(makeFullUri(path).c_str(), root);
    return getRoot(path) != NULL;
}

domCOLLADAProxy* DAE::getDom(daeString uri) {
    return getRoot(uri);
}

daeInt DAE::setDom(daeString uri, domCOLLADAProxy* dom) {
    return setRoot(uri, dom);
}

daeString DAE::getDomVersion()
{
    return(COLLADA_VERSION);
}

daeString DAE::getColladaNamespace()
{
    return(COLLADA_NAMESPACE);
}

daeAtomicTypeList& DAE::getAtomicTypes() {
    return atomicTypes;
}

daeMetaElement* DAE::getMeta(daeInt typeID) {
    if (typeID < 0 || typeID >= daeInt(metas.getCount()))
        return NULL;
    return metas[typeID];
}

daeMetaElementRefArray& DAE::getAllMetas() {
    return metas;
}

void DAE::setMeta(daeInt typeID, daeMetaElement& meta) {
    if (typeID < 0 || typeID >= daeInt(metas.getCount()))
        return;
    metas[typeID] = &meta;
}

daeURIResolverList& DAE::getURIResolvers() {
    return uriResolvers;
}

daeURI& DAE::getBaseURI() {
    return baseUri;
}

void DAE::setBaseURI(const daeURI& uri) {
    baseUri = uri;
}

void DAE::setBaseURI(const string& uri) {
    baseUri = uri.c_str();
}

daeIDRefResolverList& DAE::getIDRefResolvers() {
    return idRefResolvers;
}

daeRawRefCache& DAE::getRawRefCache() {
    return rawRefCache;
}

daeSidRefCache& DAE::getSidRefCache() {
    return sidRefCache;
}

void DAE::dummyFunction1() {
}

DAE::charEncoding DAE::getGlobalCharEncoding() {
    return globalCharEncoding;
}

void DAE::setGlobalCharEncoding(charEncoding encoding) {
    globalCharEncoding = encoding;
}

DAE::charEncoding DAE::getCharEncoding() {
    return localCharEncoding.get() ? *localCharEncoding : getGlobalCharEncoding();
}

void DAE::setCharEncoding(charEncoding encoding) {
    localCharEncoding.reset(new charEncoding(encoding));
}
