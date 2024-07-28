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

abstract class :xhp implements XHPChild, JsonSerializable {
  abstract public function __construct(
    iterable/*<string, mixed>*/ $attributes,
    iterable/*<XHPChild>*/ $children
  );
  abstract public function appendChild(mixed $child): self;
  abstract public function prependChild(mixed $child): self;
  abstract public function replaceChildren(/*...*/): self;
  abstract public function getChildren(
    ?string $selector = null
  ): array/*<XHPChild>*/;
  abstract public function getFirstChild(?string $selector = null)/*: ?XHPChild*/;
  abstract public function getLastChild(?string $selector = null)/*: ?XHPChild*/;
  abstract public function getAttribute(string $attr)/*: mixed*/;
  abstract public function getAttributes(): array/*<string, mixed>*/;
  abstract public function setAttribute(string $attr, mixed $val): self;
  abstract public function setAttributes(
    iterable/*<string, mixed>*/ $attrs
  ): self;
  abstract public function isAttributeSet(string $attr): bool;
  abstract public function removeAttribute(string $attr): self;
  abstract public function categoryOf(string $cat): bool;
  abstract public function toString(): string;

  // TODO: maybe add type hints at the extension level.
  abstract protected function &__xhpCategoryDeclaration()/*: array*/;
  abstract protected function &__xhpChildrenDeclaration()/*: mixed*/;
  protected static function &__xhpAttributeDeclaration(
  )/*: array*/ {
    return array();
  }

  public ?string $source = null;

  /**
   * Enabling validation will give you stricter documents; you won't be able to
   * do many things that violate the XHTML 1.0 Strict spec. It is recommend that
   * you leave this on because otherwise things like the `children` keyword will
   * do nothing. This validation comes at some CPU cost, however, so if you are
   * running a high-traffic site you will probably want to disable this in
   * production. You should still leave it on while developing new features,
   * though.
   */
  private static bool $validateChildren = true;
  private static bool $validateAttributes = false;

  public static function disableChildValidation(): void {
    self::$validateChildren = false;
  }

  public static function enableChildValidation(): void {
    self::$validateChildren = true;
  }

  public static function isChildValidationEnabled(): bool {
    return self::$validateChildren;
  }

  public static function disableAttributeValidation(): void {
    self::$validateAttributes = false;
  }

  public static function enableAttributeValidation(): void {
    self::$validateAttributes = true;
  }

  public static function isAttributeValidationEnabled(): bool {
    return self::$validateAttributes;
  }

  final public function __toString(): string {
    return $this->toString();
  }

  final public function jsonSerialize(): string {
    return $this->toString();
  }

  final protected static function renderChild(
    // FIXME: See XHPChild declaration.
    /*XHPChild*/ $child
  ): string {
    if ($child instanceof :xhp) {
      return $child->toString();
    } else if ($child instanceof XHPUnsafeRenderable) {
      return $child->toHTMLString();
    } else if ($child instanceof iterable || is_array($child)) {
      throw new XHPRenderArrayException('Can not render traversables!');
    } else {
      return htmlspecialchars((string)$child);
    }
  }

  public static function element2class(string $element): string {
    return 'xhp_'.str_replace(array(':', '-'), array('__', '_'), $element);
  }

  public static function class2element(string $class): string {
    return str_replace(
      array('__', '_'),
      array(':', '-'),
      preg_replace('#^\\\?xhp_#i', '', $class)
    );
  }
}
