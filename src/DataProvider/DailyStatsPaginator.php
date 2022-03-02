<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\PaginatorInterface;
use App\Service\StatsHelper;
use Traversable;

class DailyStatsPaginator implements PaginatorInterface, \IteratorAggregate
{
    private ?Traversable $dailyStatsIterator = null;

    public function __construct(
        private StatsHelper $statsHelper,
        private int $currentPage,
        private int $itemsPerPage
    )
    {
    }

    public function getLastPage(): float
    {
        return ceil($this->getTotalItems() / $this->getItemsPerPage()) ?: 1.;
    }

    public function getTotalItems(): float
    {
        return $this->statsHelper->count();
    }

    public function getCurrentPage(): float
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): float
    {
        return $this->itemsPerPage;
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    private function getOffset(): int
    {
        return (($this->getCurrentPage() - 1) * $this->getItemsPerPage());
    }

    public function getIterator(): Traversable
    {
        if ($this->dailyStatsIterator === null) {
            $this->dailyStatsIterator = new \ArrayIterator(
                $this->statsHelper->fetchMany(
                    $this->getItemsPerPage(),
                    $this->getOffset()
                )
            );
        }

        return $this->dailyStatsIterator;
    }
}