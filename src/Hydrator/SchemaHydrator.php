<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;

interface SchemaHydrator
{
    public function hydrate($data, Schema $schema);
}
