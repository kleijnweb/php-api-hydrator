<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\Exception\UnsupportedException;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;

class FallthroughDehydrator extends Dehydrator
{
    /**
     * Cast a scalar value using the schema.
     *
     * @param mixed  $value
     * @param Schema $schema
     *
     * @return float|int|string|\DateTimeInterface
     * @throws UnsupportedException
     */
    public function dehydrate($value, Schema $schema)
    {
        return $value;
    }

    /**
     * @param        $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return true;
    }
}
