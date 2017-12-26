<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator;

use KleijnWeb\PhpApi\Hydrator\Dehydrator\ReflectingObjectIterator;
use PHPUnit\Framework\TestCase;


class ReflectingObjectIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function canMoveOutOfBounds()
    {
        $iterator = new ReflectingObjectIterator($this);

        for ($i = count(get_object_vars($this)); $i > 0; --$i) {
            $iterator->next();
        }

        try {
            $iterator->current();
        } catch (\OutOfBoundsException $e) {
            try {
                $iterator->key();
            } catch (\OutOfBoundsException $e) {
                $this->assertTrue(true);
                return;
            }
        }

        $this->fail();
    }
}
