<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Bridge\GoatQuery;

use Goat\Query\ExpressionLike;
use Goat\Query\ExpressionRaw;
use Goat\Query\ExpressionValue;
use Goat\Query\Query;
use Goat\Query\SelectQuery;
use Goat\Query\Expression\CastExpression;
use MakinaCorpus\EventStore\AbstractEventQuery;
use MakinaCorpus\EventStore\EventStream;
use MakinaCorpus\Normalization\NameMap;

final class GoatQueryEventQuery extends AbstractEventQuery
{
    private GoatQueryEventStore $eventStore;

    public function __construct(GoatQueryEventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): EventStream
    {
        $select = $this
            ->createSelectQuery($this)
            ->columnExpression("event.*")
            ->columns(['index.aggregate_type', 'index.aggregate_root', 'index.namespace'])
        ;

        if ($this->limit) {
            $select->range($this->limit);
        }

        return new GoatQueryEventStream($select->execute(), $this->eventStore);
    }

    /**
     * Count all the things.
     */
    public function doCount(): int
    {
        if ($this->isApproximateCountAllowed() && $this->isApproximateCountPossible()) {
            $eventRelation = $this->eventStore->getEventRelation('default'); // @todo

            if ($schema = $eventRelation->getSchema()) {
                return (int) $this
                    ->eventStore
                    ->getRunner()
                    ->getQueryBuilder()
                    ->select('pg_class')
                    ->columnExpression('reltuples::bigint', 'total')
                    ->whereExpression("oid = ?", new CastExpression($schema . '.' . $eventRelation->getName(), 'regclass'))
                    ->execute()
                    ->fetchField()
                ;
            }

            return $this
                ->eventStore
                ->getRunner()
                ->getQueryBuilder()
                ->select('pg_class')
                ->columnExpression('reltuples::bigint', 'total')
                ->where('relname', $eventRelation->getName())
                ->execute()
                ->fetchField()
            ;
        }

        return $this
            ->createSelectQuery()
            ->removeAllColumns()
            ->removeAllOrder()
            ->columnExpression('count(*)', 'total')
            ->execute()
            ->fetchField()
        ;
    }

    /**
     * Create the SELECT query.
     *
     * @internal
     *   Public because the event store uses it for counting.
     */
    public function createSelectQuery(): SelectQuery
    {
        $nameMap = $this->eventStore->getNameMap();
        $eventRelation = $this->eventStore->getEventRelation('default'); // @todo
        $indexRelation = $this->eventStore->getIndexRelation();

        $select = $this
            ->eventStore
            ->getRunner()
            ->getQueryBuilder()
            ->select($eventRelation)
            ->join($indexRelation, 'event.aggregate_id = index.aggregate_id')
        ;

        $where = $select->getWhere();

        // @todo names convertion using the name map should not belong to the
        //   store implementation, we must find a way to make it completely
        //   dependency free.
        if ($this->names) {
            $conditions = [];
            foreach ($this->names as $name) {
                if ($name !== ($value = $nameMap->toPhpType($name, NameMap::TAG_EVENT))) {
                    $conditions[] = $value;
                    $conditions[] = $name;
                } else if ($name !== ($value = $nameMap->fromPhpType($name, NameMap::TAG_EVENT))) {
                    $conditions[] = $value;
                    $conditions[] = $name;
                } else {
                    $conditions[] = $name;
                }
            }
            $where->isIn('event.name', $conditions);
        }
        if ($this->searchName) {
            $where->expression(ExpressionLike::iLike('event.name', '%?%', $this->searchName));
        }
        if ($this->searchData) {
            // TODO: use jsonb storage and search ?
            $where->expression(ExpressionLike::iLike('data', '%?%', $this->searchData));
        }
        if ($this->aggregateTypes) {
            $where->isIn('index.aggregate_type', $this->aggregateTypes);
        }
        if ($this->aggregateId) {
            if ($this->aggregateAsRoot) {
                $where->or()->isEqual('index.aggregate_id', $this->aggregateId)->isEqual('index.aggregate_root', $this->aggregateId);
            } else {
                $where->isEqual('index.aggregate_id', $this->aggregateId);
            }
        }
        if ($this->dateLowerBound && $this->dateHigherBound) {
            $where->condition(
                'event.valid_at',
                // need to accept 2019-04-25 10:12:13.22115 as valid for
                // higerBound 2019-04-25 10:12:13 using a date_trunc(second)
                // on event.valid_at would be a perf killer, better to check
                // against 2019-04-25 10:12:14 (note that would also accept
                // 2019-04-25 10:12:14.00000).
                // @todo get rid of that, find a better way.
                new ExpressionRaw(\sprintf("'%s'::timestamp without time zone AND '%s'::timestamp without time zone + interval '1 second'",
                    $this->dateLowerBound->format("Y-m-d H:i:s"),
                    $this->dateHigherBound->format("Y-m-d H:i:s")
                )),
                'BETWEEN'
            );
        }
        if (null !== $this->failed) {
            $where->condition('event.has_failed', $this->failed);
        }

        // @todo make goat-query evolve to support JSON expressions better than that.
        if ($this->properties) {
            foreach ($this->properties as $propName => $values) {
                $values = \array_filter($values, fn ($value) => null !== $value);

                if ($values) {
                    throw new \Exception("Not implemented yet.");
                    // $select->expression("properties->>? in ()", ExpressionValue::create($value, 'varchar'));
                } else {
                    $select->expression("properties->>? is not null", ExpressionValue::create($propName, 'varchar'));
                }
            }
        }
        if ($this->withoutProperties) {
            foreach (\array_keys($this->withoutProperties) as $propName) {
                $select->expression("properties->>? is null", ExpressionValue::create($propName, 'varchar'));
            }
        }

        if ($this->reverse) {
            $select->orderBy('event.valid_at', Query::ORDER_DESC);
            // @todo order by revision as well, for disambuigating when
            //   dates are the same (or find another way that does not
            //   necessitate to change the date: may be an "order" field,
            //   when you position explicity and event after another, then
            //   it innherits from the order + 1 from the previous, but
            //   without being a key itself ?).
            if ($this->position) {
                $where->isLessOrEqual('event.position', $this->position);
            }
            if ($this->revision) {
                $where->isLessOrEqual('event.revision', $this->revision);
            }
        } else {
            $select->orderBy('event.valid_at', Query::ORDER_ASC);
            if ($this->position) {
                $where->isGreaterOrEqual('event.position', $this->position);
            }
            if ($this->revision) {
                $where->isGreaterOrEqual('event.revision', $this->revision);
            }
        }

        if ($this->dateLowerBound && !$this->dateHigherBound) {
            $where->isGreaterOrEqual('event.valid_at', $this->dateLowerBound);
        }

        if ($this->dateHigherBound && !$this->dateLowerBound) {
            $where->isLessorEqual(
                'event.valid_at',
                new ExpressionRaw(\sprintf("'%s'::timestamp without time zone + interval '1 second'", $this->dateHigherBound->format("Y-m-d H:i:s")))
            );
        }

        return $select;
    }
}
