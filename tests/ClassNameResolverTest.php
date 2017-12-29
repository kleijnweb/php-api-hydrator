<?php declare(strict_types=1);
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
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 *
 * @runInSeparateProcess Makes sure the class has to be autoloaded
 */
class ClassNameResolverTest extends TestCase
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
    public function willUseCache()
    {
        $resolver = new ClassNameResolver([__NAMESPACE__ . '\\Types']);

        $start = microtime(true);
        $this->assertSame(Pet::class, $resolver->resolve('Pet'));
        $first = microtime(true) - $start;

        $start = microtime(true);
        $this->assertSame(Pet::class, $resolver->resolve('Pet'));
        $this->assertSame(Pet::class, $resolver->resolve('Pet'));
        $this->assertSame(Pet::class, $resolver->resolve('Pet'));
        $this->assertSame(Pet::class, $resolver->resolve('Pet'));

        // 4 repeats divided by 3 should still always be faster than the first run
        $this->assertLessThan($first, (microtime(true) - $start) / 3);
    }

    /**
     * @test
     */
    public function willThrowExceptionWhenClassNameIsNotResolvable()
    {
        $resolver = new ClassNameResolver([__NAMESPACE__ . '\\Types']);

        $this->expectException(ClassNotFoundException::class);
        $resolver->resolve('ProjectX');
    }
}
