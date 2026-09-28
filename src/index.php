<?php
declare(strict_types=1);

// Parámetros de conexión a la base de datos (obtenidos de variables de entorno o valores por defecto)
$dbHost     = getenv('DB_HOST') ?: 'db';
$dbPort     = getenv('DB_PORT') ?: '3306';
$dbName     = getenv('DB_DATABASE') ?: 'daw_db';
$dbUser     = getenv('DB_USERNAME') ?: 'daw_user';
$dbPass     = getenv('DB_PASSWORD') ?: 'daw_password';

$pdoConnected = false;
$errorMessage = null;
$mysqlVersion = null;
$visitasCount = 0;

// Intentar conexión a MySQL mediante PDO
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    $pdoConnected = true;

    // Obtener versión de MySQL
    $stmt = $pdo->query("SELECT VERSION() AS version");
    $mysqlVersion = $stmt->fetch()['version'] ?? 'Desconocida';

    // Crear tabla de prueba si no existe
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registro_visitas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Registrar la visita actual
    $insertStmt = $pdo->prepare("INSERT INTO registro_visitas (ip, user_agent) VALUES (:ip, :ua)");
    $insertStmt->execute([
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ':ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido', 0, 255),
    ]);

    // Contar total de registros
    $countStmt = $pdo->query("SELECT COUNT(*) AS total FROM registro_visitas");
    $visitasCount = (int) ($countStmt->fetch()['total'] ?? 0);

} catch (PDOException $e) {
    $pdoConnected = false;
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Práctica 1 (UD1) — Entorno de Desarrollo con Docker</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #fff;
            color: #111;
            font-family: Arial, sans-serif;
        }

        .container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 24px;
        }

        .badge-header,
        p.subtitle,
        footer {
            color: #444;
        }

        .badge-header {
            display: block;
            margin-bottom: 8px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.8rem;
        }

        p.subtitle {
            margin: 0;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .card,
        .connection-status,
        .alert-box {
            border: 1px solid #bbb;
            border-radius: 0;
            background: #fff;
            padding: 16px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .card-title {
            font-weight: bold;
        }

        .status-pill {
            font-size: 0.85rem;
        }

        .status-ok {
            color: #176b2c;
        }

        .status-error {
            color: #a00000;
        }

        .status-dot {
            display: none;
        }

        .info-list {
            display: grid;
            gap: 6px;
            margin: 0;
            padding-left: 20px;
            font-size: 0.9rem;
        }

        .connection-status {
            margin-bottom: 24px;
        }

        .connection-status h3 {
            margin-top: 0;
        }

        .alert-box {
            margin-top: 12px;
            line-height: 1.5;
        }

        .alert-success {
            border-color: #176b2c;
        }

        .alert-danger {
            border-color: #a00000;
        }

        footer {
            border-top: 1px solid #bbb;
            padding-top: 12px;
            font-size: 0.85rem;
        }

        code {
            padding: 2px 4px;
            background: #f2f2f2;
            font-family: monospace;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .card-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="badge-header">0613 · Desarrollo Web en Entorno Servidor · 2º DAW</div>
            <h1>Práctica 1 (UD1): Entorno Docker</h1>
            <p class="subtitle">Arquitectura de tres contenedores orquestados con Docker Compose</p>
        </header>

        <div class="grid">
            <!-- Contenedor 1: Web (Nginx) -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Servidor Web</span>
                    <span class="status-pill status-ok">
                        <span class="status-dot"></span> Online
                    </span>
                </div>
                <ul class="info-list">
                    <li><strong>Contenedor:</strong> <code>web</code> (Nginx Alpine)</li>
                    <li><strong>Puerto expuesto:</strong> <code>8080</code> (Host) &rarr; <code>80</code> (Container)</li>
                    <li><strong>Software:</strong> <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Nginx') ?></li>
                    <li><strong>Protocolo:</strong> <?= htmlspecialchars($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1') ?></li>
                </ul>
            </div>

            <!-- Contenedor 2: PHP (PHP-FPM 8.3) -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Intérprete PHP</span>
                    <span class="status-pill status-ok">
                        <span class="status-dot"></span> PHP <?= PHP_VERSION ?>
                    </span>
                </div>
                <ul class="info-list">
                    <li><strong>Contenedor:</strong> <code>php</code> (PHP-FPM 8.3)</li>
                    <li><strong>Versión exacta:</strong> PHP <?= phpversion() ?></li>
                    <li><strong>SAPI:</strong> <?= php_sapi_name() ?></li>
                    <li><strong>PDO MySQL:</strong> <?= extension_loaded('pdo_mysql') ? 'Instalado' : 'Falta' ?></li>
                </ul>
            </div>

            <!-- Contenedor 3: Base de Datos (MySQL) -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Base de Datos</span>
                    <?php if ($pdoConnected): ?>
                        <span class="status-pill status-ok">
                            <span class="status-dot"></span> Conectado
                        </span>
                    <?php else: ?>
                        <span class="status-pill status-error">
                            <span class="status-dot"></span> Desconectado
                        </span>
                    <?php endif; ?>
                </div>
                <ul class="info-list">
                    <li><strong>Contenedor:</strong> <code>db</code> (MySQL 8.0)</li>
                    <li><strong>Host / Puerto:</strong> <code><?= htmlspecialchars($dbHost) ?>:<?= htmlspecialchars($dbPort) ?></code></li>
                    <li><strong>Base de datos:</strong> <code><?= htmlspecialchars($dbName) ?></code></li>
                    <li><strong>Versión MySQL:</strong> <?= htmlspecialchars($mysqlVersion ?? 'Sin conexión') ?></li>
                </ul>
            </div>
        </div>

        <!-- Sección de Verificación de Conexión PDO -->
        <div class="connection-status">
            <h3>Verificación de Conexión PDO MySQL</h3>
            <?php if ($pdoConnected): ?>
                <div class="alert-box alert-success">
                    <strong>¡Conexión establecida con éxito!</strong><br>
                    </div>
            <?php else: ?>
                <div class="alert-box alert-danger">
                    <strong>Error al conectar con la base de datos:</strong><br>
                    <code><?= htmlspecialchars($errorMessage ?? 'Error desconocido') ?></code><br><br>
                    <em>Nota: Si acabas de levantar los contenedores, MySQL puede tardar unos segundos en inicializar la base de datos por primera vez. Recarga la página en unos instantes.</em>
                </div>
            <?php endif; ?>
        </div>

        <footer>
            <p>Práctica 1 (UD1) — Arranque del entorno de desarrollo con Docker · 2º DAW A</p>
        </footer>
    </div>
</body>
</html>
