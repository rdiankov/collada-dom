#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""copies necessary files for deb source package building to a destination directory. The destination directory must not exist
"""
import os, sys
import shutil

if __name__=='__main__':
    srcdir = sys.argv[1]
    destdir = sys.argv[2]
    
    def ignorefiles(src, names):
        if src == srcdir:
            return ['.git', 'release']
        
        if src == os.path.join(srcdir,'dom'):
            return ['test']
        
        if src == os.path.join(srcdir,'dom','external-libs'):
            return ['libxml2-new', 'pcre-8.02', 'tinyxml', 'zlib-1.2.5']
        
        return []
    
    shutil.copytree(srcdir, destdir, ignore = ignorefiles)
