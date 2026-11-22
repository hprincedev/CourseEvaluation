<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Connection Test - CEMsystem</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 40px; }
        .card { max-width: 500px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; padding: 12px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        td, th { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Database Connectivity Test</h2>
        <div class="success">
            &#10004; Successfully connected to MySQL via PDO!
        </div>
        <table>
            <tr>
                <th>Database</th>
                <td><code>course_evaluation</code></td>
            </tr>
            <tr>
                <th>Driver</th>
                <td><code><?= $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) ?></code></td>
            </tr>
            <tr>
                <th>Server Version</th>
                <td><?= $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?></td>
            </tr>
            <tr>
                <th>Connection Status</th>
                <td><?= $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
