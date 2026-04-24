<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verificación de instalación — TimeClock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 p-8">
<div class="max-w-2xl mx-auto">
  <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-3">
    <span class="text-3xl">🕐</span> TimeClock — Verificación de instalación
  </h1>

  <?php
  $checks = [];

  // PHP version
  $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
  $checks[] = ['PHP >= 8.0', $phpOk, 'PHP ' . PHP_VERSION];

  // Extensiones
  foreach (['pdo', 'pdo_mysql', 'mbstring', 'json', 'openssl'] as $ext) {
    $checks[] = ["Extensión $ext", extension_loaded($ext), extension_loaded($ext) ? 'OK' : 'No disponible'];
  }

  // Zona horaria
  $tzOk = date_default_timezone_get() === 'America/Bogota';
  $checks[] = ['Zona horaria Bogotá', $tzOk, date_default_timezone_get()];

  // Conexión a BD
  require_once dirname(__DIR__) . '/config/database.php';
  try {
    $pdo = new PDO(
      "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
      DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $checks[] = ['Conexión MySQL', true, 'Conectado a ' . DB_NAME];

    // Verificar tablas
    $tables = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
    $checks[] = ['Tabla usuarios', !empty($tables), !empty($tables) ? 'Existe' : 'No existe — ejecutar schema.sql'];
  } catch (\Exception $e) {
    $checks[] = ['Conexión MySQL', false, $e->getMessage()];
    $checks[] = ['Tabla usuarios', false, 'Sin conexión'];
  }

  // Directorio logs
  $logsDir = dirname(__DIR__) . '/logs';
  $logsOk  = is_dir($logsDir) && is_writable($logsDir);
  if (!is_dir($logsDir)) @mkdir($logsDir, 0750, true);
  $checks[] = ['Directorio logs writable', is_writable($logsDir), $logsOk ? $logsDir : 'No escribible — verificar permisos'];

  $allOk = array_reduce($checks, fn($carry, $c) => $carry && $c[1], true);
  ?>

  <div class="space-y-2 mb-8">
    <?php foreach ($checks as [$label, $ok, $detail]): ?>
    <div class="flex items-center gap-3 p-3 rounded-lg <?= $ok ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' ?>">
      <span class="text-lg"><?= $ok ? '✅' : '❌' ?></span>
      <div class="flex-1">
        <p class="font-medium text-sm <?= $ok ? 'text-green-800' : 'text-red-800' ?>"><?= htmlspecialchars($label) ?></p>
        <p class="text-xs <?= $ok ? 'text-green-600' : 'text-red-600' ?>"><?= htmlspecialchars($detail) ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($allOk): ?>
  <div class="p-5 bg-indigo-600 rounded-xl text-white text-center">
    <p class="text-xl font-bold mb-2">✅ Sistema listo para usar</p>
    <a href="../login.php" class="inline-block mt-2 px-5 py-2 bg-white text-indigo-700 rounded-lg font-semibold hover:bg-indigo-50">
      Ir al login →
    </a>
  </div>
  <?php else: ?>
  <div class="p-5 bg-red-50 border border-red-200 rounded-xl text-red-700">
    <p class="font-bold">⚠ Corrija los errores antes de continuar</p>
    <ul class="mt-2 text-sm list-disc pl-5 space-y-1">
      <li>Edite <code>config/database.php</code> con sus credenciales</li>
      <li>Importe <code>database/schema.sql</code> en MySQL</li>
      <li>Verifique que las extensiones PHP requeridas estén activas</li>
    </ul>
  </div>
  <?php endif; ?>

  <p class="text-xs text-gray-400 mt-6 text-center">
    ⚠ Elimine este archivo después de la instalación: <code>install/check.php</code>
  </p>
</div>
</body>
</html>
