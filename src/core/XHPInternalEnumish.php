<?php

/**
 * Class implementing an API similar to HHVM's enums.
 *
 * Can eventually be replaced with native PHP enums.
 */
abstract class XHPInternalEnumish {
  final public static function getValues(): array {
    return (new \ReflectionClass(static::class))->getConstants();
  }

  final public static function getNames(): array {
    return array_flip(static::getValues());
  }

  final public static function isValid($value): bool {
    return isset(static::getNames()[$value]);
  }

  final public static function coerce($value) {
    return static::isValid($value)
      // Re-map it so that it gets coerced from e.g. stringish-int to int.
      ? static::getValues()[static::getNames()[$value]]
      : null;
  }

  final public static function assert($value) {
    $new_value = static::coerce($value);
    if ($new_value === null) {
      throw new \UnexpectedValueException(
        "{$value} is not a valid value for ".get_called_class()
      );
    }
    return $new_value;
  }

}
