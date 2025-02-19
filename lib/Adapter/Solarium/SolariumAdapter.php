<?php declare(strict_types=1);

namespace Pagerfanta\Solarium;

use Pagerfanta\Adapter\AdapterInterface;
use Solarium\Core\Client\ClientInterface;
use Solarium\Core\Client\Endpoint;
use Solarium\QueryType\Select\Query\Query;
use Solarium\QueryType\Select\Result\Result;

/**
 * Adapter which calculates pagination from a Solarium Query.
 */
class SolariumAdapter implements AdapterInterface
{
    private ClientInterface $client;
    private Query $query;

    private ?Result $resultSet = null;

    /**
     * @var Endpoint|string|null
     */
    private $endpoint;

    private ?int $resultSetStart = null;
    private ?int $resultSetRows = null;

    public function __construct(ClientInterface $client, Query $query)
    {
        $this->client = $client;
        $this->query = $query;
    }

    public function getNbResults(): int
    {
        return $this->getResultSet()->getNumFound();
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->getResultSet($offset, $length);
    }

    public function getResultSet(?int $start = null, ?int $rows = null): Result
    {
        if ($this->resultSetStartAndRowsAreNotNullAndChange($start, $rows)) {
            $this->resultSetStart = $start;
            $this->resultSetRows = $rows;

            $this->modifyQuery();
            $this->resultSet = null;
        }

        if (null === $this->resultSet) {
            $this->resultSet = $this->createResultSet();
        }

        return $this->resultSet;
    }

    private function resultSetStartAndRowsAreNotNullAndChange(?int $start, ?int $rows): bool
    {
        return $this->resultSetStartAndRowsAreNotNull($start, $rows) && $this->resultSetStartAndRowsChange($start, $rows);
    }

    private function resultSetStartAndRowsAreNotNull(?int $start, ?int $rows): bool
    {
        return null !== $start && null !== $rows;
    }

    private function resultSetStartAndRowsChange(?int $start, ?int $rows): bool
    {
        return $start !== $this->resultSetStart || $rows !== $this->resultSetRows;
    }

    private function modifyQuery(): void
    {
        $this->query->setStart($this->resultSetStart)
            ->setRows($this->resultSetRows);
    }

    private function createResultSet(): Result
    {
        return $this->client->select($this->query, $this->endpoint);
    }

    /**
     * @param Endpoint|string|null $endpoint
     *
     * @return $this
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }
}
