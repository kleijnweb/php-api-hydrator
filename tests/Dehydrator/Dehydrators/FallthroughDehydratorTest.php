<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Dehydrator\Dehydrators;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\FallthroughDehydrator;
use PHPUnit\Framework\TestCase;

class FallthroughDehydratorTest extends TestCase
{
    /**
     * @var FallthroughDehydrator
     */
    private $dehydrator;

    protected function setUp()
    {
        $this->dehydrator = new FallthroughDehydrator();
    }

    /**
     * @test
     */
    public function supportsScalars()
    {
        $this->assertTrue($this->dehydrator->supports(1.0, new AnySchema()));
        $this->assertTrue($this->dehydrator->supports('1.0', new AnySchema()));
        $this->assertTrue($this->dehydrator->supports('', new AnySchema()));
        $this->assertTrue($this->dehydrator->supports(1, new AnySchema()));
    }

    /**
     * @test
     */
    public function supportsCompositeValues()
    {
        $this->assertTrue($this->dehydrator->supports([], new AnySchema()));
        $this->assertTrue($this->dehydrator->supports((object)[], new AnySchema()));
    }

    /**
     * @test
     */
    public function valuesAreReturned()
    {
        $values = [
            1.0,
            '1.0',
            1,
            [],
            (object)[],
            $this,
        ];

        foreach ($values as $value) {
            $this->assertSame($value, $this->dehydrator->dehydrate($value, new AnySchema()));
        }
    }
}
