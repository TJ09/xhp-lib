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

class :figure extends :xhp:html-element {
  category %flow, %sectioning;
  children ((:figcaption, %flow+) | (%flow+, :figcaption?));
  protected string $tagName = 'figure';
}
