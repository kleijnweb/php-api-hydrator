<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator\Hydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\SimpleObjectHydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\TestSchemaFactory;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Category;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Pet;
use KleijnWeb\PhpApi\Hydrator\Tests\Types\Tag;
use PHPUnit\Framework\TestCase;

class ComplexTypeObjectHydratorTest extends TestCase
{
    /**
     * @var SimpleObjectHydrator
     */
    private $hydrator;

    protected function setUp()
    {
        $this->hydrator = new SimpleObjectHydrator();
    }
}
