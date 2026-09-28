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
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
            --accent-blue: #3b82f6;
            --accent-cyan: #06b6d4;
            --border-color: #334155;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            padding: 2.5rem 1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            max-width: 900px;
            width: 100%;
        }

        header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .badge-header {
            display: inline-block;
            background: rgba(59, 130, 246, 0.15);
            color: var(--accent-blue);
            border: 1px solid rgba(59, 130, 246, 0.3);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #60a5fa, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            border-color: #475569;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-pill {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .status-ok {
            background-color: rgba(34, 197, 94, 0.15);
            color: var(--accent-green);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .status-error {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--accent-red);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .info-list {
            list-style: none;
            font-size: 0.9rem;
            color: var(--text-muted);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-list strong {
            color: var(--text-color);
        }

        .connection-status {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-box {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        footer {
            text-align: center;
            margin-top: auto;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            width: 100%;
        }

        code {
            background: #090d16;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
            color: #38bdf8;
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
                    <span class="card-title">🌐 Servidor Web</span>
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
                    <span class="card-title">🐘 Intérprete PHP</span>
                    <span class="status-pill status-ok">
                        <span class="status-dot"></span> PHP <?= PHP_VERSION ?>
                    </span>
                </div>
                <ul class="info-list">
                    <li><strong>Contenedor:</strong> <code>php</code> (PHP-FPM 8.3)</li>
                    <li><strong>Versión exacta:</strong> PHP <?= phpversion() ?></li>
                    <li><strong>SAPI:</strong> <?= php_sapi_name() ?></li>
                    <li><strong>PDO MySQL:</strong> <?= extension_loaded('pdo_mysql') ? '✅ Instalado' : '❌ Falta' ?></li>
                </ul>
            </div>

            <!-- Contenedor 3: Base de Datos (MySQL) -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">🗄️ Base de Datos</span>
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
            <h3>🔌 Verificación de Conexión PDO MySQL</h3>
            <?php if ($pdoConnected): ?>
                <div class="alert-box alert-success">
                    <strong>¡Conexión establecida con éxito!</strong><br>
                    El script PHP ha establecido comunicación con el contenedor <code>db</code> mediante la extensión <strong>PDO</strong>.<br>
                    Se ejecutaron operaciones de lectura y escritura en la tabla <code>registro_visitas</code>.<br>
                    Visitas acumuladas registradas en la base de datos: <strong><?= $visitasCount ?></strong>.
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
