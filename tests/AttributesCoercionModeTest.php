<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

// Using decl because this test intentional passes the wrong types for
// attributes

class :test:attribute-coercion-modes extends :x:element {
  attribute
    int myint,
    float myfloat,
    string mystring,
    bool mybool;

  protected function render(): XHPRoot {
    return <div />;
  }
}

class AttributesCoercionModeTest extends PHPUnit\Framework\TestCase {
  private /*?XHPAttributeCoercionMode*/ $coercionMode;
  private /*mixed*/ $errorReporting;

  public function setUp(): void {
    $this->coercionMode = XHPAttributeCoercion::GetMode();
    $this->errorReporting = error_reporting();
    :xhp::enableAttributeValidation();
  }

  public function tearDown(): void {
    $mode = $this->coercionMode;
    assert($mode !== null, 'did not save coercion mode');
    XHPAttributeCoercion::SetMode($mode);
    error_reporting($this->errorReporting);
    :xhp::disableAttributeValidation();
  }

  public function testNoCoercion(): void {
    XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
    $x =
      <test:attribute-coercion-modes
        myint={3}
        myfloat={1.23}
        mystring="foo"
        mybool={true}
      />;
    $this->assertSame(3, $x->:myint);
    $this->assertSame(1.23, $x->:myfloat);
    $this->assertSame('foo', $x->:mystring);
    $this->assertSame(true, $x->:mybool);
  }

  public function testIntishStringAsInt(): void {
      $this->expectException(XHPInvalidAttributeException::class);
      XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
      $x = <test:attribute-coercion-modes myint="1" />;
  }

  public function testFloatAsInt(): void {
      $this->expectException(XHPInvalidAttributeException::class);
      XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
      $x = <test:attribute-coercion-modes myint={1.23} />;
  }

  public function testIntAsFloat(): void {
      $this->expectException(XHPInvalidAttributeException::class);
      XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
      $x = <test:attribute-coercion-modes myfloat={2} />;
  }

  public function testIntAsString(): void {
      $this->expectException(XHPInvalidAttributeException::class);
      XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
      $x = <test:attribute-coercion-modes mystring={2} />;
  }

  public function testIntAsBool(): void {
      $this->expectException(XHPInvalidAttributeException::class);
      XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
      $x = <test:attribute-coercion-modes mybool={1} />;
  }

  public function testStringAsBool(): void {
      $this->expectException(XHPInvalidAttributeException::class);
      XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::THROW_EXCEPTION);
      $x = <test:attribute-coercion-modes mybool="true" />;
  }

  public function testSilentCoercion(): void {
    error_reporting(E_ALL);
    XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::SILENT);
    $x = <test:attribute-coercion-modes mystring={2} />;
    $this->assertSame('2', $x->:mystring);
  }

  public function testLoggingDeprecationCoercion(): void {
    error_reporting(E_ALL);
    $exception = null;
    XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::LOG_DEPRECATION);
    try {
      $x = <test:attribute-coercion-modes mystring={2} />;
    } catch (Exception $e) {
      $exception = $e;
    }
    $this->assertInstanceOf(PHPUnit\Framework\Error\Deprecated::class, $exception);

    error_reporting(E_ALL & ~E_USER_DEPRECATED);
    $x = <test:attribute-coercion-modes mystring={2} />;
    $this->assertSame('2', $x->:mystring);
  }
}
