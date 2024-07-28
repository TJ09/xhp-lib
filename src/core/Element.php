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
 * :x:element defines an interface that all user-land elements should subclass
 * from. The main difference between :x:element and :x:primitive is that
 * subclasses of :x:element should implement `render()` instead of `stringify`.
 * This is important because most elements should not be dealing with strings
 * of markup.
 */
abstract class :x:element extends :x:composable-element implements XHPRoot {
  abstract protected function render(): XHPRoot;

  final public function toString(): string {
    if (:xhp::isChildValidationEnabled()) {
      $this->validateChildren();
    }
    $that = $this->__flushRenderedRootElement();

    return $that->toString();
  }

  protected function __renderAndProcess(): XHPRoot {
    if (:xhp::isChildValidationEnabled()) {
      $this->validateChildren();
    }

      $composed = $this->render();

    $composed->__transferContext($this->getAllContexts());
    // FIXME: traits cannot implement interfaces, sadly.
    // if ($this instanceof XHPHasTransferAttributes) {
    if (method_exists($this, 'transferAttributesToRenderedRoot')) {
      $this->transferAttributesToRenderedRoot($composed);
    }

    return $composed;
  }

  final protected function __flushRenderedRootElement(): :x:primitive {
    $that = $this;
    // Flush root elements returned from render() to an :x:primitive
    while ($that instanceof :x:element) {
      $that = $that->__renderAndProcess();
    }

    if ($that instanceof :x:primitive) {
      return $that;
    }

    // render() must always (eventually) return :x:primitive
    throw new XHPCoreRenderException($this, $that);
  }
}
