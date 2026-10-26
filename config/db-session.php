<?php
require_once __DIR__ . '/database.php';

/** Stores PHP sessions in the database so every serverless instance sees the same session. */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = $this->pdo->prepare('SELECT payload FROM sessions WHERE id = ? AND last_activity > ?');
        $stmt->execute([$id, time() - SESSION_LIFETIME]);
        $data = $stmt->fetchColumn();

        return $data === false ? '' : (string) $data;
    }

    public function write(string $id, string $data): bool
    {
        $now = time();
        $stmt = $this->pdo->prepare(
            'INSERT INTO sessions (id, payload, last_activity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE payload = ?, last_activity = ?'
        );

        return $stmt->execute([$id, $data, $now, $data, $now]);
    }

    public function destroy(string $id): bool
    {
        return $this->pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE last_activity < ?');
        $stmt->execute([time() - $max_lifetime]);

        return $stmt->rowCount();
    }
}
