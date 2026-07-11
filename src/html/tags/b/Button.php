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

class :button extends :xhp:html-element {
  attribute
    bool autofocus,
    string command,
    string commandfor,
    bool disabled,
    string form,
    string formaction,
    string formenctype,
    enum {'get', 'post', 'dialog'} formmethod,
    bool formnovalidate,
    string formtarget,
    string menu,
    string name,
    string popovertarget,
    enum {'hide', 'show', 'toggle'} popovertargetaction,
    enum {'submit', 'button', 'reset'} type,
    string value;
  category %flow, %phrase, %interactive;
  // Should not contain interactive
  children (pcdata | %phrase)*;
  protected string $tagName = 'button';
}
