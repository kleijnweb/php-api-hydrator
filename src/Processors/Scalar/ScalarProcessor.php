<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Processors\Scalar;


use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;
use KleijnWeb\PhpApi\Hydrator\Processors\Processor;

abstract class ScalarProcessor extends Processor
{
    public function __construct(ScalarSchema $schema)
    {
        parent::__construct($schema);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function dehydrate($value)
    {
        return $value;
    }
}
