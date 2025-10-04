<?php
// func/security.php

/**
 * Detector avançado de spam
 */
class SpamDetector
{
    private $spamKeywords = [
        // Mantenha apenas palavras realmente suspeitas
        'BTC',
        'bitcoin',
        'crypto',
        'unclaimed',
        'free money',
        'winning',
        'viagra',
        'cialis',
        'pharmacy',
        'casino',
        'porn',
        'sex',
        'drugs',
        'graph.org',
        'zikzak.gq',
        'tempr.email',
        'yopmail',
        '<script',
        'javascript:',
        'onload=',
        'onclick='
        // '.com', '.org', '.net', 'http://', etc.
    ];

    public function containsSpam($text)
    {
        if (!is_string($text) || empty(trim($text)))
            return false;

        $textLower = strtolower($text);

        foreach ($this->spamKeywords as $keyword) {
            if (stripos($textLower, strtolower($keyword)) !== false) {
                $this->logSpamAttempt($text, "KEYWORD: $keyword");
                return true;
            }
        }

        return false;
    }

    private function logSpamAttempt($text, $reason)
    {
        $logFile = __DIR__ . '/../logs/security/spam_detection.log';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        $logData = [
            'timestamp' => date('c'),
            'ip' => $ip,
            'reason' => $reason,
            'suspicious_text' => substr($text, 0, 200)
        ];

        $logMessage = "[SPAM] " . json_encode($logData) . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Proteção CSRF
 */
class CSRFProtection
{
    public static function gerarToken($nome = 'csrf_default')
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_' . $nome] = $token;
        $_SESSION['csrf_' . $nome . '_expira'] = time() + 1800;
        return $token;
    }

    public static function verificarToken($token, $nome = 'csrf_default')
    {
        if (
            !isset($_SESSION['csrf_' . $nome]) ||
            !isset($_SESSION['csrf_' . $nome . '_expira']) ||
            time() > $_SESSION['csrf_' . $nome . '_expira']
        ) {
            return false;
        }

        return hash_equals($_SESSION['csrf_' . $nome], $token);
    }
}

/**
 * Rate Limiting
 */
//class RateLimiter {
//    private $pdo;
//    
//    public function __construct($pdo) {
//        $this->pdo = $pdo;
//    }
//    
//    public function checkLimit($userId, $action, $maxAttempts = 5, $timeWindow = 3600) {
//        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
//        
//        // Contar tentativas recentes
//        $query = "SELECT COUNT(*) as attempts 
//                  FROM rate_limiting 
//                  WHERE (user_id = :user_id OR ip = :ip) 
//                  AND action = :action 
//                  AND timestamp > DATE_SUB(NOW(), INTERVAL :seconds SECOND)";
//        
//        $stmt = $this->pdo->prepare($query);
//        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
//        $stmt->bindValue(":ip", $ip);
//        $stmt->bindValue(":action", $action);
//        $stmt->bindValue(":seconds", $timeWindow, PDO::PARAM_INT);
//        $stmt->execute();
//        
//        $result = $stmt->fetch(PDO::FETCH_OBJ);
//        
//        if ($result->attempts >= $maxAttempts) {
//            throw new Exception("Muitas tentativas. Tente novamente em " . ceil($timeWindow/60) . " minutos.");
//        }
//        
//        // Registrar tentativa atual
//        $this->logAttempt($userId, $ip, $action);
//        
//        return true;
//    }
//    
//    private function logAttempt($userId, $ip, $action) {
//        $query = "INSERT INTO rate_limiting (user_id, ip, action, timestamp) 
//                  VALUES (:user_id, :ip, :action, NOW())";
//        $stmt = $this->pdo->prepare($query);
//        $stmt->bindValue(":user_id", $userId, PDO::PARAM_INT);
//        $stmt->bindValue(":ip", $ip);
//        $stmt->bindValue(":action", $action);
//        $stmt->execute();
//    }
//}


/**
 * Logger para validações de segurança
 */
class SecurityValidationLogger
{
    private $logFile;

    public function __construct()
    {
        $this->logFile = __DIR__ . '/../logs/security/validation.log';

        // Garantir que o diretório existe
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    private function logEvent($eventType, $details, $userId = null)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $sessionId = session_id();

        $logData = [
            'timestamp' => date('c'),
            'event_type' => $eventType,
            'ip' => $ip,
            'session_id' => $sessionId,
            'user_id' => $userId ?? ($_SESSION['id'] ?? 'NOT_LOGGED_IN'),
            'details' => $details
        ];

        $logMessage = "[SECURITY_VALIDATION] " . json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public function logPermissionFailure()
    {
        $details = [
            'reason' => 'USER_NOT_ADMIN',
            'user_admin_flag' => $_SESSION['admin'] ?? 'NOT_SET',
            'required_level' => 1
        ];

        $this->logEvent('UNAUTHORIZED_ADMIN_ACCESS', $details, $_SESSION['id'] ?? null);
    }

    public function logIdentityFailure($requestedUserId, $reason)
    {
        $details = [
            'reason' => $reason,
            'requested_user_id' => $requestedUserId,
            'session_user_id' => $_SESSION['id'] ?? 'NOT_SET'
        ];

        $this->logEvent('IDENTITY_VALIDATION_FAILED', $details, $requestedUserId);
    }

    public function logSessionHijacking($requestedUserId, $dbUserId)
    {
        $details = [
            'reason' => 'SESSION_USER_ID_MISMATCH',
            'session_user_id' => $_SESSION['id'],
            'database_user_id' => $dbUserId
        ];

        $this->logEvent('SESSION_HIJACKING_SUSPECTED', $details, $requestedUserId);
    }
}


/**
 * Validação de permissões
 */
function validarPermissaoAdmin()
{
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
        $logger = new SecurityValidationLogger();
        $logger->logPermissionFailure();
        throw new Exception("Acesso restrito a administradores");
    }
    return true;
}

function validarIdentidadeUsuario($userId)
{
    require_once __DIR__ . "/database.php";

    $conn = new Conexao();
    $pdo = $conn->conectar();

    $query = "SELECT id, nome, email, validado FROM atleta WHERE id = :id AND validado = 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(":id", $userId, PDO::PARAM_INT);
    $stmt->execute();

    $usuario = $stmt->fetch(PDO::FETCH_OBJ);

    if (!$usuario) {
        $logger = new SecurityValidationLogger();
        $logger->logIdentityFailure($userId, 'USER_NOT_FOUND_OR_NOT_VALIDATED');
        throw new Exception("Usuário não encontrado ou não validado");
    }

    if ($_SESSION['id'] != $usuario->id) {
        $logger = new SecurityValidationLogger();
        $logger->logSessionHijacking($userId, $usuario->id);

        session_destroy();
        throw new Exception("Inconsistência de dados detectada");
    }

    return $usuario;
}
?>