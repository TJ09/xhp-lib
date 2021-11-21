<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class ReflectionXHPClass {
  private $className;
  public function __construct(string $className) {
    $this->className = $className;
    assert(
      class_exists($this->className),
      'Invalid class name: '.$this->className
    );
  }

  public function getReflectionClass(): ReflectionClass {
    return new ReflectionClass($this->getClassName());
  }

  public function getClassName(): string {
    return $this->className;
  }

  public function getElementName(): string {
    return :xhp::class2element($this->getClassName());
  }

  public function getChildren(): ReflectionXHPChildrenDeclaration {
    $class = $this->getClassName();
    return $class::__xhpReflectionChildrenDeclaration();
  }

  public function getAttribute(string $name): ReflectionXHPAttribute {
    $map = $this->getAttributes();
    assert(
      isset($map[$name]),
      sprintf(
        'Tried to get attribute %s for XHP element %s, which does not exist',
        $name,
        $this->getElementName(),
      ),
    );
    return $map[$name];
  }

  public function getAttributes(): iterable/*<string, ReflectionXHPAttribute>*/ {
    $class = $this->getClassName();
    return $class::__xhpReflectionAttributes();
  }

  public function getCategories(): iterable/*<string>*/ {
    $class = $this->getClassName();
    return $class::__xhpReflectionCategoryDeclaration();
  }
}
