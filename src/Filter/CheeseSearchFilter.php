<?php

namespace App\Filter;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;

class CheeseSearchFilter extends AbstractFilter
{

    protected function filterProperty(string $property, $value, QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        if ($property !== "search") {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        // a param name that is guaranteed unique in this query
        $parameterName = $queryNameGenerator->generateParameterName('search');

        $queryBuilder
            ->andWhere(sprintf('%s.title LIKE :%s OR %s.description LIKE :%s', $alias, $parameterName, $alias, $parameterName))
            ->setParameter($parameterName, '%'.$value.'%');
    }

    public function getDescription(string $resourceClass): array
    {
       return [
           'search' => [
               'property' => null,
               'type' => 'string',
               'required' => false,
               'description' => 'Search across multiple fields',
           ]
       ];
    }
}