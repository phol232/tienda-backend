<?php
try {
    $host = '127.0.0.1';
    $port = 3010;
    $dbname = 'Tienda';
    $user = 'root';
    $pass = 'root123456';

    echo "Intentando conectar a MySQL...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "¡Conexión exitosa!\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM Movimientos_Inventario");
    $count = $stmt->fetchColumn();
    echo "Hay $count registros en Movimientos_Inventario\n";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
}
?>
