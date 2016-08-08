<?php declare(strict_types = 1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\Exception\ClassNotFoundException;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ClassNameResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function canResolveExistingClass()
    {
        $resolver = new ClassNameResolver([__NAMESPACE__ . '\\Types']);
        $this->assertSame(Pet::class, $resolver->resolve('Pet'));
    }

    /**
     * @test
     */
    public function willThrowExceptionWhenClassNameIsNotResolvable()
    {
        $resolver = new ClassNameResolver([__NAMESPACE__ . '\\Types']);

        $this->setExpectedException(ClassNotFoundException::class);
        $resolver->resolve('ProjectX');
    }
}
