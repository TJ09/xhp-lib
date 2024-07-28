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

class :test:no-xhphelpers extends :x:element {
  use XHPBaseHTMLHelpers;
  attribute :xhp:html-element;

  protected function render(): XHPRoot {
    return <div />;
  }
}

class :test:xhphelpers extends :x:element {
  use XHPHelpers;
  attribute :xhp:html-element;

  protected function render(): XHPRoot {
    return <div>{$this->getChildren()}</div>;
  }
}

class :test:with-class-on-root extends :x:element {
  use XHPHelpers;
  attribute :xhp:html-element;

  protected function render(): XHPRoot {
    return <div class="rootClass" />;
  }
}

class XHPHelpersTest extends PHPUnit\Framework\TestCase {
  public function testTransferAttributesWithoutHelpers(): void {
    $x = <test:no-xhphelpers data-foo="bar" />;
    $this->assertSame('<div></div>', $x->toString());
    $this->assertNotEmpty($x->getID());
    $this->assertSame('<div></div>', $x->toString());
  }

  public function testTransferAttributesWithHelpers(): void {
    $x = <test:xhphelpers data-foo="bar" />;
    $this->assertSame('<div data-foo="bar"></div>', $x->toString());
    $this->assertNotEmpty($x->getID());
    $this->assertSame('<div id="'.$x->getID().'"></div>', $x->toString());
  }

  public function testAddClassWithoutHelpers(): void {
    $x = <test:no-xhphelpers class="foo" />;
    $x->addClass("bar");
    $x->conditionClass(true, "herp");
    $x->conditionClass(false, "derp");
    $this->assertSame('foo bar herp', $x->:class);
    $this->assertSame("<div></div>", $x->toString());
  }

  public function testAddClassWithHelpers(): void {
    $x = <test:xhphelpers class="foo" />;
    $x->addClass("bar");
    $x->conditionClass(true, "herp");
    $x->conditionClass(false, "derp");
    $this->assertSame('foo bar herp', $x->:class);
    $this->assertSame('<div class="foo bar herp"></div>', $x->toString());
  }

  public function testRootClassPreserved(): void {
    $x = <test:with-class-on-root />;
    $this->assertSame('<div class="rootClass"></div>', $x->toString());
  }

  public function testTransferedClassesAppended(): void {
    $x = <test:with-class-on-root class="extraClass" />;
    $this->assertSame(
      '<div class="rootClass extraClass"></div>',
      $x->toString()
    );
  }

  public function testRootClassesNotOverridenByEmptyString(): void {
    $x = <test:with-class-on-root class="" />;
    $this->assertSame('<div class="rootClass"></div>', $x->toString());
  }

  public function testNested(): void {
    $x =
      <test:xhphelpers class="herp">
        <test:xhphelpers class="derp" />
      </test:xhphelpers>;
    $this->assertSame(
      '<div class="herp"><div class="derp"></div></div>',
      $x->toString()
    );
  }
}
