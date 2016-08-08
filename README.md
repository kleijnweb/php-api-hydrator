# KleijnWeb\PhpApi\Hydrator 
[![Build Status](https://travis-ci.org/kleijnweb/php-api-hydrator.svg?branch=master)](https://travis-ci.org/kleijnweb/php-api-hydrator)
[![Coverage Status](https://coveralls.io/repos/github/kleijnweb/php-api-hydrator/badge.svg?branch=master)](https://coveralls.io/github/kleijnweb/php-api-hydrator?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kleijnweb/php-api-hydrator/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kleijnweb/php-api-hydrator/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/kleijnweb/php-api-hydrator/v/stable)](https://packagist.org/packages/kleijnweb/php-api-hydrator)

A small PHP7 library for hydrating objects using api descriptions. Hydrating in this context refers to creating fully initialized typed objects from input consisting of arrays and instances of `stdClass`.
 
Works with [OpenAPI 2.0](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/2.0.md) (FKA _Swagger_), [RAML 1.0](https://github.com/raml-org/raml-spec/blob/master/versions/raml-10/raml-10.md/) support is in development.

Relies on functionality provided by [KleijnWeb\PhpApi\Descriptions](https://github.com/kleijnweb/php-api-descriptions).

# Usage

Converting from `stdClass|stdClass[]` to typed objects matching your API description on hydration, the reverse on dehydration:

```php
$schema = $description->getPath('/foo')->getOperation('post')->getRequestSchema();
$hydrator = new ObjectHydrator(new ClassNameResolver(['A\\NameSpace\\Somewhere']));
$typedObjects = $hydrator->hydrate($input, $schema);
$output = $hydrator->dehydrate($typedObjects, $schema);
// $input == $output && $input !== $output
```

You can also technically use (abuse?) the hydrator to cast scalar values: it will accept any type of `Schema`.

### DateTime

Parses and produces [RFC3339](http://xml2rfc.ietf.org/public/rfc/html/rfc3339.html#anchor14) dates, in accorance with OpenAPi 2.0. Easily tweakable by injecting a custom instance of `DateTimeSerializer`:
 
 ```php
 $hydrator = new ObjectHydrator(new ClassNameResolver(['A\\NameSpace\\Somewhere']), new DateTimeSerializer(\DateTime::RFC850);
 ```

# Contributing

Pull requests are *very* welcome, but the code has to be PSR2 compliant, follow used conventions concerning parameter and return type declarations, and the coverage can not go below **100%**. 

## License

KleijnWeb\PhpApi\Hydrator is made available under the terms of the [LGPL, version 3.0](https://spdx.org/licenses/LGPL-3.0.html#licenseText).
