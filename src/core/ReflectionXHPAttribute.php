<?php
final class XHPAttributeType extends XHPInternalEnumish {
  const TYPE_STRING = 1;
  const TYPE_BOOL = 2;
  const TYPE_INTEGER = 3;
  const TYPE_ARRAY = 4;
  const TYPE_OBJECT = 5;
  const TYPE_VAR = 6;
  const TYPE_ENUM = 7;
  const TYPE_FLOAT = 8;
  const TYPE_UNSUPPORTED_LEGACY_CALLABLE = 9;
}

class ReflectionXHPAttribute {
  /** @var int<1, 9> */
  private int $type;
  /*
   * OBJECT: string (class name)
   * ENUM: array<string> (enum values)
   * ARRAY: Array decl
   */
  private mixed $extraType;
  private mixed $defaultValue;
  private bool $required;

  const SPECIAL_ATTRIBUTES = [ 'data', 'aria' ];
  private string $name;

  public function __construct(string $name, array $decl) {
    $this->name = $name;
    $this->type = XHPAttributeType::assert($decl[0]);
    $this->extraType = $decl[1];
    $this->defaultValue = $decl[2];
    $this->required = (bool)$decl[3];
  }

  public function getName(): string {
    return $this->name;
  }

  /**
   * @return int<1, 9>
   */
  public function getValueType(): int {
    return $this->type;
  }

  public function isRequired(): bool {
    return $this->required;
  }

  public function hasDefaultValue(): bool {
    return $this->defaultValue !== null;
  }

  public function getDefaultValue(): mixed {
    return $this->defaultValue;
  }

  /*<<__Memoize>>*/
  public function getValueClass(): string {
    $t = $this->getValueType();
    assert(
      $this->getValueType() === XHPAttributeType::TYPE_OBJECT,
      sprintf(
        'Tried to get value class for attribute %s of type %s - needed '.'OBJECT',
        $this->getName(),
        XHPAttributeType::getNames()[$this->getValueType()],
      ),
    );
    $v = $this->extraType;
    assert(
      is_string($v),
      'Class name for attribute '.$this->getName().' is not a string',
    );
    return $v;
  }

  /**
   * @return array<string>
   */
  public function getEnumValues(): array {
    $t = $this->getValueType();
    assert(
      $this->getValueType() === XHPAttributeType::TYPE_ENUM,
      sprintf(
        'Tried to get enum values for attribute %s of type %s - needed '.'ENUM',
        $this->getName(),
        XHPAttributeType::getNames()[$this->getValueType()]
      ),
    );
    $v = $this->extraType;
    assert(
      is_array($v),
      'Class name for attribute '.$this->getName().' is not a string',
    );
    return $v;
  }

  /**
   * Returns true if the attribute is a data- or aria- attribute.
   */
  /*<<__Memoize>>*/
  public static function IsSpecial(string $attr): bool {
    return strlen($attr) >= 6 &&
      $attr[4] === '-' &&
      array_search(substr($attr, 0, 4), self::SPECIAL_ATTRIBUTES) !== false;
  }

  public function __toString(): string {
    switch ($this->getValueType()) {
      case XHPAttributeType::TYPE_STRING:
        $out = 'string';
        break;
      case XHPAttributeType::TYPE_BOOL:
        $out = 'bool';
        break;
      case XHPAttributeType::TYPE_INTEGER:
        $out = 'int';
        break;
      case XHPAttributeType::TYPE_ARRAY:
        $out = 'array';
        break;
      case XHPAttributeType::TYPE_OBJECT:
        $out = $this->getValueClass();
        break;
      case XHPAttributeType::TYPE_VAR:
        $out = 'mixed';
        break;
      case XHPAttributeType::TYPE_ENUM:
        $out = 'enum {';
        $out .=
          implode(', ',
            array_map(
              function($x) { return "'".$x."'"; },
              $this->getEnumValues()
            )
          );
        $out .= '}';
        break;
      case XHPAttributeType::TYPE_FLOAT:
        $out = 'float';
        break;
      case XHPAttributeType::TYPE_UNSUPPORTED_LEGACY_CALLABLE:
        $out = '<UNSUPPORTED: legacy callable>';
        break;
    }
    $out .= ' '.$this->getName();
    if ($this->hasDefaultValue()) {
      $out .= ' = '.var_export($this->getDefaultValue(), true);
    }
    if ($this->isRequired()) {
      $out .= ' @required';
    }
    return $out;
  }
}
