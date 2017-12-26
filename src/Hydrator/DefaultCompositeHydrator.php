<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\ClassNameResolver;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ArrayHydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ComplexTypeObjectHydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\CompositeHydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\ScalarHydrator;
use KleijnWeb\PhpApi\Hydrator\Hydrator\Hydrators\SimpleObjectHydrator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DefaultCompositeHydrator extends CompositeHydrator
{
    /**
     * SchemaNodeHydrator constructor.
     * @param ClassNameResolver       $classNameResolver
     * @param DateTimeSerializer|null $dateTimeSerializer
     * @param bool                    $force32Bit
     */
    public function __construct(
        ClassNameResolver $classNameResolver,
        DateTimeSerializer $dateTimeSerializer = null,
        $force32Bit = false
    ) {
        parent::__construct(
            [
                new ScalarHydrator(
                    $dateTimeSerializer ?: new DateTimeSerializer(),
                    $force32Bit
                ),
                new ComplexTypeObjectHydrator($classNameResolver),
                new SimpleObjectHydrator(),
                new ArrayHydrator(),
            ]
        );
    }

    public function supports($data, Schema $schema): bool
    {
        return true;
    }
}
