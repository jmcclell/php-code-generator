<?php
namespace gossi\codegen\tests\model;

// use gossi\codegen\tests\model\fixture\Entity;
use gossi\docblock\Docblock;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpProperty;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\model\PhpConstant;
use gossi\codegen\model\PhpInterface;
use gossi\codegen\model\PhpTrait;

class PhpClassTest extends \PHPUnit_Framework_TestCase {

	public function setUp() {
		// they are not explicitely instantiated through new WhatEver(); and such not
		// required through composer's autoload
		require_once __DIR__ . '/../fixture/Entity.php';
		require_once __DIR__ . '/../fixture/ClassWithTraits.php';
		require_once __DIR__ . '/../fixture/ClassWithConstants.php';
	}

	public function testFromReflection() {
		$classDoc = new Docblock('/**
 * Doc Comment.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */');
		$propDoc = new Docblock('/**
 * @var integer
 */');
		$class = new PhpClass();
		$class->setQualifiedName('gossi\codegen\tests\fixture\Entity')
			->setAbstract(true)
			->setDocblock($classDoc)
			->setDescription($classDoc->getShortDescription())
			->setLongDescription($classDoc->getLongDescription())
			->setProperty(PhpProperty::create('id')
				->setVisibility('private')
				->setDocblock($propDoc)
				->setDescription($propDoc->getShortDescription()))
			->setProperty(PhpProperty::create('enabled')
				->setVisibility('private')
				->setDefaultValue(false));
		
		$methodDoc = new Docblock('/**
 * Another doc comment.
 *
 * @param unknown_type $a
 * @param array        $b
 * @param \stdClass    $c
 * @param string       $d
 * @param callable     $e
 */');
		$method = PhpMethod::create('__construct')
			->setFinal(true)
			->addParameter(new PhpParameter('a'))
			->addParameter(PhpParameter::create()
				->setName('b')
				->setType('array')
				->setPassedByReference(true))
			->addParameter(PhpParameter::create()
				->setName('c')
				->setType('stdClass'))
			->addParameter(PhpParameter::create()
				->setName('d')
				->setDefaultValue('foo'))
			->addParameter(PhpParameter::create()
				->setName('e')
				->setType('callable'))
			->setDocblock($methodDoc)
			->setDescription($methodDoc->getShortDescription())
			->setLongDescription($methodDoc->getLongDescription());

		$class->setMethod($method);
		$class->setMethod(PhpMethod::create('foo')->setAbstract(true)->setVisibility('protected'));
		$class->setMethod(PhpMethod::create('bar')->setStatic(true)->setVisibility('private'));
		
		$this->assertEquals($class, PhpClass::fromReflection(new \ReflectionClass('gossi\codegen\tests\fixture\Entity')));
		
		$class = new PhpClass('gossi\codegen\tests\fixture\ClassWithConstants');
		$class->setConstant('FOO', 'bar');
		$this->assertEquals($class, PhpClass::fromReflection(new \ReflectionClass('gossi\codegen\tests\fixture\ClassWithConstants')));
	}

	public function testConstants() {
		$class = new PhpClass();
		
		$this->assertEquals(array(), $class->getConstants());
		$this->assertSame($class, $class->setConstants(array(
			'foo' => 'bar'
		)));
		$this->assertEquals(array(
			'foo' => 'bar'
		), $class->getConstants());
		$this->assertSame($class, $class->setConstant('bar', 'baz'));
		$this->assertEquals(array(
			'foo' => 'bar',
			'bar' => 'baz'
		), $class->getConstants());
		$this->assertSame($class, $class->removeConstant('foo'));
		$this->assertEquals(array(
			'bar' => 'baz'
		), $class->getConstants());
		$this->assertSame($class, $class->setConstant($bim = new PhpConstant('bim', 'bam')));
		$this->assertTrue($class->hasConstant('bim'));
		$this->assertSame($bim, $class->getConstant('bim'));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRemoveConstantThrowsExceptionWhenConstantDoesNotExist() {
		$class = new PhpClass();
		$class->removeConstant('foo');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testGetConstantThrowsExceptionWhenConstantDoesNotExist() {
		$class = new PhpClass();
		$class->getConstant('foo');
	}

	public function testAbstract() {
		$class = new PhpClass();
		
		$this->assertFalse($class->isAbstract());
		$this->assertSame($class, $class->setAbstract(true));
		$this->assertTrue($class->isAbstract());
		$this->assertSame($class, $class->setAbstract(false));
		$this->assertFalse($class->isAbstract());
	}

	public function testFinal() {
		$class = new PhpClass();
		
		$this->assertFalse($class->isFinal());
		$this->assertSame($class, $class->setFinal(true));
		$this->assertTrue($class->isFinal());
		$this->assertSame($class, $class->setFinal(false));
		$this->assertFalse($class->isFinal());
	}

	public function testParentClassName() {
		$class = new PhpClass();
		
		$this->assertNull($class->getParentClassName());
		$this->assertSame($class, $class->setParentClassName('stdClass'));
		$this->assertEquals('stdClass', $class->getParentClassName());
		$this->assertSame($class, $class->setParentClassName(null));
		$this->assertNull($class->getParentClassName());
	}

	public function testInterfaces() {
		$class = new PhpClass('my\name\space\Class');
		
		$this->assertEquals(array(), $class->getInterfaces());
		$this->assertSame($class, $class->setInterfaces(array(
			'foo',
			'bar'
		)));
		$this->assertEquals(array(
			'foo',
			'bar'
		), $class->getInterfaces());
		$this->assertSame($class, $class->addInterface('stdClass'));
		$this->assertEquals(array(
			'foo',
			'bar',
			'stdClass'
		), $class->getInterfaces());
		
		$interface = new PhpInterface('my\name\space\Interface');
		$class->addInterface($interface);
		$this->assertTrue($class->hasInterface('my\name\space\Interface'));
		$this->assertSame($class, $class->removeInterface($interface));
		
		$class->addInterface(new PhpInterface('other\name\space\Interface'));
		$this->assertTrue($class->hasUseStatement('other\name\space\Interface'));
		$this->assertSame($class, $class->removeInterface('other\name\space\Interface'));
		$this->assertTrue($class->hasUseStatement('other\name\space\Interface'));
	}

	public function testTraits() {
		$class = new PhpClass('my\name\space\Class');
		
		$this->assertEquals(array(), $class->getTraits());
		$this->assertSame($class, $class->setTraits(array(
			'foo',
			'bar'
		)));
		$this->assertEquals(array(
			'foo',
			'bar'
		), $class->getTraits());
		$this->assertSame($class, $class->addTrait('stdClass'));
		$this->assertEquals(array(
			'foo',
			'bar',
			'stdClass'
		), $class->getTraits());
		
		$trait = new PhpTrait('my\name\space\Trait');
		$class->addTrait($trait);
		$this->assertTrue($class->hasTrait('my\name\space\Trait'));
		$this->assertSame($class, $class->removeTrait($trait));
		
		$class->addTrait(new PhpTrait('other\name\space\Trait'));
		$this->assertTrue($class->hasUseStatement('other\name\space\Trait'));
		$this->assertSame($class, $class->removeTrait('other\name\space\Trait'));
		$this->assertTrue($class->hasUseStatement('other\name\space\Trait'));
	}

	public function testProperties() {
		$class = new PhpClass();
		
		$this->assertEquals(array(), $class->getProperties());
		$this->assertSame($class, $class->setProperty($prop = new PhpProperty('foo')));
		$this->assertSame(array(
			'foo' => $prop
		), $class->getProperties());
		$this->assertTrue($class->hasProperty('foo'));
		$this->assertSame($class, $class->removeProperty('foo'));
		$this->assertEquals(array(), $class->getProperties());
		
		$prop = new PhpProperty('bam');
		$class->setProperty($prop);
		$this->assertTrue($class->hasProperty($prop));
		$this->assertSame($class, $class->removeProperty($prop));
		
		$class->setProperty($orphaned = new PhpProperty('orphaned'));
		$this->assertSame($class, $orphaned->getParent());
		$this->assertSame($orphaned, $class->getProperty('orphaned'));
		$this->assertSame($orphaned, $class->getProperty($orphaned));
		$this->assertEmpty($class->getProperty('prop-not-found'));
		$this->assertTrue($class->hasProperty($orphaned));
		$this->assertSame($class, $class->setProperties([
			$prop,
			$prop2 = new PhpProperty('bar')
		]));
		$this->assertSame([
			'bam' => $prop,
			'bar' => $prop2
		], $class->getProperties());
		$this->assertNull($orphaned->getParent());
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRemoveNonExistentProperty() {
		$class = new PhpClass();
		$class->removeProperty('haha');
	}

	public function testLongDescription() {
		$class = new PhpClass();
		
		$this->assertSame($class, $class->setLongDescription('very long description'));
		$this->assertEquals('very long description', $class->getLongDescription());
	}
	
	public function testClassWithTraitsFromReflection() {
		$class = PhpClass::fromReflection(new \ReflectionClass('gossi\codegen\tests\fixture\ClassWithTraits'));
		
		$this->assertTrue($class->hasTrait('DummyTrait'));
	}
	
	public function testUseStatements() {
		$class = new PhpClass();
		$class->addUseStatement('Symfony\\Component\\Console\\Application', 'Console');
		
		$this->assertEquals(['Console' => 'Symfony\\Component\\Console\\Application'], $class->getUseStatements());
		$this->assertEquals('Console', $class->getUseAlias('Symfony\\Component\\Console\\Application'));
		
		$class->addUseStatement('Logger');
		$this->assertEquals([
			'Console' => 'Symfony\\Component\\Console\\Application',
			'Logger' => 'Logger'
		], $class->getUseStatements());
		$this->assertEquals('Logger', $class->getUseAlias('Logger'));
	}
	
	public function testDescripion() {
		$class = new PhpClass();
		$class->setDescription(['multiline', 'description']);
		$this->assertEquals("multiline\ndescription", $class->getDescription());
	}
	
}
