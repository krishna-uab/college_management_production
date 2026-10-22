<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class CrudService
{
    private const MAX_BULK = 1000;


    public function query(
        string $model,
        array $relations = [],
        array $where = [],
        ?string $orderBy = 'id',
        string $orderDir = 'desc',
        string|array|null $groupBy = null,
        ?int $limit = null,
        array $whereIn = []
    ): Builder {
        $query = $this->newQuery($model);
        $table = $query->getModel()->getTable();

        $query->select("{$table}.*");

        if ($relations) {
            $query->with($relations);
        }

        foreach ($where as $column => $value) {
            $column = $this->assertSafeColumn((string) $column);

            match (true) {
                is_array($value) => $query->whereIn($column, $value),
                $value === null  => $query->whereNull($column),
                default          => $query->where($column, $value),
            };
        }

        foreach ($whereIn as $column => $values) {
            $column = $this->assertSafeColumn((string) $column);

            if (!is_array($values)) {
                throw new InvalidArgumentException("whereIn value for {$column} must be an array.");
            }

            $query->whereIn($column, $values);
        }

        if (!empty($groupBy)) {
            $query->groupBy($groupBy);
        }

        if (!empty($orderBy)) {
            $direction = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';
            $query->orderBy($this->assertSafeColumn($orderBy), $direction);
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        return $query;
    }


    public function getResult(
        string $model,
        array $relations = [],
        array $where = [],
        ?string $orderBy = 'id',
        string $orderDir = 'desc',
        array $whereIn = []
    ): ?Model {
        return $this->query(
            model: $model,
            relations: $relations,
            where: $where,
            orderBy: $orderBy,
            orderDir: $orderDir,
            whereIn: $whereIn
        )->first();
    }

    public function getResults(
        string $model,
        array $relations = [],
        array $where = [],
        ?string $orderBy = 'id',
        string $orderDir = 'desc',
        string|array|null $groupBy = null,
        ?int $limit = null,
        array $whereIn = []
    ): Collection {
        return $this->query(
            $model,
            $relations,
            $where,
            $orderBy,
            $orderDir,
            $groupBy,
            $limit,
            $whereIn
        )->get();
    }

    public function findById(string $model, int $id, array $relations = []): Model
    {
        return $this->newQuery($model)
            ->with($relations)
            ->findOrFail($id);
    }

    public function getRecordWithCustomColumns(
        string $model,
        array $columns = ['*'],
        array $relations = [],
        array $where = [],
        ?string $orderBy = 'id',
        string $orderDir = 'desc',
        array $whereIn = []
    ): ?Model {
        $query = $this->newQuery($model);
        $eloquent = $query->getModel();
        $table = $eloquent->getTable();

        // Columns
        if ($columns && $columns !== ['*']) {
            if ($relations && !in_array($eloquent->getKeyName(), $columns, true)) {
                $columns[] = $eloquent->getKeyName();
            }

            $select = array_map(function ($c) use ($table): string {
                $c = $this->assertSafeColumn((string) $c);
                return str_contains($c, '.') ? $c : "{$table}.{$c}";
            }, $columns);
        } else {
            $select = ["{$table}.*"];
        }

        $query->select($select);

        // Relations
        $with = [];
        foreach ($relations as $name => $spec) {
            if (is_int($name)) {
                $with[] = (string) $spec;
            } elseif ($spec instanceof \Closure) {
                $with[$name] = $spec;
            } elseif (is_array($spec)) {
                $cols = array_map(fn($c) => $this->assertSafeColumn((string) $c), $spec);
                $with[$name] = fn($q) => $q->select(array_map(
                    fn($c) => str_contains($c, '.') ? $c : $q->getModel()->getTable() . ".{$c}",
                    $cols
                ));
            } else {
                throw new InvalidArgumentException("Invalid relation spec for {$name}.");
            }
        }

        if ($with) {
            $query->with($with);
        }

        // Where
        foreach ($where as $column => $value) {
            $column = $this->assertSafeColumn((string) $column);

            match (true) {
                is_array($value) => $query->whereIn($column, $value),
                $value === null  => $query->whereNull($column),
                default          => $query->where($column, $value),
            };
        }

        foreach ($whereIn as $column => $values) {
            $column = $this->assertSafeColumn((string) $column);

            if (!is_array($values)) {
                throw new InvalidArgumentException("whereIn value for {$column} must be an array.");
            }

            $query->whereIn($column, $values);
        }

        // Order
        if (!empty($orderBy)) {
            $query->orderBy(
                $this->assertSafeColumn($orderBy),
                strtolower($orderDir) === 'asc' ? 'asc' : 'desc'
            );
        }

        return $query->first();
    }

    public function getRecordsWithCustomColumns(
        string $model,
        array $columns = ['*'],
        array $relations = [],
        array $where = [],
        ?string $orderBy = 'id',
        string $orderDir = 'desc',
        ?int $limit = null,
        array $whereIn = []
    ): Collection {
        $query = $this->newQuery($model);
        $eloquent = $query->getModel();
        $table = $eloquent->getTable();

        // Columns
        if ($columns && $columns !== ['*']) {
            if ($relations && !in_array($eloquent->getKeyName(), $columns, true)) {
                $columns[] = $eloquent->getKeyName();
            }

            $select = array_map(function ($c) use ($table): string {
                $c = $this->assertSafeColumn((string) $c);
                return str_contains($c, '.') ? $c : "{$table}.{$c}";
            }, $columns);
        } else {
            $select = ["{$table}.*"];
        }

        $query->select($select);

        // Relations
        $with = [];
        foreach ($relations as $name => $spec) {
            if (is_int($name)) {
                $with[] = (string) $spec;
            } elseif ($spec instanceof \Closure) {
                $with[$name] = $spec;
            } elseif (is_array($spec)) {
                $cols = array_map(fn($c) => $this->assertSafeColumn((string) $c), $spec);
                $with[$name] = fn($q) => $q->select(array_map(
                    fn($c) => str_contains($c, '.') ? $c : $q->getModel()->getTable() . ".{$c}",
                    $cols
                ));
            } else {
                throw new InvalidArgumentException("Invalid relation spec for {$name}.");
            }
        }

        if ($with) {
            $query->with($with);
        }

        // Where
        foreach ($where as $column => $value) {
            $column = $this->assertSafeColumn((string) $column);

            match (true) {
                is_array($value) => $query->whereIn($column, $value),
                $value === null  => $query->whereNull($column),
                default          => $query->where($column, $value),
            };
        }

        foreach ($whereIn as $column => $values) {
            $column = $this->assertSafeColumn((string) $column);

            if (!is_array($values)) {
                throw new InvalidArgumentException("whereIn value for {$column} must be an array.");
            }

            $query->whereIn($column, $values);
        }

        // Order + limit
        if (!empty($orderBy)) {
            $query->orderBy(
                $this->assertSafeColumn($orderBy),
                strtolower($orderDir) === 'asc' ? 'asc' : 'desc'
            );
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    
    public function toggleStatus(string $model, int $id, string $column = 'status'): Model
    {
        $this->assertSafeColumn($column);

        $record = $this->newQuery($model)->findOrFail($id);
        $record->forceFill([$column => !$record->{$column}])->save();

        return $record;
    }

    public function updateField(
        string $model,
        int $id,
        string $column,
        mixed $value,
        array $allowedValues
    ): Model {
        $this->assertAllowedValue($column, $value, $allowedValues);

        $record = $this->newQuery($model)->findOrFail($id);
        $record->forceFill([$column => $value])->save();

        return $record;
    }

    public function softDelete(string $model, int $id): void
    {
        $this->newQuery($model)->findOrFail($id)->delete();
    }

    public function getTrashed(string $model, ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = $this->trashedQuery($model)->latest();

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    public function restore(string $model, int $id): Model
    {
        $record = $this->trashedQuery($model)->findOrFail($id);
        $record->restore();

        return $record;
    }

    public function forceDelete(string $model, int $id): void
    {
        $this->trashedQuery($model)->findOrFail($id)->forceDelete();
    }

    public function bulkInsert(string $model, array $rows, int $chunkSize = 500): int
    {
        if (!$rows) {
            return 0;
        }

        $this->assertBulkSize(count($rows));

        $proto = $this->newQuery($model)->getModel();
        $payload = [];
        $columns = null;

        foreach (array_values($rows) as $index => $row) {
            if (!is_array($row) || !$row) {
                throw new InvalidArgumentException("Row {$index} must be a non-empty array.");
            }

            foreach (array_keys($row) as $column) {
                $this->assertSafeColumn((string) $column);

                if (!$proto->isFillable((string) $column)) {
                    throw new InvalidArgumentException("Column {$column} is not fillable on {$model}.");
                }
            }

            $instance = $proto->newInstance();
            $instance->fill($row);

            if ($instance->usesTimestamps()) {
                $instance->updateTimestamps();
            }

            $attributes = $instance->getAttributes();
            ksort($attributes);
            $keys = array_keys($attributes);

            if ($columns === null) {
                $columns = $keys;
            } elseif ($columns !== $keys) {
                throw new InvalidArgumentException("Row {$index} has different columns than row 0.");
            }

            $payload[] = $attributes;
        }

        DB::transaction(function () use ($model, $payload, $chunkSize): void {
            foreach (array_chunk($payload, max(1, $chunkSize)) as $chunk) {
                $this->newQuery($model)->insert($chunk);
            }
        });

        return count($payload);
    }

    public function bulkUpdate(string $model, array $rows, string $keyColumn = 'id'): int
    {
        $this->assertSafeColumn($keyColumn);

        if (!$rows) {
            return 0;
        }

        $this->assertBulkSize(count($rows));

        $proto = $this->newQuery($model)->getModel();
        $values = [];

        foreach (array_values($rows) as $index => $row) {
            if (!is_array($row) || !array_key_exists($keyColumn, $row)) {
                throw new InvalidArgumentException("Row {$index} must contain '{$keyColumn}'.");
            }

            $id = $this->normalizeId($row[$keyColumn]);
            unset($row[$keyColumn]);

            if (!$row) {
                throw new InvalidArgumentException("Row {$index} has nothing to update.");
            }

            if (isset($values[$id])) {
                throw new InvalidArgumentException("Duplicate {$keyColumn} {$id} in rows.");
            }

            foreach (array_keys($row) as $column) {
                $this->assertSafeColumn((string) $column);

                if (!$proto->isFillable((string) $column)) {
                    throw new InvalidArgumentException("Column {$column} is not fillable on {$model}.");
                }
            }

            $values[$id] = $row;
        }

        return DB::transaction(function () use ($model, $keyColumn, $values): int {
            $records = $this->newQuery($model)
                ->whereIn($keyColumn, array_keys($values))
                ->lockForUpdate()
                ->get()
                ->keyBy($keyColumn);

            $missing = array_diff(array_keys($values), $records->keys()->all());

            if ($missing) {
                throw (new ModelNotFoundException())->setModel($model, array_values($missing));
            }

            $changed = 0;

            foreach ($values as $id => $row) {
                $record = $records->get($id);
                $record->fill($row);

                if ($record->isDirty()) {
                    $record->save();
                    $changed++;
                }
            }

            return $changed;
        });
    }

    public function bulkUpdateField(
        string $model,
        array $ids,
        string $column,
        mixed $value,
        array $allowedValues,
        bool $fireEvents = true
    ): int {
        $this->assertAllowedValue($column, $value, $allowedValues);

        $ids = $this->normalizeIds($ids);

        if (!$ids) {
            return 0;
        }

        return DB::transaction(function () use ($model, $ids, $column, $value, $fireEvents): int {
            $query = $this->newQuery($model)->whereKey($ids);

            if (!$fireEvents) {
                return (int) $query->update([$column => $value]);
            }

            return $this->eachRecord(
                $query->lockForUpdate(),
                static fn(Model $record) => $record->forceFill([$column => $value])->save()
            );
        });
    }


    public function bulkToggleStatus(string $model, array $ids, string $column = 'status'): int
    {
        $this->assertSafeColumn($column);

        $ids = $this->normalizeIds($ids);

        if (!$ids) {
            return 0;
        }

        return DB::transaction(fn(): int => $this->eachRecord(
            $this->newQuery($model)->whereKey($ids)->lockForUpdate(),
            static fn(Model $record) => $record->forceFill([$column => !$record->{$column}])->save()
        ));
    }

    public function bulkDelete(string $model, array $ids, bool $fireEvents = true): int
    {
        $ids = $this->normalizeIds($ids);

        if (!$ids) {
            return 0;
        }

        return DB::transaction(function () use ($model, $ids, $fireEvents): int {
            $query = $this->newQuery($model)->whereKey($ids);

            if (!$fireEvents) {
                return (int) $query->delete();
            }

            return $this->eachRecord(
                $query->lockForUpdate(),
                static fn(Model $record) => $record->delete()
            );
        });
    }

    public function bulkRestore(string $model, array $ids): int
    {
        $ids = $this->normalizeIds($ids);

        if (!$ids) {
            return 0;
        }

        return DB::transaction(fn(): int => $this->eachRecord(
            $this->trashedQuery($model)->whereKey($ids)->lockForUpdate(),
            static fn(Model $record) => $record->restore()
        ));
    }

    public function bulkForceDelete(string $model, array $ids, bool $fireEvents = true): int
    {
        $ids = $this->normalizeIds($ids);

        if (!$ids) {
            return 0;
        }

        return DB::transaction(function () use ($model, $ids, $fireEvents): int {
            $query = $this->trashedQuery($model)->whereKey($ids);

            if (!$fireEvents) {
                return (int) $query->forceDelete();
            }

            return $this->eachRecord(
                $query->lockForUpdate(),
                static fn(Model $record) => $record->forceDelete()
            );
        });
    }

    public function isDuplicateByAll(string $model, array $conditions, ?int $ignoreId = null): bool
    {
        $conditions = $this->filledConditions($conditions);

        if (!$conditions) {
            return false;
        }

        return $this->newQuery($model)
            ->where($conditions)
            ->when($ignoreId, fn(Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    public function isDuplicateByAny(string $model, array $conditions, ?int $ignoreId = null): bool
    {
        $conditions = $this->filledConditions($conditions);

        if (!$conditions) {
            return false;
        }

        return $this->newQuery($model)
            ->where(static function (Builder $q) use ($conditions): void {
                foreach ($conditions as $field => $value) {
                    $q->orWhere($field, $value);
                }
            })
            ->when($ignoreId, fn(Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    private function newQuery(string $model): Builder
    {
        if (!is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException("{$model} is not an Eloquent model.");
        }

        return $model::query();
    }

    /** @return Builder<Model> */
    private function trashedQuery(string $model): Builder
    {
        if (!in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            throw new LogicException("{$model} does not support soft deletes.");
        }

        return $this->newQuery($model)->onlyTrashed();
    }

    private function assertSafeColumn(string $column): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column)) {
            throw new InvalidArgumentException("Invalid column name: {$column}");
        }

        return $column;
    }

    private function assertAllowedValue(string $column, mixed $value, array $allowedValues): void
    {
        $this->assertSafeColumn($column);

        if (
            !is_scalar($value)
            || !in_array((string) $value, array_map('strval', $allowedValues), true)
        ) {
            throw new InvalidArgumentException("Invalid value for {$column}.");
        }
    }

    private function assertBulkSize(int $count): void
    {
        if ($count > self::MAX_BULK) {
            throw new InvalidArgumentException(
                'Bulk limit exceeded: max ' . self::MAX_BULK . " items per call, {$count} given."
            );
        }
    }

    private function normalizeId(mixed $id): int
    {
        if (is_int($id) && $id > 0) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id) && (int) $id > 0) {
            return (int) $id;
        }

        throw new InvalidArgumentException('Invalid id supplied.');
    }

    /** @return int[] unique, validated ids */
    private function normalizeIds(array $ids): array
    {
        $clean = [];

        foreach ($ids as $id) {
            $id = $this->normalizeId($id);
            $clean[$id] = $id;
        }

        $this->assertBulkSize(count($clean));

        return array_values($clean);
    }


    private function eachRecord(Builder $query, callable $action): int
    {
        $count = 0;

        foreach ($query->get() as $record) {
            if ($action($record)) {
                $count++;
            }
        }

        return $count;
    }

    private function filledConditions(array $conditions): array
    {
        $filled = array_filter($conditions, static fn($v) => filled($v));

        foreach (array_keys($filled) as $column) {
            $this->assertSafeColumn((string) $column);
        }

        return $filled;
    }
}
