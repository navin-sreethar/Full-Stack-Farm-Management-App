<?php
/**
 * Base Model - Database Abstraction Layer
 * All models extend this for CRUD operations via PDO
 */

class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';

    /**
     * Find all records, optionally filtered
     */
    public function findAll(array $conditions = [], string $orderBy = 'id DESC', int $limit = 0): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $wheres = [];
            foreach ($conditions as $col => $val) {
                $wheres[] = "{$col} = ?";
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        $sql .= " ORDER BY {$orderBy}";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = App::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find a single record by primary key
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = App::db()->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find a single record by column value
     */
    public function findBy(string $column, mixed $value): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = ? LIMIT 1";
        $stmt = App::db()->prepare($sql);
        $stmt->execute([$value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Insert a new record, return the insert ID
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = App::db()->prepare($sql);
        $stmt->execute(array_values($data));
        return (int) App::db()->lastInsertId();
    }

    /**
     * Update a record by primary key
     */
    public function update(int $id, array $data): bool
    {
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "{$col} = ?";
            $params[] = $val;
        }
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = ?";
        $stmt = App::db()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = App::db()->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Count records with optional conditions
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $wheres = [];
            foreach ($conditions as $col => $val) {
                $wheres[] = "{$col} = ?";
                $params[] = $val;
            }
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }

        $stmt = App::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Run a raw query
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = App::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run a raw statement (insert/update/delete)
     */
    public function exec(string $sql, array $params = []): bool
    {
        $stmt = App::db()->prepare($sql);
        return $stmt->execute($params);
    }
}
