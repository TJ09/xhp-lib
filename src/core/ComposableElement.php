<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

abstract class :x:composable-element extends :xhp {
  private array $attributes = array();
  private array $children = array();
  private array $context = array();

  const SPECIAL_ATTRIBUTES = array('data' => true, 'aria' => true);

  protected function init(): void {}

  /**
   * A new :x:composable-element is instantiated for every literal tag
   * expression in the script.
   *
   * The following code:
   * $foo = <foo attr="val">bar</foo>;
   *
   * will execute something like:
   * $foo = new xhp_foo(array('attr' => 'val'), array('bar'));
   *
   * @param $attributes    map of attributes to values
   * @param $children      list of children
   */
  final public function __construct(
    iterable/*<string, mixed>*/ $attributes,
    iterable/*<XHPChild>*/ $children
  ) {
    foreach ($children as $child) {
      $this->appendChild($child);
    }
    $this->setAttributes($attributes);
    if (:xhp::isChildValidationEnabled()) {
      // There is some cost to having defaulted unused arguments on a function
      // so we leave these out and get them with func_get_args().
      $args = func_get_args();
      if (isset($args[2])) {
        $this->source = "$args[2]:$args[3]";
      } else {
        $this->source =
          'You have child validation on, but debug information is not being '.
          'passed to XHP objects correctly. Ensure xhp.include_debug is on '.
          'in your PHP configuration. Without this option enabled, '.
          'validation errors will be painful to debug at best.';
      }
    }
    $this->init();
  }

  /**
   * Adds a child to the end of this node. If you give an array to this method
   * then it will behave like a DocumentFragment.
   *
   * @param $child     single child or array of children
   */
  final public function appendChild(/*mixed*/ $child)/*: this*/ {
    if (is_array($child) || $child instanceof Traversable) {
      foreach ($child as $c) {
        $this->appendChild($c);
      }
    } else if ($child instanceof :x:frag) {
      foreach($child->getChildren() as $c) {
        $this->appendChild($c);
      }
    } else if ($child !== null) {
      // FIXME: See XHPChild declaration.
      // assert($child instanceof XHPChild);
      $this->children[] = $child;
    }
    return $this;
  }

  /**
   * Adds a child to the beginning of this node. If you give an array to this
   * method then it will behave like a DocumentFragment.
   *
   * @param $child     single child or array of children
   */
  final public function prependChild(/*mixed*/ $child)/*: this*/ {
    if (is_array($child) || $child instanceof Traversable) {
      foreach (array_reverse($child) as $c) {
        $this->prependChild($c);
      }
    } else if ($child instanceof :x:frag) {
      $this->children = array_merge($child->getChildren(), $this->children);
    } else if ($child !== null) {
      array_unshift($this->children, $child);
    }
    return $this;
  }

  /**
   * Replaces all children in this node. You may pass a single array or
   * multiple parameters.
   *
   * @param $children  Single child or array of children
   */
  final public function replaceChildren(/*...*/)/*: this*/ {
    // This function has been micro-optimized
    $args = func_get_args();
    $new_children = array();
    foreach ($args as $xhp) {
      if ($xhp) {
        if ($xhp instanceof :x:frag) {
          foreach ($xhp->children as $child) {
            $new_children[] = $child;
          }
        } else if (!is_array($xhp) && !($xhp instanceof Traversable)) {
          $new_children[] = $xhp;
        } else {
          foreach ($xhp as $element) {
            if ($element instanceof :x:frag) {
              foreach ($element->children as $child) {
                $new_children[] = $child;
              }
            } else if ($element !== null) {
              $new_children[] = $element;
            }
          }
        }
      }
    }
    $this->children = $new_children;
    return $this;
  }

  /**
   * Fetches all direct children of this element that match a particular tag
   * name or category (or all children if none is given)
   *
   * @param $selector   tag name or category (optional)
   * @return array
   */
  final public function getChildren(?string $selector = null): array {
    if (!$selector) {
      return $this->children;
    }
    $result = array();
      if ($selector[0] == '%') {
        $selector = substr($selector, 1);
        foreach ($this->children as $child) {
          if ($child instanceof :xhp && $child->categoryOf($selector)) {
          $result[] = $child;
          }
        }
      } else {
        $selector = :xhp::element2class($selector);
        foreach ($this->children as $child) {
          if ($child instanceof $selector) {
          $result[] = $child;
        }
      }
    }
    return $result;
  }


  /**
   * Fetches the first direct child of the element, or the first child that
   * matches the tag if one is given
   *
   * @param $selector   string   tag name or category (optional)
   * @return            element  the first child node (with the given selector),
   *                             false if there are no (matching) children
   */
  final public function getFirstChild(?string $selector = null): ?XHPChild {
    if (!$selector) {
      return $this->children[0];
    } else if ($selector[0] == '%') {
      $selector = substr($selector, 1);
      foreach ($this->children as $child) {
        if ($child instanceof :xhp && $child->categoryOf($selector)) {
          return $child;
        }
      }
    } else {
      $selector = :xhp::element2class($selector);
      foreach ($this->children as $child) {
        if ($child instanceof $selector) {
          return $child;
        }
      }
    }
    return null;
  }

  /**
   * Fetches the last direct child of the element, or the last child that
   * matches the tag or category if one is given
   *
   * @param $selector  string   tag name or category (optional)
   * @return           element  the last child node (with the given selector),
   *                            false if there are no (matching) children
   */
  final public function getLastChild(?string $selector = null): ?XHPChild {
    $temp = $this->getChildren($selector);
    return end($temp);
    }

  /**
   * Returns true if the attribute is a data- or aria- attribute.
   *
   * @param $attr      attribute to fetch
   * @return           bool
   */
  final public static function isAttributeSpecial($attr) {
    // Must be at least 6 characters, with a '-' in the 5th position
    return
      isset($attr[5])
      && $attr[4] == '-'
      && isset(self::SPECIAL_ATTRIBUTES[substr($attr, 0, 4)]);
  }

  /**
   * Fetches an attribute from this elements attribute store. If $attr is not
   * defined in the store and is not a data- or aria- attribute an exception
   * will be thrown. An exception will also be thrown if $attr is required and
   * not set.
   *
   * @param $attr      attribute to fetch
   */
  final public function getAttribute(string $attr) {
    // Return the attribute if it's there
    if (array_key_exists($attr, $this->attributes)) {
      return $this->attributes[$attr];
    }

    if (!self::isAttributeSpecial($attr)) {
      // Get the declaration
      $decl = static::__xhpAttributeDeclaration();

      if (!isset($decl[$attr])) {
        throw new XHPAttributeNotSupportedException($this, $attr);
      } else if (!empty($decl[$attr][3])) {
        throw new XHPAttributeRequiredException($this, $attr);
      } else {
        return $decl[$attr][2];
      }
    } else {
      return null;
    }
  }

  final public static function __xhpReflectionAttribute(
    string $attr
  ): ?ReflectionXHPAttribute {
    $map = static::__xhpReflectionAttributes();
	return $map[$attr] ?? null;
  }

  final public static function __xhpReflectionAttributes(
  ): array {
    static $cache = array();
    $class = static::class;
    if (!isset($cache[$class])) {
      $map = array();
      $decl = static::__xhpAttributeDeclaration();
      foreach ($decl as $name => $attr_decl) {
        $map[$name] = new ReflectionXHPAttribute($name, $attr_decl);
      }
      $cache[$class] = $map;
    }
    return $cache[$class];
  }

  final public static function __xhpReflectionChildrenDeclaration(
  ): ReflectionXHPChildrenDeclaration {
    static $cache = array();
    $class = static::class;
    if (!isset($cache[$class])) {
      $cache[$class] = new ReflectionXHPChildrenDeclaration(
        :xhp::class2element($class),
        self::emptyInstance()->__xhpChildrenDeclaration()
      );
    }
    return $cache[$class];
  }

  final public static function __xhpReflectionCategoryDeclaration(
  ): array {
    return
      array_keys(self::emptyInstance()->__xhpCategoryDeclaration());
  }

  // Work-around to call methods that should be static without a real
  // instance.
  private static function emptyInstance() {
    return (new ReflectionClass(static::class))->newInstanceWithoutConstructor();
  }

  final public function getAttributes(): array {
    return $this->attributes;
  }

  /**
   * Sets an attribute in this element's attribute store. If the attribute is
   * not defined in the store and is not a data- or aria- attribute an
   * exception will be thrown. An exception will also be thrown if the
   * attribute value is invalid.
   *
   * @param $attr      attribute to set
   * @param $val       value
   */
  final public function setAttribute(string $attr, /*mixed*/ $value)/*: this*/ {
    if (!self::isAttributeSpecial($attr)) {
      if (:xhp::isAttributeValidationEnabled()) {
        $value = $this->validateAttributeValue($attr, $value);
      }
    } else {
      $value = (string)$value;
    }
    $this->attributes[$attr] = $value;
    return $this;
  }

  /**
   * Takes an array of key/value pairs and adds each as an attribute.
   *
   * @param $attrs    array of attributes
   */
  final public function setAttributes(
    iterable/*<string, mixed>*/ $attrs
  )/*: this*/ {
    foreach ($attrs as $key => $value) {
      $this->setAttribute($key, $value);
    }
    return $this;
  }

  /**
   * Whether the attribute has been explicitly set to a non-null value by the
   * caller (vs. using the default set by "attribute" in the class definition).
   *
   * @param $attr attribute to check
   */
  final public function isAttributeSet(string $attr): bool {
    return array_key_exists($attr, $this->attributes);
  }

  /**
   * Removes an attribute from this element's attribute store. An exception
   * will be thrown if $attr is not supported.
   *
   * @param $attr      attribute to remove
   * @param $val       value
   */
  final public function removeAttribute(string $attr)/*: this*/ {
    if (!self::isAttributeSpecial($attr)) {
        $value = $this->validateAttributeValue($attr, null);
      }
    unset($this->attributes[$attr]);
    return $this;
  }

  /**
   * Sets an attribute in this element's attribute store. Always foregoes
   * validation.
   *
   * @param $attr      attribute to set
   * @param $val       value
   */
  final public function forceAttribute(string $attr, /*mixed*/ $value)/*: this*/ {
    $this->attributes[$attr] = $value;
    return $this;
  }

  /**
   * Returns all contexts currently set.
   *
   * @return array  All contexts
   */
  final public function getAllContexts(): array/*<string, mixed>*/ {
    return $this->context;
  }

  /**
   * Returns a specific context value. Can include a default if not set.
   *
   * @param string $key     The context key
   * @param mixed $default  The value to return if not set (optional)
   * @return mixed          The context value or $default
   */
  final public function getContext(string $key, /*mixed*/ $default = null)/*: mixed*/ {
    return $this->context[$key] ?? $default;
  }

  /**
   * Sets a value that will be automatically passed down through a render chain
   * and can be referenced by children and composed elements. For instance, if
   * a root element sets a context of "admin_mode" = true, then all elements
   * that are rendered as children of that root element will receive this
   * context WHEN RENDERED. The context will not be available before render.
   *
   * @param mixed $key      Either a key, or an array of key/value pairs
   * @param mixed $default  if $key is a string, the value to set
   * @return :xhp           $this
   */
  final public function setContext(string $key, /*mixed*/ $value)/*: this*/ {
    $this->context[$key] = $value;
    return $this;
  }

  /**
   * Sets a value that will be automatically passed down through a render chain
   * and can be referenced by children and composed elements. For instance, if
   * a root element sets a context of "admin_mode" = true, then all elements
   * that are rendered as children of that root element will receive this
   * context WHEN RENDERED. The context will not be available before render.
   *
   * @param array $context  A map of key/value pairs
   * @return :xhp         $this
   */
  final public function addContextMap(iterable/*<string, mixed>*/ $context)/*: this*/ {
    foreach($context as $key => $value) {
      $this->context[$key] = $value;
    }
    return $this;
  }

  /**
   * Transfers the context but will not overwrite anything. This is done only
   * for rendering because we don't want a parent's context to replace a
   * child's context if they have the same key.
   *
   * @param array $parentContext  The context to transfer
   */
  final protected function __transferContext(
    iterable/*<string, mixed>*/ $parentContext
  ): void {
    foreach ($parentContext as $key => $value) {
      if (!array_key_exists($key, $this->context)) {
        $this->context[$key] = $value;
      }
    }
  }

  final protected function __flushElementChildren() {

    // Flush all :xhp elements to x:primitive's
    $ln = count($this->children);
    for ($ii = 0; $ii < $ln; ++$ii) {
      $child = $this->children[$ii];
      if ($child instanceof :x:composable-element) {
        $child->__transferContext($this->context);
      }

      if ($child instanceof :x:element) {
        $child = $child->__flushRenderedRootElement();

        if ($child instanceof :x:frag) {
          array_splice($this->children, $ii, 1, $child->getChildren());
          $ln = count($this->children);
          --$ii;
        } else {
          $this->children[$ii] = $child;
        }
      }
    }
  }

  /**
   * Defined in elements by the `attribute` keyword. The declaration is simple.
   * There is a keyed array, with each key being an attribute. Each value is
   * an array with 4 elements. The first is the attribute type. The second is
   * meta-data about the attribute. The third is a default value (null for
   * none). And the fourth is whether or not this value is required.
   *
   * Attribute types are suggested by the TYPE_* constants.
   */
  protected static function &__xhpAttributeDeclaration(
  )/*: array*/ {
    static $decl = array();
    return $decl;
  }

  /**
   * Defined in elements by the `category` keyword. This is just a list of all
   * categories an element belongs to. Each category is a key with value 1.
   */
  protected function &__xhpCategoryDeclaration()/*: array*/ {
    static $decl = array();
    return $decl;
  }

  /**
   * Defined in elements by the `children` keyword. This returns a pattern of
   * allowed children. The return value is potentially very complicated. The
   * two simplest are 0 and 1 which mean no children and any children,
   * respectively. Otherwise you're dealing with an array which is just the
   * biggest mess you've ever seen.
   */
  protected function &__xhpChildrenDeclaration()/*: mixed*/ {
    static $decl = 1;
    return $decl;
  }

  /**
   * Throws an exception if $val is not a valid value for the attribute $attr
   * on this element.
   */
  final protected function validateAttributeValue/*<T>*/(
    string $attr,
/*T*/ $val
  )/*: mixed*/ {
    $decl = static::__xhpAttributeDeclaration();
    if (!isset($decl[$attr])) {
      throw new XHPAttributeNotSupportedException($this, $attr);
    }
    if ($val === null) {
      return null;
    }
    switch ($decl[$attr][0]) {
      case XHPAttributeType::TYPE_STRING:
        if (!is_string($val)) {
          $val = XHPAttributeCoercion::CoerceToString($this, $attr, $val);
        }
        break;

      case XHPAttributeType::TYPE_BOOL:
        if (!is_bool($val)) {
          $val = XHPAttributeCoercion::CoerceToBool($this, $attr, $val);
        }
        break;

      case XHPAttributeType::TYPE_INTEGER:
        if (!is_int($val)) {
          $val = XHPAttributeCoercion::CoerceToInt($this, $attr, $val);
        }
        break;

      case XHPAttributeType::TYPE_FLOAT:
        if (!is_float($val)) {
          $val = XHPAttributeCoercion::CoerceToFloat($this, $attr, $val);
        }
        break;

      case XHPAttributeType::TYPE_ARRAY:
        if (!is_array($val)) {
          throw new XHPInvalidAttributeException($this, 'array', $attr, $val);
        }
        break;

      case XHPAttributeType::TYPE_OBJECT:
        $class = $decl[$attr][1];
        if ($val instanceof $class) {
          break;
        }
        if (
          function_exists('enum_exists') &&
          enum_exists($class) &&
          $class::isValid($val)
        ) {
          break;
        }
        // Things that are a valid array key without any coercion
        if ($class === 'arraykey') {
          if (is_int($val) || is_string($val)) {
            break;
          }
        }
        if ($class === 'num') {
          if (is_int($val) || is_float($val)) {
            break;
          }
        }
        if (is_array($val)) {
          trigger_error(
            'Allowing array as object type '.$class.' due to dropped shape support',
            E_USER_DEPRECATED
          );
          break;
        }
        throw new XHPInvalidAttributeException($this, $class, $attr, $val);
        break;

      case XHPAttributeType::TYPE_VAR:
        break;

      case XHPAttributeType::TYPE_ENUM:
        $enum_values = $decl[$attr][1];
        if (!(is_string($val) && array_search($val, $enum_values) !== false)) {
          $enums = 'enum("' . implode('","', $enum_values) . '")';
          throw new XHPInvalidAttributeException($this, $enums, $attr, $val);
        }
        break;

      case XHPAttributeType::TYPE_UNSUPPORTED_LEGACY_CALLABLE:
        throw new XHPUnsupportedAttributeTypeException(
          $this,
          'callable',
          $attr,
          'not supported in XHP-Lib 2.0 or higher.'
        );
    }
    return $val;
  }

  /**
   * Validates that this element's children match its children descriptor, and
   * throws an exception if that's not the case.
   */
  final protected function validateChildren() {
    $decl = $this->__xhpChildrenDeclaration();
    if ($decl === XHPChildrenDeclarationType::ANY_CHILDREN) {
      return;
    }
    if ($decl === XHPChildrenDeclarationType::NO_CHILDREN) {
      if ($this->children) {
        throw new XHPInvalidChildrenException($this, 0);
      } else {
        return;
      }
    }
    $ii = 0;
    if (!$this->validateChildrenExpression($decl, $ii) ||
        $ii < count($this->children)) {
      if (isset($this->children[$ii])
          && $this->children[$ii] instanceof XHPAlwaysValidChild) {
        return;
      }
      throw new XHPInvalidChildrenException($this, $ii);
    }
  }

  private function validateChildrenExpression($decl, &$index) {
    switch ($decl[0]) {
      case XHPChildrenExpressionType::SINGLE:
        // Exactly once -- :fb-thing
        if ($this->validateChildrenRule($decl[1], $decl[2], $index)) {
          return true;
        }
        return false;

      case XHPChildrenExpressionType::ANY_NUMBER:
        // Zero or more times -- :fb-thing*
        while ($this->validateChildrenRule($decl[1], $decl[2], $index));
        return true;

      case XHPChildrenExpressionType::ZERO_OR_ONE:
        // Zero or one times -- :fb-thing?
        if ($this->validateChildrenRule($decl[1], $decl[2], $index));
        return true;

      case XHPChildrenExpressionType::ONE_OR_MORE:
        // One or more times -- :fb-thing+
        if (!$this->validateChildrenRule($decl[1], $decl[2], $index)) {
          return false;
        }
        while ($this->validateChildrenRule($decl[1], $decl[2], $index));
        return true;

      case XHPChildrenExpressionType::SUB_EXPR_SEQUENCE:
        // Specific order -- :fb-thing, :fb-other-thing
        $oindex = $index;
        if ($this->validateChildrenExpression($decl[1], $index) &&
            $this->validateChildrenExpression($decl[2], $index)) {
          return true;
        }
        $index = $oindex;
        return false;

      case XHPChildrenExpressionType::SUB_EXPR_DISJUNCTION:
        // Either or -- :fb-thing | :fb-other-thing
        if ($this->validateChildrenExpression($decl[1], $index) ||
            $this->validateChildrenExpression($decl[2], $index)) {
          return true;
        }
        return false;
    }
  }

  private function validateChildrenRule($type, $rule, &$index) {
    switch ($type) {
      case XHPChildrenConstraintType::ANY:
        if (isset($this->children[$index])) {
          ++$index;
          return true;
        }
        return false;

      case XHPChildrenConstraintType::PCDATA:
        if (isset($this->children[$index]) &&
            !($this->children[$index] instanceof :xhp)) {
          ++$index;
          return true;
        }
        return false;

      case XHPChildrenConstraintType::ELEMENT:
        if (isset($this->children[$index]) &&
            $this->children[$index] instanceof $rule) {
          ++$index;
          return true;
        }
        return false;

      case XHPChildrenConstraintType::CATEGORY:
        if (!isset($this->children[$index]) ||
            !($this->children[$index] instanceof :xhp)) {
          return false;
        }
        $category = :xhp::class2element($rule);
        $child = $this->children[$index];
        assert($child instanceof :xhp);
        $categories = $child->__xhpCategoryDeclaration();
        if (empty($categories[$category])) {
          return false;
        }
        ++$index;
        return true;

      case 5: // nested rule -- ((:fb-thing, :fb-other-thing)*, :fb:thing-footer)
        return $this->validateChildrenExpression($rule, $index);
    }
  }

  /**
   * Returns the human-readable `children` declaration as seen in this class's
   * source code.
   */
  final public function __getChildrenDeclaration() {
    $decl = $this->__xhpChildrenDeclaration();
    if ($decl === 1) {
      return 'any';
    }
    if ($decl === 0) {
      return 'empty';
    }
    return $this->renderChildrenDeclaration($decl);
  }

  private function renderChildrenDeclaration($decl) {
    switch ($decl[0]) {
      case 0:
        return $this->renderChildrenRule($decl[1], $decl[2]);

      case 1:
        return $this->renderChildrenRule($decl[1], $decl[2]) . '*';

      case 2:
        return $this->renderChildrenRule($decl[1], $decl[2]) . '?';

      case 3:
        return $this->renderChildrenRule($decl[1], $decl[2]) . '+';

      case 4:
        return $this->renderChildrenDeclaration($decl[1]) . ',' .
          $this->renderChildrenDeclaration($decl[2]);

      case 5:
        return $this->renderChildrenDeclaration($decl[1]) . '|' .
          $this->renderChildrenDeclaration($decl[2]);
    }
  }

  private function renderChildrenRule($type, $rule) {
    switch ($type) {
      case 1:
        return 'any';

      case 2:
        return 'pcdata';

      case 3:
        return ':' . :xhp::class2element($rule);

      case 4:
        return '%' . $rule;

      case 5:
        return '(' . $this->renderChildrenDeclaration($rule) . ')';
    }
  }

  /**
   * Returns a description of the current children in this element. Maybe
   * something like this:
   * <div><span>foo</span>bar</div> ->
   * :span[%inline],pcdata
   */
  final public function __getChildrenDescription(): string {
    $desc = array();
    foreach ($this->children as $child) {
      if ($child instanceof :xhp) {
        $tmp = ':'.:xhp::class2element(get_class($child));
        if ($categories = $child->__xhpCategoryDeclaration()) {
          $tmp .= '[%'.implode(',%', array_keys($categories)).']';
        }
        $desc[] = $tmp;
      } else {
        $desc[] = 'pcdata';
      }
    }
    return implode(',', $desc);
  }

  final public function categoryOf(string $c): bool {
    $categories = $this->__xhpCategoryDeclaration();
    if (isset($categories[$c])) {
      return true;
    }
    // XHP parses the category string
    $c = str_replace(array(':', '-'), array('__', '_'), $c);
    return isset($categories[$c]);
  }
}
