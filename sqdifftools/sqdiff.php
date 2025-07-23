<?php
// db_diff.php
ini_set("display_errors", true);
ini_set('default_charset', 'utf-8');

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_STRICT ^ E_DEPRECATED);
// --- Load Config ---
$config = require __DIR__ . '/sqdiffconfig.php';

$db1 = new PDO(
    "mysql:host={$config['db1']['host']};dbname={$config['db1']['dbname']}",
    $config['db1']['user'],
    $config['db1']['pass']
);

$db2 = new PDO(
    "mysql:host={$config['db2']['host']};dbname={$config['db2']['dbname']}",
    $config['db2']['user'],
    $config['db2']['pass']
);

function getTables(PDO $db) {
    return $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
}

function getColumns(PDO $db, $table) {
    $stmt = $db->query("SHOW COLUMNS FROM `{$table}`");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$tables1 = getTables($db1);
$tables2 = getTables($db2);

$tableDiff = array_diff($tables1, $tables2);
$tableDiffReverse = array_diff($tables2, $tables1);

$columnDiff = [];
$syncSql = [];
foreach (array_intersect($tables1, $tables2) as $table) {
    $cols1 = getColumns($db1, $table);
    $cols2 = getColumns($db2, $table);

    $col1Names = array_column($cols1, 'Field');
    $col2Names = array_column($cols2, 'Field');

    $diff1 = array_diff($col1Names, $col2Names);
    $diff2 = array_diff($col2Names, $col1Names);

    if ($diff1 || $diff2) {
        $columnDiff[$table] = ['only_in_db1' => $diff1, 'only_in_db2' => $diff2];

        foreach ($diff2 as $missingCol) {
            foreach ($cols2 as $colDef) {
                if ($colDef['Field'] === $missingCol) {
                    $syncSql[] = "ALTER TABLE `{$table}` ADD `{$colDef['Field']}` {$colDef['Type']}" .
                        ($colDef['Null'] === 'NO' ? ' NOT NULL' : '') .
                        ($colDef['Default'] !== null ? " DEFAULT '{$colDef['Default']}'" : '') .
                        ($colDef['Extra'] ? " {$colDef['Extra']}" : '') . ";";
                    break;
                }
            }
        }
    }
}

foreach ($tableDiffReverse as $table) {
    $stmt = $db2->query("SHOW CREATE TABLE `{$table}`");
    $create = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($create && isset($create['Create Table'])) {
        $syncSql[] = $create['Create Table'] . ";";
    }
}

// --- Render HTML ---
?>
<html>
<head>
    <title>Database Diff Report</title>
    <style>
        body { font-family: sans-serif; }
        h2 { margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #999; padding: 8px; text-align: left; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<h1>Database Schema Diff</h1>

<h2>Tables only in DB1</h2>
<ul>
    <?php foreach ($tableDiff as $table) echo "<li>$table</li>"; ?>
</ul>

<h2>Tables only in DB2</h2>
<ul>
    <?php foreach ($tableDiffReverse as $table) echo "<li>$table</li>"; ?>
</ul>

<h2>Column Differences in Common Tables</h2>
<?php foreach ($columnDiff as $table => $diff): ?>
    <h3>Table: <?= htmlspecialchars($table) ?></h3>
    <table>
        <tr>
            <th>Only in DB1</th>
            <th>Only in DB2</th>
        </tr>
        <tr>
            <td><?= implode(', ', $diff['only_in_db1']) ?></td>
            <td><?= implode(', ', $diff['only_in_db2']) ?></td>
        </tr>
    </table>
<?php endforeach; ?>

<h2>Suggested Sync SQL (to apply to DB1)</h2>
<pre><?= htmlspecialchars(implode("\n", $syncSql)) ?></pre>

</body>
</html>
