<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\Hydrator\DefaultCompositeHydrator;
use KleijnWeb\PhpApi\Hydrator\Tests\TestSchemaFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DefaultCompositeHydratorPerformanceTest extends TestCase
{
    /**
     * @var DefaultCompositeHydrator
     */
    private $hydrator;

    protected function setUp()
    {
        $this->hydrator = new DefaultCompositeHydrator(
            new ClassNameResolver(['KleijnWeb\PhpApi\Hydrator\Tests\Types'])
        );
    }


    /**
     * @test
     * @group perf
     */
    public function canHydrateLargeArray()
    {
        $size  = 10000;
        $input = [];

        for ($i = 0; $i < $size; ++$i) {
            $input[] = (object)[
                'id'        => (string)rand(),
                'name'      => (string)rand(),
                'status'    => (string)rand(),
                'x'         => 'y',
                'photoUrls' => [' / ' . (string)rand(), ' / ' . (string)rand()],
                'price'     => (string)rand() . '.25',
                'category'  => (object)[
                    'name' => 'Shepherd',
                ],
                'tags'      => [
                    (object)['name' => (string)rand()],
                    (object)['name' => (string)rand()],
                ],
                'rating'    => (object)[
                    'value'   => '10',
                    'created' => '2016-01-01',
                ],
            ];
        }

        $schema = new ArraySchema((object)[], TestSchemaFactory::createPetSchema());

        $start = microtime(true);
        $this->hydrator->hydrate($input, $schema);

        // Just making sure future changes don't introduce crippling performance issues .
        // This runs in under 2s on my old W3570. Travis does it in about 3.7s at the time of writing.
        $this->assertLessThan(5, microtime(true) - $start);
    }
}
