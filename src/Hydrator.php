<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
interface Hydrator
{
    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    public function hydrate($data, Schema $schema);

    /**
     * @param mixed  $data
     * @param Schema $schema
     *
     * @return mixed
     */
    public function dehydrate($data, Schema $schema);
}
