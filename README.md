# KleijnWeb\PhpApi\Hydrator 
[![Build Status](https://travis-ci.org/kleijnweb/php-api-hydrator.svg?branch=master)](https://travis-ci.org/kleijnweb/php-api-hydrator)
[![Coverage Status](https://coveralls.io/repos/github/kleijnweb/php-api-hydrator/badge.svg?branch=master)](https://coveralls.io/github/kleijnweb/php-api-hydrator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kleijnweb/php-api-hydrator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kleijnweb/php-api-hydrator/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/kleijnweb/php-api-hydrator/v/stable)](https://packagist.org/packages/kleijnweb/php-api-hydrator)

A small PHP7 library for hydrating objects using api descriptions. Hydrating in this context refers to creating fully initialized typed objects from input consisting of arrays and instances of `stdClass`.
 
Works with [OpenAPI 2.0](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md) (FKA _Swagger_), [RAML 1.0](https://github.com/raml-org/raml-spec/blob/master/versions/raml-10/raml-10.md/) support is in development.

Relies on functionality provided by [KleijnWeb\PhpApi\Descriptions](https://github.com/kleijnweb/php-api-descriptions).

# Usage 

```php
$schema = $description->getPath('/foo')->getOperation('post')->getRequestSchema();
$hydratorBuilder = new HydratorBuilder(new ClassNameResolver(['A\\NameSpace\\Somewhere']));
$hydrator = $hydratorBuilder->build($schema);

$dehydratorBuilder = new DehydratorBuilder();
$dehydrator = $dehydratorBuilder->build($schema);

$typedObjects = $hydrator->hydrate($input, $schema);
$output = $dehydrator->dehydrate($typedObjects, $schema);
// $input == $output && $input !== $output
```

### Performance Expectations

On my old Xeon W3570, both hydration and deydration of a an array of 1000 realistic objects (nested objects, arrays) takes about 100ms, 
on average a little short of 1ms per root object.  

### NULL Values

When dehydrating objects, the behavior differs for typed (non-stClass) objects and instances of `stdClass`. When the input is `stdClass`, all properties are 
included in the output as-is, while typed objects will be first flattened to `stdClass` with all properties that have NULL values removed (unless their type in the passed schema is `Schema::TYPE_NULL`).

### Default Vales

JSON-Schema supports several properties not relevant to validation, referred to as "Schema Annotations", one of which is `default`. The hydrator will use this value when the input is NULL or undefined.

### DateTime

By default will toss strings in date and date-time format into the `DateTime` constructor, and lets it figure out how to parse. When serializing it uses `Y-m-d\TH:i:s.uP`.

The expected in- and output format can be tweaked by configuring the factory with a custom instance of `DateTimeSerializer`:
 
 ```php
$hydratorBuilder = new HydratorBuilder(new ClassNameResolver(['A\\NameSpace\\Somewhere']), new DateTimeSerializer(\DateTime::RFC850));
 ```

# Contributing

Pull requests are *very* welcome, but the code has to be PSR2 compliant, follow used conventions concerning parameter and return type declarations, and the coverage can not go below **100%**. 

## License

KleijnWeb\PhpApi\Hydrator is made available under the terms of the [LGPL, version 3.0](https://spdx.org/licenses/LGPL-3.0.html#licenseText).
