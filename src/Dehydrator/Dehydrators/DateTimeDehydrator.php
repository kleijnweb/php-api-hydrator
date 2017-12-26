<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrators;

use KleijnWeb\PhpApi\Descriptions\Description\Schema\AnySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\Schema;
use KleijnWeb\PhpApi\Hydrator\DateTimeSerializer;
use KleijnWeb\PhpApi\Hydrator\Dehydrator\Dehydrator;

class DateTimeDehydrator extends Dehydrator
{
    /**
     * @var AnySchema
     */
    private $anySchema;

    /**
     * @var DateTimeSerializer
     */
    private $dateTimeSerializer;

    /**
     * DateTimeDehydrator constructor.
     * @param DateTimeSerializer $dateTimeSerializer
     */
    public function __construct(DateTimeSerializer $dateTimeSerializer)
    {
        $this->anySchema          = new AnySchema();
        $this->dateTimeSerializer = $dateTimeSerializer;
    }


    /**
     * @param mixed  $node
     * @param Schema $schema
     *
     * @return mixed
     */
    public function dehydrate($node, Schema $schema)
    {
        return $this->dehydrateDateTime($node, $schema);
    }


    /**
     * @param \DateTimeInterface $value
     * @param Schema             $schema
     * @return string
     */
    private function dehydrateDateTime(\DateTimeInterface $value, Schema $schema): string
    {
        return $this->dateTimeSerializer->serialize($value, $schema);
    }

    /**
     * @param mixed  $data
     * @param Schema $schema
     * @return bool
     */
    public function supports($data, Schema $schema): bool
    {
        return $data instanceof \DateTimeInterface;
    }
}
