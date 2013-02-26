/*
 * Copyright 2006 Sony Computer Entertainment Inc.
 *
 * Licensed under the MIT Open Source License, for details please see license.txt or the website
 * http://www.opensource.org/licenses/mit-license.php
 *
 */
// The DOM used to provide an "integration library", which was a mechanism for
// converting the DOM's representation of a Collada model to the user's representation.
// The integration classes were very clumsy and not particularly useful, so they
// were removed in December 07. In their place, setUserData and getUserData methods
// were added to the daeElement class. This program shows how you might write a Collada
// importer using these new methods instead of the integration classes.
//
// Our model structure consists of nodes, meshes, and materials. We create them by
// converting from domNode, domGeometry, and domMaterial, respectively. We'll
// demonstrate how you can write an importer to traverse the Collada DOM element
// hierarchy and attach our model representation structures to the dom* classes.

#include <list>
#include <vector>
#include <iostream>
#include <dae.h>
#include <dom/domMaterial.h>
#include <dom/domGeometry.h>
#include <dom/domNode.h>
#include <dom/domCOLLADA.h>
#include "domTest.h"

using namespace std;

#define Check(val) if (!(val)) throw exception();


// Our material structure, which we create by converting a domMaterial object
class Material {
public:
    vector<float> diffuseColor;
    string diffuseTexture;
    // ... and lots of other parameters

    Material(domMaterial& mtl) {
        // Grab the <effect> from the <material> and initalize the parameters
    }
};


// Our mesh structure, which we create by converting a domGeometry object
class Mesh {
public:
    Material* mtl;
    // Vertex info, etc

    Mesh(domGeometry& geom) {
        // Parse the <geometry> element, extract vertex data, etc
    }
};


// Our node structure, which we create by converting a domNode object
class Node {
public:
    list<Mesh*> meshes;
    list<Node*> childNodes;

    // This is defined later to work around a circular dependency on the lookup function
    Node(domNode& node);
};


// This function checks to see if a user data object has already been attached to
// the DOM object. If so, that object is casted from void* to the appropriate type
// and returned, otherwise the object is created and attached to the DOM object
// via the setUserData method.
template<typename MyType, typename DomType>
MyType& lookup(DomType& domObject) {
    if (!domObject.getUserData())
        domObject.setUserData(new MyType(domObject));
    return *(MyType*)(domObject.getUserData());
}

// This function traverses all the DOM objects of a particular type and frees
// destroys the associated user data object.
template<typename MyType, typename DomType>
void freeConversionObjects(DAE& dae) {
    vector<daeElement*> elts = dae.getDatabase()->typeLookup(DomType::ID());
    for (size_t i = 0; i < elts.size(); i++)
        delete (MyType*)elts[i]->getUserData();
}


Node::Node(domNode& node) {
    // Recursively convert all child nodes. First iterate over the <node> elements.
    for (size_t i = 0; i < node.getNode_array().getCount(); i++)
        childNodes.push_back(&lookup<Node, domNode>(*node.getNode_array()[i]));

    // Then iterate over the <instance_node> elements.
    for (size_t i = 0; i < node.getInstance_node_array().getCount(); i++) {
        domNode* child = daeSafeCast<domNode>(
            node.getInstance_node_array()[i]->getUrl().getElement());
        Check(child);
        childNodes.push_back(&lookup<Node, domNode>(*child));
    }

    // Iterate over all the <instance_geometry> elements
    for (size_t i = 0; i < node.getInstance_geometry_array().getCount(); i++) {
        domInstance_geometry* instanceGeom = node.getInstance_geometry_array()[i];
        domGeometry* geom = daeSafeCast<domGeometry>(instanceGeom->getUrl().getElement());
        Check(geom);

        // Lookup the material that we should apply to the <geometry>. In a real app
        // we'd need to worry about having multiple <instance_material>s, but in this
        // test let's just convert the first <instance_material> we find.
        domInstance_material* instanceMtl = daeSafeCast<domInstance_material>(
            instanceGeom->getDescendant("instance_material"));
        Check(instanceMtl);
        domMaterial* mtl = daeSafeCast<domMaterial>(instanceMtl->getTarget().getElement());
        Check(mtl);
        Material& convertedMtl = lookup<Material, domMaterial>(*mtl);

        // Now convert the geometry, add the result to our list of meshes, and assign
        // the mesh a material.
        meshes.push_back(&lookup<Mesh, domGeometry>(*geom));
        meshes.back()->mtl = &convertedMtl;
    }
}


void convertModel(domCOLLADA& root) {
    // We need to convert the model from the DOM's representation to our internal representation.
    // First find a <visual_scene> to load. In a real app we would look for and load all
    // the <visual_scene>s in a document, but for this app we just convert the first
    // <visual_scene> we find.
    domVisual_scene* visualScene = daeSafeCast<domVisual_scene>(root.getDescendant("visual_scene"));
    Check(visualScene);

    // Now covert all the <node>s in the <visual_scene>. This is a recursive process,
    // so any child nodes will also be converted.
    domNode_Array& nodes = visualScene->getNode_array();
    for (size_t i = 0; i < nodes.getCount(); i++)
        lookup<Node, domNode>(*nodes[i]);
}


DefineTest(integration) {
    // Load a document from disk
    string file = lookupTestFile("cube.dae");
    DAE dae;
    domCOLLADA* root = (domCOLLADA*)dae.open(file);
    CheckResult(root);

    // Do the conversion. The conversion process throws an exception on error, so
    // we'll include a try/catch handler.
    try {
        convertModel(*root);
    }
    catch (const exception&) {
        return testResult(false);
    }

    // Don't forget to destroy the objects we created during the conversion process
    freeConversionObjects<Node, domNode>(dae);
    freeConversionObjects<Mesh, domGeometry>(dae);
    freeConversionObjects<Material, domMaterial>(dae);

    return testResult(true);
}
