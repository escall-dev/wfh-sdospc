<?php

define('BACKUP_DIR', __DIR__ . '/../backups/');

function ensureBackupDir(): void {
    if (!is_dir(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0755, true);
    }
}

function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function generateSqlDump(PDO $pdo, array $tables = []): string {
    $sql = "-- WFH-SDOSPC Database Backup\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: " . DB_NAME . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

    if (empty($tables)) {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
        $createSql = $createStmt['Create Table'] ?? '';
        if (!$createSql) continue;

        $sql .= "-- Table: {$table}\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $sql .= $createSql . ";\n\n";

        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $colList = '`' . implode('`, `', $columns) . '`';

            foreach (array_chunk($rows, 100) as $chunk) {
                $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n";
                $values = [];
                foreach ($chunk as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote($val);
                        }
                    }
                    $values[] = '(' . implode(', ', $vals) . ')';
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function generateDataOnlyDump(PDO $pdo, array $tables): string {
    $sql = "-- WFH-SDOSPC Pre-Deletion Backup (Data Only)\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Database: " . DB_NAME . "\n";
    $sql .= "-- Type: Pre-deletion snapshot\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            $sql .= "-- Table `{$table}`: 0 rows (empty)\n\n";
            continue;
        }

        $columns = array_keys($rows[0]);
        $colList = '`' . implode('`, `', $columns) . '`';

        $sql .= "-- Table: {$table} (" . count($rows) . " rows)\n";

        foreach (array_chunk($rows, 100) as $chunk) {
            $sql .= "INSERT IGNORE INTO `{$table}` ({$colList}) VALUES\n";
            $values = [];
            foreach ($chunk as $row) {
                $vals = [];
                foreach ($row as $val) {
                    if ($val === null) {
                        $vals[] = 'NULL';
                    } else {
                        $vals[] = $pdo->quote($val);
                    }
                }
                $values[] = '(' . implode(', ', $vals) . ')';
            }
            $sql .= implode(",\n", $values) . ";\n\n";
        }
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql;
}

function createFullDatabaseBackup(PDO $pdo, string $type, string $description, ?int $createdBy): array {
    ensureBackupDir();

    $timestamp = date('Y-m-d_His');
    $backupName = DB_NAME . '_full_' . $timestamp;
    $fileName = $backupName . '.sql';
    $filePath = BACKUP_DIR . $fileName;

    $sql = generateSqlDump($pdo);
    file_put_contents($filePath, $sql);
    $fileSize = filesize($filePath);

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $insert = $pdo->prepare(
        "INSERT INTO db_backups (backup_name, file_name, backup_type, description, tables_included, file_size, created_by)
         VALUES (:name, :file, :type, :desc, :tables, :size, :by)"
    );
    $insert->execute([
        ':name' => $backupName,
        ':file' => $fileName,
        ':type' => $type,
        ':desc' => $description,
        ':tables' => implode(', ', $tables),
        ':size' => $fileSize,
        ':by' => $createdBy,
    ]);

    return [
        'id' => $pdo->lastInsertId(),
        'backup_name' => $backupName,
        'file_name' => $fileName,
        'file_size' => $fileSize,
    ];
}

function createTableBackup(PDO $pdo, array $tables, string $type, string $description, ?int $createdBy): array {
    ensureBackupDir();

    $timestamp = date('Y-m-d_His');
    $suffix = str_replace(' ', '_', strtolower(preg_replace('/[^a-zA-Z0-9 ]/', '', $description)));
    $suffix = substr($suffix, 0, 40);
    $backupName = DB_NAME . '_' . $suffix . '_' . $timestamp;
    $fileName = $backupName . '.sql';
    $filePath = BACKUP_DIR . $fileName;

    $sql = generateDataOnlyDump($pdo, $tables);
    file_put_contents($filePath, $sql);
    $fileSize = filesize($filePath);

    $insert = $pdo->prepare(
        "INSERT INTO db_backups (backup_name, file_name, backup_type, description, tables_included, file_size, created_by)
         VALUES (:name, :file, :type, :desc, :tables, :size, :by)"
    );
    $insert->execute([
        ':name' => $backupName,
        ':file' => $fileName,
        ':type' => $type,
        ':desc' => $description,
        ':tables' => implode(', ', $tables),
        ':size' => $fileSize,
        ':by' => $createdBy,
    ]);

    return [
        'id' => $pdo->lastInsertId(),
        'backup_name' => $backupName,
        'file_name' => $fileName,
        'file_size' => $fileSize,
    ];
}

function restoreFromBackup(PDO $pdo, int $backupId): array {
    $stmt = $pdo->prepare("SELECT * FROM db_backups WHERE id = :id");
    $stmt->execute([':id' => $backupId]);
    $backup = $stmt->fetch();

    if (!$backup) {
        return ['success' => false, 'message' => 'Backup not found.'];
    }

    $filePath = BACKUP_DIR . $backup['file_name'];
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Backup file is missing from disk.'];
    }

    $sql = file_get_contents($filePath);
    if (empty($sql)) {
        return ['success' => false, 'message' => 'Backup file is empty.'];
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

        $statements = array_filter(
            array_map('trim', explode(";\n", $sql)),
            function ($s) {
                $s = trim($s);
                return $s !== '' && strpos($s, '--') !== 0 && $s !== 'SET FOREIGN_KEY_CHECKS=0' && $s !== 'SET FOREIGN_KEY_CHECKS=1';
            }
        );

        foreach ($statements as $statement) {
            $trimmed = trim($statement);
            if (empty($trimmed) || strpos($trimmed, '--') === 0) continue;
            $pdo->exec($trimmed);
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        return ['success' => true, 'message' => 'Backup "' . $backup['backup_name'] . '" restored successfully.'];
    } catch (Exception $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        return ['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()];
    }
}

function deleteBackupEntry(PDO $pdo, int $backupId): array {
    $stmt = $pdo->prepare("SELECT * FROM db_backups WHERE id = :id");
    $stmt->execute([':id' => $backupId]);
    $backup = $stmt->fetch();

    if (!$backup) {
        return ['success' => false, 'message' => 'Backup not found.'];
    }

    $filePath = BACKUP_DIR . $backup['file_name'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    $del = $pdo->prepare("DELETE FROM db_backups WHERE id = :id");
    $del->execute([':id' => $backupId]);

    return ['success' => true, 'message' => 'Backup deleted.'];
}

function checkAndRunScheduledBackup(PDO $pdo, ?int $userId): void {
    $monthStart = date('Y-m-01 00:00:00');
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM db_backups WHERE backup_type = 'scheduled' AND created_at >= :ms"
    );
    $stmt->execute([':ms' => $monthStart]);

    if ((int) $stmt->fetchColumn() === 0) {
        createFullDatabaseBackup(
            $pdo,
            'scheduled',
            'Automatic monthly backup - ' . date('F Y'),
            $userId
        );
    }
}

function getBackupStats(PDO $pdo): array {
    $total = (int) $pdo->query("SELECT COUNT(*) FROM db_backups")->fetchColumn();
    $totalSize = (int) $pdo->query("SELECT COALESCE(SUM(file_size), 0) FROM db_backups")->fetchColumn();
    $lastBackup = $pdo->query("SELECT MAX(created_at) FROM db_backups")->fetchColumn();

    $nextMonth = date('Y-m-01', strtotime('first day of next month'));

    return [
        'total_backups' => $total,
        'total_size' => $totalSize,
        'total_size_formatted' => formatFileSize($totalSize),
        'last_backup' => $lastBackup,
        'next_scheduled' => $nextMonth,
    ];
}
