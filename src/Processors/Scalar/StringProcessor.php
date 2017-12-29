<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Processors\Scalar;


class StringProcessor extends ScalarProcessor
{
    /**
     * Cast a scalar value using the schema.
     *
     * @param mixed $value
     *
     * @return string
     */
    public function hydrate($value)
    {
        if ($value === null) {
            return $this->schema->getDefault();
        }

        return (string)$value;
    }
}
