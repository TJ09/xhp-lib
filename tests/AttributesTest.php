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

class :test:attribute-types extends :x:element {
  attribute
    string mystring,
    bool mybool,
    int myint,
    array myarray,
    stdClass myobject,
    enum {'foo', 'bar'} myenum,
    float myfloat,
    arraykey myarraykey,
    num mynum;

  protected function render(): XHPRoot {
    return <div />;
  }
}

class :test:required-attributes extends :x:element {
  attribute string mystring @required;

  protected function render(): XHPRoot {
    return <div>{$this->:mystring}</div>;
  }
}

class :test:default-attributes extends :x:element {
  attribute string mystring = 'mydefault';

  protected function render(): XHPRoot {
    return <div>{$this->:mystring}</div>;
  }
}

class :test:callable-attribute extends :x:element {
  attribute
    callable foo; // unsupported in 2.0+
  protected function render(): XHPRoot {
    $x = $this->getAttribute('foo');
    return <div />;
  }
}

class EmptyTestClass {
}
class StringableTestClass {
  public function __toString(): string {
    return __CLASS__;
  }
}

class AttributesTest extends PHPUnit\Framework\TestCase {
  public function setUp(): void {
    XHPAttributeCoercion::SetMode(XHPAttributeCoercionMode::SILENT);
    :xhp::enableAttributeValidation();
  }

  public function tearDown(): void {
    :xhp::disableAttributeValidation();
  }

  public function testValidTypes(): void {
    $x =
      <test:attribute-types
        mystring="foo"
        mybool={true}
        myint={123}
        myarray={[1, 2, 3]}
        myobject={new stdClass()}
        myenum={'foo'}
        myfloat={1.23}
      />;
    $this->assertEquals('<div></div>', $x->toString());
  }

  public function testInvalidArrayKeys(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myarraykey={1.23} />;
      $x->toString();
  }

  public function testValidNum(): void {
    $x = <test:attribute-types mynum={123} />;
    $this->assertSame('<div></div>', $x->toString());
    $x = <test:attribute-types mynum={1.23} />;
    $this->assertSame('<div></div>', $x->toString());
  }

  public function testInvalidNum(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types mynum="123" />;
      $x->toString();
  }

  public function testNoAttributes(): void {
    $this->assertEquals('<div></div>', <test:attribute-types />);
  }

  public function testStringableObjectAsString(): void {
    $x = <test:attribute-types mystring={new StringableTestClass()} />;
    $this->assertSame('StringableTestClass', $x->:mystring);
  }

  public function testIntegerAsString(): void {
    $x = <test:attribute-types mystring={123} />;
    $this->assertSame('123', $x->:mystring);
  }

  public function testUnstringableObjectAsString(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types mystring={new EmptyTestClass()} />;
  }

  public function testArrayAsString(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types mystring={[]} />;
  }

  public function testIntishStringAsInt(): void {
    $x = <test:attribute-types myint={'123'} />;
    $this->assertSame(123, $x->:myint);
  }

  public function testFloatAsInt(): void {
    $x = <test:attribute-types myint={1.23} />;
    $this->assertSame(1, $x->:myint);
  }

  public function testFloatishStringAsInt(): void {
    $x = <test:attribute-types myint="1.23" />;
    $this->assertSame(1, $x->:myint);
  }

  public function testObjectAsInt(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myint={new EmptyTestClass()} />;
  }

  public function testIncompleteObjectAsInt(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myint={new __PHP_Incomplete_Class()} />;
  }

  public function testArrayAsInt(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myint={[]} />;
  }

  public function testNumericPrefixStringAsInt(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myint="123derp" />;
  }

  public function testTrueStringAsBool(): void {
    $x = <test:attribute-types mybool="true" />;
    $this->assertSame(true, $x->:mybool);
  }

  public function testFalseStringAsBool(): void {
    $x = <test:attribute-types mybool="false" />;
    $this->assertSame(false, $x->:mybool);
  }

  public function testMixedCaseFalseStringAsBool(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types mybool="False" />;
    // 'False' is actually truthy
  }

  public function testNoStringAsBool(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types mybool="No" />;
    // 'No' is actually truthy
  }

  public function testAttrNameAsBool(): void {
    $x = <test:attribute-types mybool="mybool" />;
    $this->assertSame(true, $x->:mybool);
  }

  public function testInvalidEnumValue(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myenum="derp" />;
  }

  public function testIntAsFloat(): void {
    $x = <test:attribute-types myfloat={123} />;
    $this->assertSame(123.0, $x->:myfloat);
  }

  public function testNumericStringsAsFloats(): void {
    $x = <test:attribute-types myfloat="123" />;
    $this->assertSame(123.0, $x->:myfloat);
    $x = <test:attribute-types myfloat="1.23" />;
    $this->assertSame(1.23, $x->:myfloat);
  }

  public function testNonNumericStringAsFloat(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myfloat="herpderp" />;
  }

  public function testNumericPrefixStringAsFloat(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myfloat="123derp" />;
  }

  public function testNotAContainerAsArray(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myarray={new EmptyTestClass()} />;
  }

  public function testIncompatibleObjectAsObject(): void {
    $this->expectException(XHPInvalidAttributeException::class);
    $x = <test:attribute-types myobject={new EmptyTestClass()} />;
  }

  public function testProvidingRequiredAttributes(): void {
    $x = <test:required-attributes mystring="herp" />;
    $this->assertSame('herp', $x->:mystring);
    $this->assertSame('<div>herp</div>', $x->toString());
  }

  public function testOmittingRequiredAttributes(): void {
    $this->expectException(XHPAttributeRequiredException::class);
    $x = <test:required-attributes />;
    $this->assertNull($x->:mystring);
  }

  public function testProvidingDefaultAttributes(): void {
    $x = <test:default-attributes mystring="herp" />;
    $this->assertSame('herp', $x->:mystring);
    $this->assertSame('<div>herp</div>', $x->toString());
  }

  public function testOmittingDefaultAttributes(): void {
    $x = <test:default-attributes />;
    $this->assertSame('mydefault', $x->:mystring);
    $this->assertSame('<div>mydefault</div>', $x->toString());
  }

  public function testBogusAttributes(): void {
    $this->expectException(XHPAttributeNotSupportedException::class);
    $x = <test:default-attributes idonotexist="derp" />;
  }

  public function testSpecialAttributes(): void {
    $x = <test:default-attributes data-idonotexist="derp" />;
    $this->assertSame('<div>mydefault</div>', $x->toString());
    $x = <test:default-attributes aria-idonotexist="derp" />;
    $this->assertSame('<div>mydefault</div>', $x->toString());
  }

  public function testRenderCallableAttribute(): void {
    $this->expectException(XHPUnsupportedAttributeTypeException::class);
      $x =
      <test:callable-attribute
        foo={function() {
        }}
      />;
  }

  public function testReflectOnCallableAttribute(): void {
    $rxhp = new ReflectionXHPClass(:test:callable-attribute::class);
    $rattr = $rxhp->getAttribute('foo');
    $this->assertTrue(
      strstr((string)$rattr, "<UNSUPPORTED: legacy callable>") !== false,
        "Incorrect reflection for unsupported `callable` attribute type"
      );
  }
}
