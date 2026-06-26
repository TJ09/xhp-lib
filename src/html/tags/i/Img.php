<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  Copyright (c) 2018-present, T.J. Lipscomb
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class :img extends :xhp:html-singleton {
  attribute
    string alt,
    string attributionsrc,
    enum {'anonymous', 'use-credentials'} crossorigin,
    enum {'async', 'auto', 'sync'} decoding,
    string elementtiming,
    enum {'auto', 'high', 'low'} fetchpriority,
    int height,
    bool ismap,
    enum {'eager', 'lazy'} loading,
    enum {'no-referrer', 'no-referrer-when-downgrade', 'origin', 'origin-when-cross-origin', 'same-origin', 'strict-origin', 'strict-origin-when-cross-origin', 'unsafe-url'} referrerpolicy,
    string sizes,
    string src,
    string srcset,
    string usemap,
    int width;
  category %flow, %phrase;
  protected string $tagName = 'img';
}
