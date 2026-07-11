public static function gerarNumeroRecibo(PDO $pdo, int $abertura_id): int
{
    $pdo->beginTransaction();

    // bloqueia linha (evita duplicação em multi-caixa)
    $stmt = $pdo->prepare("
        SELECT ultimo_numero 
        FROM caixa_recibos 
        WHERE abertura_id = ? 
        FOR UPDATE
    ");
    $stmt->execute([$abertura_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {

        $numero = $row['ultimo_numero'] + 1;

        $stmt = $pdo->prepare("
            UPDATE caixa_recibos 
            SET ultimo_numero = ? 
            WHERE abertura_id = ?
        ");
        $stmt->execute([$numero, $abertura_id]);

    } else {

        $numero = 1;

        $stmt = $pdo->prepare("
            INSERT INTO caixa_recibos (abertura_id, ultimo_numero)
            VALUES (?, ?)
        ");
        $stmt->execute([$abertura_id, $numero]);
    }

    $pdo->commit();

    return $numero;
}