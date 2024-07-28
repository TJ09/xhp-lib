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

class :option extends :xhp:pcdata-element {
  attribute
    bool disabled,
    string label,
    bool selected,
    string value;
  protected string $tagName = 'option';
}
