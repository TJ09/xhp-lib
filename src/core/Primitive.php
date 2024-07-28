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

/**
 * :x:primitive lays down the foundation for very low-level elements. You
 * should directly :x:primitive only if you are creating a core element that
 * needs to directly implement stringify(). All other elements should subclass
 * from :x:element.
 */
abstract class :x:primitive extends :x:composable-element implements XHPRoot {
  abstract protected function stringify(): string;

  final public function toString(): string {
    $this->__flushElementChildren();
    if (:xhp::isChildValidationEnabled()) {
      $this->validateChildren();
    }
    return $this->stringify();
  }
}
