<?php
/**
 * MathPlay Solutions — API e Rotina CLI para Auditoria Mensal de Recuperação
 * 
 * Execução manual via navegador:
 *   Requer login de professor ou token secreto:
 *   GET /vortex/api/monthly_recovery_check.php?token=SEU_TOKEN
 * 
 * Execução automática via terminal / Task Scheduler:
 *   php C:\xampp\htdocs\vortex\api\monthly_recovery_check.php
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/recovery_audit.php';

$isCli = (php_sapi_name() === 'cli' || empty($_SERVER['REMOTE_ADDR']));

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}

// 1. Verificação de Autenticação / Permissão
$authorized = false;

if ($isCli) {
    $authorized = true;
} elseif (isLoggedIn() && isTeacher()) {
    $authorized = true;
} else {
    // Verificar token secreto para automações de cron/agendadores externos
    $envCfg = file_exists(__DIR__ . '/../.env') ? (parse_ini_file(__DIR__ . '/../.env') ?: []) : [];
    $secretToken = trim($envCfg['INSTALL_TOKEN'] ?? '');
    $providedToken = $_GET['token'] ?? ($_POST['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));

    if (!empty($secretToken) && !empty($providedToken) && hash_equals($secretToken, $providedToken)) {
        $authorized = true;
    }
}

if (!$authorized) {
    if (!$isCli) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error'   => 'Acesso negado. Requer autenticação de professor ou token válido.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "Acesso negado: permissao insuficiente.\n";
    }
    exit;
}

// 2. Parâmetros de execução
$force = isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true');
if ($isCli) {
    // Suporte a flags CLI: --force e --month=YYYY-MM
    global $argv;
    if (isset($argv) && is_array($argv)) {
        foreach ($argv as $arg) {
            if ($arg === '--force') $force = true;
            if (str_starts_with($arg, '--month=')) {
                $monthParam = explode('=', $arg)[1];
            }
        }
    }
}

$targetMonth = $_GET['month'] ?? ($monthParam ?? date('Y-m'));

// Validar formato YYYY-MM
if (!preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
    $targetMonth = date('Y-m');
}

// 3. Executar a auditoria mensal
try {
    $result = runMonthlyRecoveryAudit($pdo, $targetMonth, $force);

    $response = [
        'success'              => true,
        'message'              => $result['already_run'] 
            ? "A auditoria de {$targetMonth} já havia sido executada anteriormente. Use ?force=1 para reexecutar."
            : "Auditoria de {$targetMonth} executada com sucesso!",
        'audit_month'          => $result['audit_month'],
        'already_run'          => $result['already_run'],
        'executed_at'          => $result['executed_at'],
        'students_audited'     => $result['students_audited'],
        'students_in_recovery' => $result['students_in_recovery'],
        'recovery_list'        => $result['details']
    ];

    if ($isCli) {
        echo "\n=======================================================\n";
        echo " MATHPLAY SOLUTIONS — AUDITORIA MENSAL DE RECUPERAÇÃO\n";
        echo "=======================================================\n";
        echo " Mês de Referência:     {$response['audit_month']}\n";
        echo " Data de Execução:      {$response['executed_at']}\n";
        echo " Alunos Auditados:      {$response['students_audited']}\n";
        echo " Alunos em Recuperação: {$response['students_in_recovery']}\n";
        echo " Status:                " . ($response['already_run'] ? "Registro já existente" : "Novo processamento concluído") . "\n";
        echo "-------------------------------------------------------\n";
        if (!empty($response['recovery_list'])) {
            echo " Alunos identificados para recuperação:\n";
            foreach ($response['recovery_list'] as $stu) {
                echo "   • {$stu['student_name']} ({$stu['priority_topic']}) - Erro: {$stu['overall_error']}%\n";
            }
        } else {
            echo " Nenhum aluno em situação de recuperação identificada.\n";
        }
        echo "=======================================================\n\n";
    } else {
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    if (!$isCli) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Erro interno ao processar auditoria mensal: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "ERRO: " . $e->getMessage() . "\n";
    }
}
