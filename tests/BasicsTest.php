<?php
/*
 *  Copyright (c) 2004-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

class :test:renders-primitive extends :x:element {
  protected function render(): XHPRoot {
    return <x:frag><div>123</div></x:frag>;
  }
}

class BasicsTest extends PHPUnit\Framework\TestCase {
  public function testDivWithString() {
    $xhp =
      <div>
        Hello, world.
      </div>;
    $this->assertEquals('<div> Hello, world. </div>', $xhp->toString());
  }

  public function testFragWithString() {
    $xhp = <x:frag>Derp</x:frag>;
    $this->assertSame('Derp', $xhp->toString());
  }

  public function testDivWithChild() {
    $xhp = <div><div>Herp</div></div>;
    $this->assertEquals('<div><div>Herp</div></div>', $xhp->toString());
  }

  public function testInterpolation() {
    $x = "Herp";
    $xhp = <div>{$x}</div>;
    $this->assertEquals('<div>Herp</div>', $xhp->toString());
  }

  public function testXFrag() {
    $x = 'herp';
    $y = 'derp';
    $frag = <x:frag>{$x}{$y}</x:frag>;
    $this->assertEquals(2, count($frag->getChildren()));
    $xhp = <div>{$frag}</div>;
    $this->assertEquals('<div>herpderp</div>', $xhp->toString());
  }

  public function testEscaping() {
    $xhp = <div>{"foo<SCRIPT>bar"}</div>;
    $this->assertEquals('<div>foo&lt;SCRIPT&gt;bar</div>', $xhp->toString());
  }

  public function testElement2Class(): void {
    $this->assertSame(:div::class, :xhp::element2class('div'));
  }

  public function testClass2Element(): void {
    $this->assertSame('div', :xhp::class2element(:div::class));
  }

  public function testRendersPrimitive(): void {
    $xhp = <test:renders-primitive />;
    $this->assertSame('<div>123</div>', $xhp->toString());
  }

  public function testJsonSerialize(): void {
    $xhp = <div>Hello world.</div>;
    $this->assertSame('["<div>Hello world.<\/div>"]', json_encode([$xhp]));
  }
}
