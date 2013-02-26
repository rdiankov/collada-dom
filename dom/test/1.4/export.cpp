/*
 * Copyright 2006 Sony Computer Entertainment Inc.
 *
 * Licensed under the MIT Open Source License, for details please see license.txt or the website
 * http://www.opensource.org/licenses/mit-license.php
 *
 */
#include <dae.h>
#include <dae/daeUtils.h>
#include <dom/domCOLLADA.h>
#include "domTest.h"

using namespace std;
using namespace cdom;

// Demonstrates how to use the DOM to create a simple, textured Collada model
// and save it to disk.

testResult addAsset(daeElement* root);
testResult addGeometry(daeElement* root);
testResult addImage(daeElement* root);
testResult addEffect(daeElement* root);
testResult addMaterial(daeElement* root);
testResult addVisualScene(daeElement* root);

DefineTest(export) {
    DAE dae;
    string file = getTmpFile("export.dae");
    domCOLLADA* root = (domCOLLADA*)dae.add(file);
    CheckResult(root);

    CheckTestResult(addAsset(root));
    CheckTestResult(addGeometry(root));
    CheckTestResult(addImage(root));
    CheckTestResult(addEffect(root));
    CheckTestResult(addMaterial(root));
    CheckTestResult(addVisualScene(root));

    dae.writeAll();

    // As a very simple check for possible errors, make sure the document loads
    // back in successfully.
    dae.clear();
    CheckResult(dae.open(file));

    return testResult(true);
}

testResult addAsset(daeElement* root) {
    SafeAdd(root, "asset", asset);
    asset->add("created")->setCharData("2008-02-23T13:30:00Z");
    asset->add("modified")->setCharData("2008-02-23T13:30:00Z");
    return testResult(true);
}

template<typename T>
daeTArray<T> rawArrayToDaeArray(T rawArray[], size_t count) {
    daeTArray<T> result;
    for (size_t i = 0; i < count; i++)
        result.append(rawArray[i]);
    return result;
}

// "myGeom" --> "#myGeom"
string makeUriRef(const string& id) {
    return string("#") + id;
}

testResult addSource(daeElement* mesh,
                     const string& srcID,
                     const string& paramNames,
                     domFloat values[],
                     int valueCount) {
    SafeAdd(mesh, "source", src);
    src->setAttribute("id", srcID.c_str());

    domFloat_array* fa = daeSafeCast<domFloat_array>(src->add("float_array"));
    CheckResult(fa);
    fa->setId((src->getAttribute("id") + "-array").c_str());
    fa->setCount(valueCount);
    fa->getValue() = rawArrayToDaeArray(values, valueCount);

    domAccessor* acc = daeSafeCast<domAccessor>(src->add("technique_common accessor"));
    CheckResult(acc);
    acc->setSource(makeUriRef(fa->getId()).c_str());

    list<string> params = tokenize(paramNames, " ");
    acc->setStride(params.size());
    acc->setCount(valueCount/params.size());
    for (tokenIter iter = params.begin(); iter != params.end(); iter++) {
        SafeAdd(acc, "param", p);
        p->setAttribute("name", iter->c_str());
        p->setAttribute("type", "float");
    }

    return testResult(true);
}

testResult addInput(daeElement* triangles,
                    const string& semantic,
                    const string& srcID,
                    int offset) {
    domInputLocalOffset* input = daeSafeCast<domInputLocalOffset>(triangles->add("input"));
    CheckResult(input);
    input->setSemantic(semantic.c_str());
    input->setOffset(offset);
    input->setSource(makeUriRef(srcID).c_str());
    if (semantic == "TEXCOORD")
        input->setSet(0);
    return testResult(true);
}

testResult addGeometry(daeElement* root) {
    SafeAdd(root, "library_geometries", geomLib);
    SafeAdd(geomLib, "geometry", geom);
    string geomID = "cubeGeom";
    geom->setAttribute("id", geomID.c_str());
    SafeAdd(geom, "mesh", mesh);

    // Add the position data
    domFloat posArray[] = { -10, -10, -10,
                            -10, -10,  10,
                            -10,  10, -10,
                            -10,  10,  10,
                            10, -10, -10,
                            10, -10,  10,
                            10,  10, -10,
                            10,  10,  10 };
    int count = sizeof(posArray)/sizeof(posArray[0]);
    CheckTestResult(addSource(mesh, geomID + "-positions", "X Y Z", posArray, count));

    // Add the normal data
    domFloat normalArray[] = {  1,  0,  0,
                                -1,  0,  0,
                                0,  1,  0,
                                0, -1,  0,
                                0,  0,  1,
                                0,  0, -1 };
    count = sizeof(normalArray)/sizeof(normalArray[0]);
    CheckTestResult(addSource(mesh, geomID + "-normals", "X Y Z", normalArray, count));

    // Add the tex coord data
    domFloat uvArray[] = { 0, 0,
                           0, 1,
                           1, 0,
                           1, 1 };
    count = sizeof(uvArray)/sizeof(uvArray[0]);
    CheckTestResult(addSource(mesh, geomID + "-uv", "S T", uvArray, count));

    // Add the <vertices> element
    SafeAdd(mesh, "vertices", vertices);
    vertices->setAttribute("id", (geomID + "-vertices").c_str());
    SafeAdd(vertices, "input", verticesInput);
    verticesInput->setAttribute("semantic", "POSITION");
    verticesInput->setAttribute("source", makeUriRef(geomID + "-positions").c_str());

    // Add the <triangles> element.
    // Each line is one triangle.
    domUint indices[] = {   0, 1, 0,   1, 1, 1,   2, 1, 2,
                            1, 1, 1,   3, 1, 3,   2, 1, 2,
                            0, 2, 0,   4, 2, 1,   1, 2, 2,
                            4, 2, 1,   5, 2, 3,   1, 2, 2,
                            1, 4, 0,   5, 4, 1,   3, 4, 2,
                            5, 4, 1,   7, 4, 3,   3, 4, 2,
                            5, 0, 0,   4, 0, 1,   7, 0, 2,
                            4, 0, 1,   6, 0, 3,   7, 0, 2,
                            4, 5, 0,   0, 5, 1,   6, 5, 2,
                            0, 5, 1,   2, 5, 3,   6, 5, 2,
                            3, 3, 0,   7, 3, 1,   2, 3, 2,
                            7, 3, 1,   6, 3, 3,   2, 3, 2 };
    count = sizeof(indices)/sizeof(indices[0]);

    domTriangles* triangles = daeSafeCast<domTriangles>(mesh->add("triangles"));
    CheckResult(triangles);
    triangles->setCount(count/(3*3)); // 3 indices per vertex, 3 vertices per triangle
    triangles->setMaterial("mtl");

    CheckTestResult(addInput(triangles, "VERTEX",   geomID + "-vertices", 0));
    CheckTestResult(addInput(triangles, "NORMAL",   geomID + "-normals",  1));
    CheckTestResult(addInput(triangles, "TEXCOORD", geomID + "-uv",       2));

    domP* p = daeSafeCast<domP>(triangles->add("p"));
    CheckResult(p);
    p->getValue() = rawArrayToDaeArray(indices, count);

    return testResult(true);
}

testResult addImage(daeElement* root) {
    SafeAdd(root, "library_images", imageLib);
    SafeAdd(imageLib, "image", image);
    image->setAttribute("id", "img");
    image->add("init_from")->setCharData("../texture.bmp");
    return testResult(true);
}

testResult addEffect(daeElement* root) {
    SafeAdd(root, "library_effects", effectLib);
    SafeAdd(effectLib, "effect", effect);
    effect->setAttribute("id", "cubeEffect");
    SafeAdd(effect, "profile_COMMON", profile);

    // Add a <surface>
    SafeAdd(profile, "newparam", newparam);
    newparam->setAttribute("sid", "surface");
    SafeAdd(newparam, "surface", surface);
    surface->setAttribute("type", "2D");
    surface->add("init_from")->setCharData("img");

    // Add a <sampler2D>
    newparam = profile->add("newparam");
    CheckResult(newparam);
    newparam->setAttribute("sid", "sampler");
    SafeAdd(newparam, "sampler2D", sampler);
    sampler->add("source")->setCharData("surface");
    sampler->add("minfilter")->setCharData("LINEAR_MIPMAP_LINEAR");
    sampler->add("magfilter")->setCharData("LINEAR");

    SafeAdd(profile, "technique", technique);
    technique->setAttribute("sid", "common");
    SafeAdd(technique, "phong diffuse texture", texture);
    texture->setAttribute("texture", "sampler");
    texture->setAttribute("texcoord", "uv0");

    return testResult(true);
}

testResult addMaterial(daeElement* root) {
    SafeAdd(root, "library_materials", materialLib);
    SafeAdd(materialLib, "material", material);
    material->setAttribute("id", "cubeMaterial");
    material->add("instance_effect")->setAttribute("url", makeUriRef("cubeEffect").c_str());

    return testResult(true);
}

testResult addVisualScene(daeElement* root) {
    SafeAdd(root, "library_visual_scenes", visualSceneLib);
    SafeAdd(visualSceneLib, "visual_scene", visualScene);
    visualScene->setAttribute("id", "cubeScene");

    // Add a <node> with a simple transformation
    SafeAdd(visualScene, "node", node);
    node->setAttribute("id", "cubeNode");
    node->add("rotate")->setCharData("1 0 0 45");
    node->add("translate")->setCharData("0 10 0");

    // Instantiate the <geometry>
    SafeAdd(node, "instance_geometry", instanceGeom);
    instanceGeom->setAttribute("url", makeUriRef("cubeGeom").c_str());

    // Bind material parameters
    SafeAdd(instanceGeom, "bind_material technique_common instance_material", instanceMaterial);
    instanceMaterial->setAttribute("symbol", "mtl");
    instanceMaterial->setAttribute("target", makeUriRef("cubeMaterial").c_str());

    SafeAdd(instanceMaterial, "bind_vertex_input", bindVertexInput);
    bindVertexInput->setAttribute("semantic", "uv0");
    bindVertexInput->setAttribute("input_semantic", "TEXCOORD");
    bindVertexInput->setAttribute("input_set", "0");

    // Add a <scene>
    root->add("scene instance_visual_scene")->setAttribute("url", makeUriRef("cubeScene").c_str());

    return testResult(true);
}
