<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class :test:for-reflection extends :x:element {
  attribute
    string mystring @required,
    enum {'herp', 'derp'} myenum,
    string mystringwithdefault = 'mydefault';
  children (:div+, (:code, :a)?);
  category %herp, %derp;

  public function render(): XHPRoot {
    return <div />;
  }
}

class ReflectionTest extends PHPUnit_Framework_TestCase {
  private /*?ReflectionXHPClass*/ $rxc;

  public function setUp(): void {
    $this->rxc = new ReflectionXHPClass(:test:for-reflection::class);
  }

  public function testClassName(): void {
    $this->assertSame(:test:for-reflection::class, nullsafe($this->rxc)->getClassName());
  }

  public function testElementName(): void {
    $this->assertSame('test:for-reflection', nullsafe($this->rxc)->getElementName());
  }

  public function testReflectionClass(): void {
    $rc = nullsafe($this->rxc)->getReflectionClass();
    $this->assertInstanceOf(ReflectionClass::class, $rc);
    $this->assertSame(:test:for-reflection::class, nullsafe($rc)->getName());
  }

  public function testGetChildren(): void {
    $children = nullsafe($this->rxc)->getChildren();
    $this->assertInstanceOf(ReflectionXHPChildrenDeclaration::class, $children);
    $this->assertSame('(:div+,(:code,:a)?)', (string)$children);
  }

  public function testGetAttributes(): void {
    $attrs = nullsafe($this->rxc)->getAttributes();
    $this->assertNotEmpty($attrs);
    $this->assertEquals(
      [
        'mystring' => 'string mystring @required',
        'myenum' => "enum {'herp', 'derp'} myenum",
        'mystringwithdefault' => "string mystringwithdefault = 'mydefault'",
      ],
      array_map(
        function($attr) { return (string)$attr; },
        $attrs ?? []
      )
    );
  }

  public function testGetCategories(): void {
    $categories = nullsafe($this->rxc)->getCategories();
    $this->assertEquals([ 'herp', 'derp' ], $categories);
  }
}
