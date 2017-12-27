<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\PhpApi\Hydrator package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\PhpApi\Hydrator\Tests;

use KleijnWeb\PhpApi\Descriptions\Description\ComplexType;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ArraySchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ObjectSchema;
use KleijnWeb\PhpApi\Descriptions\Description\Schema\ScalarSchema;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class TestSchemaFactory
{
    /**
     * @return ObjectSchema
     */
    public static function createPetSchema(): ObjectSchema
    {
        $tagSchema      = self::createTagSchema();
        $categorySchema = new ObjectSchema((object)[], (object)[]);
        $categorySchema->setComplexType(new ComplexType('Category', $categorySchema));
        $petSchema = new ObjectSchema(
            (object)[],
            (object)[
                'id'       => new ScalarSchema((object)['type' => 'integer']),
                'price'    => new ScalarSchema((object)['type' => 'number', 'default' => 100.0]),
                'label'    => new ScalarSchema((object)['type' => 'string']),
                'category' => $categorySchema,
                'tags'     => new ArraySchema((object)['default' => []], $tagSchema),
                'rating'   => new ObjectSchema((object)[], (object)[
                    'value'   => new ScalarSchema((object)['type' => 'number']),
                    'created' => new ScalarSchema((object)[
                        'type'    => 'string',
                        'format'  => 'date',
                        'default' => 'now',
                    ]),
                ]),
            ]
        );
        $petSchema->setComplexType(new ComplexType('Pet', $petSchema));

        return $petSchema;
    }

    /**
     * @return ObjectSchema
     */
    public static function createTagSchema(): ObjectSchema
    {
        $tagSchema = new ObjectSchema((object)[], (object)[
            'name' => new ScalarSchema((object)['type' => 'string']),
        ]);
        $tagSchema->setComplexType(new ComplexType('Tag', $tagSchema));

        return $tagSchema;
    }
}
