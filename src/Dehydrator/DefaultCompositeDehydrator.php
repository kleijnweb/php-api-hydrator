<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\ArrayDehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\CompositeDehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\DateTimeDehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\ObjectDehydrator;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators\ScalarDehydrator;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class DefaultCompositeDehydrator extends CompositeDehydrator
{
    /**
     * SchemaNodeDehydrator constructor.
     *
     * @param DateTimeSerializer $dateTimeSerializer
     */
    public function __construct(DateTimeSerializer $dateTimeSerializer = null)
    {
        parent::__construct(
            [
                new ScalarDehydrator(),
                new DateTimeDehydrator($dateTimeSerializer),
                new ObjectDehydrator(),
                new ArrayDehydrator(),
            ]
        );
    }

    public function supports($data, Schema $schema): bool
    {
        return true;
    }
}
