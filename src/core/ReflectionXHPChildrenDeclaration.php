<?php
final class XHPChildrenDeclarationType extends XHPInternalEnumish {
  const NO_CHILDREN = 0;
  const ANY_CHILDREN = 1;
  const EXPRESSION = -1;
}
final class XHPChildrenExpressionType extends XHPInternalEnumish {
  const SINGLE = 0; // :thing
  const ANY_NUMBER = 1; // :thing*
  const ZERO_OR_ONE = 2; // :thing?
  const ONE_OR_MORE = 3; // :thing+
  const SUB_EXPR_SEQUENCE = 4; // (expr, expr)
  const SUB_EXPR_DISJUNCTION = 5; // (expr | expr)
}
final class XHPChildrenConstraintType extends XHPInternalEnumish {
  const ANY = 1;
  const PCDATA = 2;
  const ELEMENT = 3;
  const CATEGORY = 4;
  const SUB_EXPR = 5;
}

class ReflectionXHPChildrenDeclaration {
  private $context;
  private $data;

  public function __construct(string $context, mixed $data) {
    $this->context = $context;
    $this->data = $data;
  }

  /**
   * @return 0|1|-1
   */
  public function getType(): int {
    if (is_array($this->data)) {
      return XHPChildrenDeclarationType::EXPRESSION;
    }
    return XHPChildrenDeclarationType::assert($this->data);
  }

  public function getExpression(): ReflectionXHPChildrenExpression {
    $data = $this->data;
    assert(
      is_array($data),
      sprintf(
        "Tried to get child expression for XHP class %s, but it does not ".
        "have an expression.",
        :xhp::class2element($this->context)
      ),
    );
    return new ReflectionXHPChildrenExpression($this->context, $data);
  }

  public function __toString(): string {
    if ($this->getType() === XHPChildrenDeclarationType::ANY_CHILDREN) {
      return 'any';
    }
    if ($this->getType() === XHPChildrenDeclarationType::NO_CHILDREN) {
      return 'empty';
    }
    return (string)$this->getExpression();
  }
}

class ReflectionXHPChildrenExpression {
  private $context;
  private $data;
  public function __construct(
    string $context,
    array $data
  ) {
    $this->context = $context;
    $this->data = $data;
  }

  /**
   * @return 0|1|2|3|4|5
   */
  public function getType(): int {
    return XHPChildrenExpressionType::assert($this->data[0]);
  }

  /**
   * @return array{0: ReflectionXHPChildrenExpression, 1: ReflectionXHPChildrenExpression}
   */
  public function getSubExpressions(
  ): array {
    $type = $this->getType();
    assert(
      $type === XHPChildrenExpressionType::SUB_EXPR_SEQUENCE ||
      $type === XHPChildrenExpressionType::SUB_EXPR_DISJUNCTION,
      'Only disjunctions and sequences have two sub-expressions - in '.
      :xhp::class2element($this->context),
    );
    $sub_expr_1 = $this->data[1];
    $sub_expr_2 = $this->data[2];
    assert(
      is_array($sub_expr_1) && is_array($sub_expr_2),
      'Data is not subexpressions - in '.
      $this->context
    );
    return array(
      new ReflectionXHPChildrenExpression($this->context, $sub_expr_1),
      new ReflectionXHPChildrenExpression($this->context, $sub_expr_2),
    );
  }

  /**
   * @return 1|2|3|4|5
   */
  public function getConstraintType(): int {
    $type = $this->getType();
    assert(
      $type !== XHPChildrenExpressionType::SUB_EXPR_SEQUENCE &&
      $type !== XHPChildrenExpressionType::SUB_EXPR_DISJUNCTION,
      'Disjunctions and sequences do not have a constraint type - in '.
      :xhp::class2element($this->context)
    );
    return XHPChildrenConstraintType::assert($this->data[1]);
  }

  public function getConstraintString(): string {
    $type = $this->getConstraintType();
    assert(
      $type === XHPChildrenConstraintType::ELEMENT ||
      $type === XHPChildrenConstraintType::CATEGORY,
      'Only element and category constraints have string data - in '.
      :xhp::class2element($this->context)
    );
    $data = $this->data[2];
    assert(is_string($data), 'Expected string data');
    return $data;
  }

  /*<<__Memoize>>*/
  public function getSubExpression(): ReflectionXHPChildrenExpression {
    assert(
      $this->getConstraintType() === XHPChildrenConstraintType::SUB_EXPR,
      'Only expression constraints have a single sub-expression - in '.
      $this->context
    );
    $data = $this->data[2];
    assert(
      is_array($data),
      sprintf(
        'Expected a sub-expression, got a %s - in %s',
        is_object($data) ? get_class($data) : gettype($data),
        $this->context,
      ),
    );
    return new ReflectionXHPChildrenExpression($this->context, $data);
  }

  public function __toString(): string {
    switch ($this->getType()) {
      case XHPChildrenExpressionType::SINGLE:
        return $this->__constraintToString();

      case XHPChildrenExpressionType::ANY_NUMBER:
        return $this->__constraintToString().'*';

      case XHPChildrenExpressionType::ZERO_OR_ONE:
        return $this->__constraintToString().'?';

      case XHPChildrenExpressionType::ONE_OR_MORE:
        return $this->__constraintToString().'+';

      case XHPChildrenExpressionType::SUB_EXPR_SEQUENCE:
        list($e1, $e2) = $this->getSubExpressions();
        return $e1.','.$e2;

      case XHPChildrenExpressionType::SUB_EXPR_DISJUNCTION:
        list($e1, $e2) = $this->getSubExpressions();
        return $e1.'|'.$e2;
    }
  }

  private function __constraintToString(): string {
    switch ($this->getConstraintType()) {
      case XHPChildrenConstraintType::ANY:
        return 'any';

      case XHPChildrenConstraintType::PCDATA:
        return 'pcdata';

      case XHPChildrenConstraintType::ELEMENT:
        return ':'.:xhp::class2element($this->getConstraintString());

      case XHPChildrenConstraintType::CATEGORY:
        return '%'.$this->getConstraintString();

      case XHPChildrenConstraintType::SUB_EXPR:
        return '('.$this->getSubExpression().')';
    }
  }
}
