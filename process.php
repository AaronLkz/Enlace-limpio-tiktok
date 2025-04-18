<?php
header('Content-Type: application/json');

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener y validar la URL
if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode(['error' => 'Por favor, introduce un enlace de TikTok']);
    exit;
}

$url = trim($_POST['url']);

// Validar que sea un enlace TikTok
if (strpos($url, 'tiktok.com') === false && strpos($url, 'vm.tiktok.com') === false && strpos($url, 'vt.tiktok.com') === false) {
    echo json_encode(['error' => 'Por favor, introduce un enlace válido de TikTok (tiktok.com, vm.tiktok.com o vt.tiktok.com)']);
    exit;
}

// Función para obtener la URL final
function getFinalUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Error cURL: $error");
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception("El servidor respondió con código $httpCode");
    }
    
    return $finalUrl;
}

try {
    // Obtener URL final
    $finalUrl = getFinalUrl($url);
    
    // Parsear la URL
    $parsed = parse_url($finalUrl);
    
    if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
        throw new Exception("No se pudo analizar la URL final");
    }
    
    // Construir URL limpia
    $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'] . ($parsed['path'] ?? '');
    
    // Validar que la URL limpia sea de TikTok
    if (strpos($cleanUrl, 'tiktok.com') === false) {
        throw new Exception("El enlace redirige a un dominio no permitido");
    }
    
    echo json_encode([
        'cleanUrl' => $cleanUrl,
        'originalUrl' => $url
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error al procesar el enlace: ' . $e->getMessage()]);
}
?>
