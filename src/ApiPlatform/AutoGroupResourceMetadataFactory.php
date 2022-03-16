<?php

namespace App\ApiPlatform;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

class AutoGroupResourceMetadataFactory implements ResourceMetadataFactoryInterface
{
    private ResourceMetadataFactoryInterface $decorated;
    private array $resourceMetadata = [];

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        if (isset($this->resourceMetadata[$resourceClass])) {
            return $this->resourceMetadata[$resourceClass];
        }

        $resourceMetadata = $this->decorated->create($resourceClass);

        $itemOperations = $resourceMetadata->getItemOperations();
        $resourceMetadata = $resourceMetadata->withItemOperations(
            $this->updateContextOnOperations($itemOperations, $resourceMetadata->getShortName(), true)
        );

        $collectionOperations = $resourceMetadata->getCollectionOperations();
        $resourceMetadata = $resourceMetadata->withCollectionOperations(
            $this->updateContextOnOperations($collectionOperations, $resourceMetadata->getShortName(), false)
        );

        $this->resourceMetadata[$resourceClass] = $resourceMetadata;

        return $resourceMetadata;
    }

    private function updateContextOnOperations(array $operations, string $shortName, bool $isItem): array
    {
        foreach ($operations as $operationName => $operationOptions) {
            // get context groups from annotation
            // merge normalization context groups from annotation and default groups
            // remove duplicate values after merge

            // case : normalization
            $operationOptions['normalization_context'] = $operationOptions['normalization_context'] ?? [];
            $operationOptions['normalization_context']['groups'] = $operationOptions['normalization_context']['groups'] ?? [];
            $operationOptions['normalization_context']['groups'] = array_unique(array_merge(
                $operationOptions['normalization_context']['groups'],
                $this->getDefaultGroups($shortName, true, $isItem, $operationName)
            ));

            // case : denormalization
            $operationOptions['denormalization_context'] = $operationOptions['denormalization_context'] ?? [];
            $operationOptions['denormalization_context']['groups'] = $operationOptions['denormalization_context']['groups'] ?? [];
            $operationOptions['denormalization_context']['groups'] = array_unique(array_merge(
                $operationOptions['denormalization_context']['groups'],
                $this->getDefaultGroups($shortName, false, $isItem, $operationName)
            ));
            $operations[$operationName] = $operationOptions;
        }
        return $operations;
    }

    private function getDefaultGroups(string $shortName, bool $normalization, bool $isItem, string $operationName): array
    {
        $shortName = strtolower($shortName);
        $readOrWrite = $normalization ? 'read' : 'write';
        $itemOrCollection = $isItem ? 'item' : 'collection';
        return [
            // {shortName}:{read/write}
            // e.g. user:read
            sprintf('%s:%s', $shortName, $readOrWrite),
            // {shortName}:{item/collection}:{read/write}
            // e.g. user:collection:read
            sprintf('%s:%s:%s', $shortName, $itemOrCollection, $readOrWrite),
            // {shortName}:{item/collection}:{operationName}
            // e.g. user:collection:get
            sprintf('%s:%s:%s', $shortName, $itemOrCollection, $operationName),
        ];
    }
}