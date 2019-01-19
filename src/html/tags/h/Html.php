<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class :html extends :xhp:html-element {
  attribute
    string manifest,
    string xmlns;
  children (:head, :body);
  protected /*string*/ $tagName = 'html';
}
