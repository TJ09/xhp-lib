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

class :h1 extends :xhp:html-element {
  category %flow;
  children (pcdata | %phrase)*;
  protected string $tagName = 'h1';
}
