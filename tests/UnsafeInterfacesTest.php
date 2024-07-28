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

// Please see MIGRATING.md for information on how these should be used in
// practice; please don't create/use classes as unsafe as these examples.

class ExampleUnsafeRenderable implements XHPUnsafeRenderable {
	public $htmlString;
  public function __construct(string $htmlString) {
		$this->htmlString = $htmlString;
  }

  public function toHTMLString(): string {
    return $this->htmlString;
  }
}

class ExampleVeryUnsafeRenderable extends ExampleUnsafeRenderable
  implements XHPUnsafeRenderable, XHPAlwaysValidChild {
}

class UnsafeInterfacesTest extends PHPUnit\Framework\TestCase {
  public function testUnsafeRenderable() {
    $x = new ExampleUnsafeRenderable('<script>lollerskates</script>');
    $xhp = <div>{$x}</div>;
    $this->assertEquals(
      '<div><script>lollerskates</script></div>',
      $xhp->toString()
    );
  }

  public function testInvalidChild() {
	  $this->expectException(XHPInvalidChildrenException::class);
      $x = new ExampleUnsafeRenderable('foo');
      $xhp = <html>{$x}<body /></html>;
      $xhp->toString(); // validate, throw exception
  }

  public function testAlwaysValidChild() {
    $x = new ExampleVeryUnsafeRenderable('foo');
    $xhp = <html>{$x}<body /></html>;
    $this->assertEquals('<html>foo<body></body></html>', $xhp->toString());
  }
}
