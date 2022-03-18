<?php

namespace App\Doctrine;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\CheeseListing;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

class CheeseListingIsPublishedExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(private Security $security)
    {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    )
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        string $operationName = null,
        array $context = []
    )
    {
        $this->addWhere($queryBuilder, $resourceClass);
    }

    private function addWhere(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($resourceClass !== CheeseListing::class) {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        if (!$this->security->getUser()) {
            $queryBuilder->andWhere(sprintf('%s.isPublished = :is_published', $rootAlias));
            $queryBuilder->setParameter('is_published', true);
        } else {
            $queryBuilder->andWhere(sprintf('%s.isPublished = :is_published', $rootAlias));
            $queryBuilder->andWhere(sprintf('%s.owner = :owner', $rootAlias));
            $queryBuilder->setParameter('is_published', true);
            $queryBuilder->setParameter('owner', $this->security->getUser());
        }
    }
}