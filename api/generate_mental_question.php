<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida']);
    exit;
}

$data = !empty($data) ? $data : (json_decode(file_get_contents('php://input'), true) ?? []);
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? '');
if (!$csrfToken || !verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Sessao invalida']);
    exit;
}

$gameId = (int)($data['game_id'] ?? 3);
$level = max(1, (int)($data['level'] ?? 1));

$questionText = '';
$answer = 0;
$difficulty = 'easy';
$levelName = 'Fácil';

if (!function_exists('getAdditionExplanation')) {
function getAdditionExplanation(int $a, int $b, int $ans): string {
    $bMod = $b % 10;
    $aMod = $a % 10;
    if ($bMod >= 7) {
        $rounded = (int)(ceil($b / 10) * 10);
        $diff = $rounded - $b;
        $step1 = $a + $rounded;
        return "💡 Técnica da Compensação: $a + $b = $a + $rounded − $diff = $step1 − $diff = $ans.";
    }
    if ($aMod >= 7 && $a >= 10) {
        $rounded = (int)(ceil($a / 10) * 10);
        $diff = $rounded - $a;
        $step1 = $b + $rounded;
        return "💡 Técnica da Compensação: $a + $b = $b + $rounded − $diff = $step1 − $diff = $ans.";
    }
    if ($a >= 10 || $b >= 10) {
        $aTens = (int)($a / 10) * 10;
        $aUnits = $a % 10;
        $bTens = (int)($b / 10) * 10;
        $bUnits = $b % 10;
        $tensSum = $aTens + $bTens;
        $unitsSum = $aUnits + $bUnits;
        return "💡 Decomposição: some dezenas ($aTens + $bTens = $tensSum) e unidades ($aUnits + $bUnits = $unitsSum) → $tensSum + $unitsSum = $ans.";
    }
    return "💡 Contagem Ágil: comece no " . max($a, $b) . " e some " . min($a, $b) . " = $ans.";
}

function getSubtractionExplanation(int $a, int $b, int $ans): string {
    $bMod = $b % 10;
    if ($bMod >= 6 && $b >= 10) {
        $rounded = (int)(ceil($b / 10) * 10);
        $diff = $rounded - $b;
        $step1 = $a - $rounded;
        return "💡 Técnica da Compensação: $a − $b = $a − $rounded + $diff = $step1 + $diff = $ans.";
    }
    if ($b >= 10) {
        $bTens = (int)($b / 10) * 10;
        $bUnits = $b % 10;
        $step1 = $a - $bTens;
        return "💡 Subtração em 2 Etapas: subtraia a dezena ($a − $bTens = $step1) e depois a unidade ($step1 − $bUnits = $ans).";
    }
    return "💡 Operação Inversa: quanto falta de $b para $a? $b + $ans = $a, portanto $a − $b = $ans.";
}

function getMultiplicationExplanation(int $a, int $b, int $ans): string {
    if ($a === 2 || $b === 2) {
        $num = ($a === 2) ? $b : $a;
        return "💡 Técnica do Dobro: multiplicar por 2 é somar o número consigo mesmo ($num + $num = $ans).";
    }
    if ($a === 3 || $b === 3) {
        $num = ($a === 3) ? $b : $a;
        $double = $num * 2;
        return "💡 Técnica do Dobro + 1x: calcule o dobro ($num × 2 = $double) e some mais $num ($double + $num = $ans).";
    }
    if ($a === 4 || $b === 4) {
        $num = ($a === 4) ? $b : $a;
        $double = $num * 2;
        return "💡 Técnica do Dobro do Dobro: dobro de $num é $double, e dobro de $double é $ans.";
    }
    if ($a === 5 || $b === 5) {
        $num = ($a === 5) ? $b : $a;
        $x10 = $num * 10;
        return "💡 Regra do 5 (Metade de 10): multiplique por 10 ($num × 10 = $x10) e divida pela metade ($x10 ÷ 2 = $ans).";
    }
    if ($a === 9 || $b === 9) {
        $num = ($a === 9) ? $b : $a;
        $x10 = $num * 10;
        return "💡 Regra do 9: multiplique por 10 e subtraia 1x ($num × 10 − $num = $x10 − $num = $ans).";
    }
    if ($a === 25 || $b === 25) {
        $num = ($a === 25) ? $b : $a;
        $x100 = $num * 100;
        return "💡 Regra do 25 (100 ÷ 4): faça ($num × 100) ÷ 4 = $x100 ÷ 4 = $ans.";
    }
    if ($a >= 11 && $a <= 19) {
        $step1 = 10 * $b;
        $step2 = ($a - 10) * $b;
        return "💡 Decomposição: (10 × $b = $step1) + (" . ($a - 10) . " × $b = $step2) → $step1 + $step2 = $ans.";
    }
    if ($b >= 11 && $b <= 19) {
        $step1 = 10 * $a;
        $step2 = ($b - 10) * $a;
        return "💡 Decomposição: ($a × 10 = $step1) + ($a × " . ($b - 10) . " = $step2) → $step1 + $step2 = $ans.";
    }
    return "💡 Tabuada & Proporção: $a × $b = $ans.";
}

function getDivisionExplanation(int $dividend, int $divisor, int $quotient): string {
    if ($divisor === 2) {
        return "💡 Metade Exata: a metade de $dividend é $quotient ($quotient + $quotient = $dividend).";
    }
    if ($divisor === 4) {
        $half = (int)($dividend / 2);
        return "💡 Metade da Metade: a metade de $dividend é $half, e a metade de $half é $quotient.";
    }
    if ($divisor === 5) {
        $double = $dividend * 2;
        return "💡 Divisão por 5: dobre o valor ($dividend × 2 = $double) e divida por 10 ($double ÷ 10 = $quotient).";
    }
    return "💡 Operação Inversa: pense qual número vezes $divisor resulta em $dividend ($divisor × $quotient = $dividend). Portanto, $dividend ÷ $divisor = $quotient.";
}
}

if ($level === 1) {
    // NÍVEL 1: FÁCIL (Questões 1 a 3) - Números pequenos (2 a 15), tabuada básica
    $difficulty = 'easy';
    $levelName = 'Fácil';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(2, 15);
        $b = random_int(2, 12);
        $questionText = "$a + $b";
        $answer = $a + $b;
        $explanation = getAdditionExplanation($a, $b, $answer);
    } elseif ($op === 2) {
        $b = random_int(2, 10);
        $a = $b + random_int(2, 12);
        $questionText = "$a - $b";
        $answer = $a - $b;
        $explanation = getSubtractionExplanation($a, $b, $answer);
    } elseif ($op === 3) {
        $a = random_int(2, 5);
        $b = random_int(2, 9);
        $questionText = "$a × $b";
        $answer = $a * $b;
        $explanation = getMultiplicationExplanation($a, $b, $answer);
    } else {
        $divisor = random_int(2, 5);
        $quotient = random_int(2, 8);
        $dividend = $divisor * $quotient;
        $questionText = "$dividend ÷ $divisor";
        $answer = $quotient;
        $explanation = getDivisionExplanation($dividend, $divisor, $quotient);
    }
} elseif ($level === 2) {
    // NÍVEL 2: MÉDIO (Questões 4 a 6) - Dois dígitos moderados, tabuada do 6 ao 9
    $difficulty = 'medium';
    $levelName = 'Médio';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(15, 45);
        $b = random_int(12, 35);
        $questionText = "$a + $b";
        $answer = $a + $b;
        $explanation = getAdditionExplanation($a, $b, $answer);
    } elseif ($op === 2) {
        $b = random_int(15, 40);
        $a = $b + random_int(10, 35);
        $questionText = "$a - $b";
        $answer = $a - $b;
        $explanation = getSubtractionExplanation($a, $b, $answer);
    } elseif ($op === 3) {
        $a = random_int(6, 9);
        $b = random_int(4, 9);
        $questionText = "$a × $b";
        $answer = $a * $b;
        $explanation = getMultiplicationExplanation($a, $b, $answer);
    } else {
        $divisor = random_int(6, 9);
        $quotient = random_int(3, 9);
        $dividend = $divisor * $quotient;
        $questionText = "$dividend ÷ $divisor";
        $answer = $quotient;
        $explanation = getDivisionExplanation($dividend, $divisor, $quotient);
    }
} elseif ($level === 3) {
    // NÍVEL 3: DIFÍCIL (Questões 7 a 10) - Cálculos com números maiores
    $difficulty = 'hard';
    $levelName = 'Difícil';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(45, 95);
        $b = random_int(25, 85);
        $questionText = "$a + $b";
        $answer = $a + $b;
        $explanation = getAdditionExplanation($a, $b, $answer);
    } elseif ($op === 2) {
        $b = random_int(35, 75);
        $a = $b + random_int(40, 85);
        $questionText = "$a - $b";
        $answer = $a - $b;
        $explanation = getSubtractionExplanation($a, $b, $answer);
    } elseif ($op === 3) {
        $candidates = [
            [12, random_int(4, 8)],
            [13, random_int(3, 6)],
            [14, random_int(3, 5)],
            [15, random_int(3, 6)],
            [25, random_int(2, 4)],
            [20, random_int(3, 7)]
        ];
        $chosen = $candidates[array_rand($candidates)];
        $a = $chosen[0];
        $b = $chosen[1];
        $questionText = "$a × $b";
        $answer = $a * $b;
        $explanation = getMultiplicationExplanation($a, $b, $answer);
    } else {
        $divPairs = [
            [84, 4, 21], [96, 3, 32], [75, 3, 25], [100, 4, 25],
            [90, 6, 15], [80, 5, 16], [72, 4, 18], [65, 5, 13]
        ];
        $pair = $divPairs[array_rand($divPairs)];
        $questionText = "{$pair[0]} ÷ {$pair[1]}";
        $answer = $pair[2];
        $explanation = getDivisionExplanation($pair[0], $pair[1], $pair[2]);
    }
} elseif ($level === 4) {
    // NÍVEL 4: ESPECIALISTA (Questões 11 a 15)
    $difficulty = 'hard';
    $levelName = 'Especialista';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(85, 175);
        $b = random_int(45, 135);
        $questionText = "$a + $b";
        $answer = $a + $b;
        $explanation = getAdditionExplanation($a, $b, $answer);
    } elseif ($op === 2) {
        $b = random_int(65, 140);
        $a = $b + random_int(50, 160);
        $questionText = "$a - $b";
        $answer = $a - $b;
        $explanation = getSubtractionExplanation($a, $b, $answer);
    } elseif ($op === 3) {
        $candidates = [[15, 6], [15, 8], [25, 4], [25, 6], [16, 5], [18, 4], [14, 6], [30, 4], [12, 12]];
        $c = $candidates[array_rand($candidates)];
        $questionText = "{$c[0]} × {$c[1]}";
        $answer = $c[0] * $c[1];
        $explanation = getMultiplicationExplanation($c[0], $c[1], $answer);
    } else {
        $divPairs = [[144, 12, 12], [150, 6, 25], [180, 6, 30], [240, 6, 40], [200, 8, 25], [210, 7, 30]];
        $p = $divPairs[array_rand($divPairs)];
        $questionText = "{$p[0]} ÷ {$p[1]}";
        $answer = $p[2];
        $explanation = getDivisionExplanation($p[0], $p[1], $p[2]);
    }
} elseif ($level === 5) {
    // NÍVEL 5: MESTRE (Questões 16 a 20)
    $difficulty = 'hard';
    $levelName = 'Mestre';
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(150, 380);
        $b = random_int(120, 290);
        $questionText = "$a + $b";
        $answer = $a + $b;
        $explanation = getAdditionExplanation($a, $b, $answer);
    } elseif ($op === 2) {
        $b = random_int(120, 280);
        $a = $b + random_int(100, 320);
        $questionText = "$a - $b";
        $answer = $a - $b;
        $explanation = getSubtractionExplanation($a, $b, $answer);
    } elseif ($op === 3) {
        $candidates = [[35, 4], [45, 3], [15, 12], [11, 15], [50, 5], [25, 8], [40, 6]];
        $c = $candidates[array_rand($candidates)];
        $questionText = "{$c[0]} × {$c[1]}";
        $answer = $c[0] * $c[1];
        $explanation = getMultiplicationExplanation($c[0], $c[1], $answer);
    } else {
        $divPairs = [[360, 9, 40], [350, 7, 50], [400, 8, 50], [280, 4, 70], [300, 15, 20], [225, 15, 15]];
        $p = $divPairs[array_rand($divPairs)];
        $questionText = "{$p[0]} ÷ {$p[1]}";
        $answer = $p[2];
        $explanation = getDivisionExplanation($p[0], $p[1], $p[2]);
    }
} else {
    // NÍVEL 6+: LENDA DA MATEMÁTICA (Questões 21+)
    $difficulty = 'hard';
    $levelName = "Lenda (Nível $level)";
    $op = random_int(1, 4);
    if ($op === 1) {
        $a = random_int(250, 600);
        $b = random_int(200, 550);
        $questionText = "$a + $b";
        $answer = $a + $b;
        $explanation = getAdditionExplanation($a, $b, $answer);
    } elseif ($op === 2) {
        $b = random_int(250, 500);
        $a = $b + random_int(200, 500);
        $questionText = "$a - $b";
        $answer = $a - $b;
        $explanation = getSubtractionExplanation($a, $b, $answer);
    } elseif ($op === 3) {
        $candidates = [[75, 4], [125, 2], [15, 15], [50, 8], [60, 7], [25, 12], [80, 5]];
        $c = $candidates[array_rand($candidates)];
        $questionText = "{$c[0]} × {$c[1]}";
        $answer = $c[0] * $c[1];
        $explanation = getMultiplicationExplanation($c[0], $c[1], $answer);
    } else {
        $divPairs = [[500, 25, 20], [600, 15, 40], [480, 12, 40], [720, 8, 90], [800, 20, 40], [630, 9, 70]];
        $p = $divPairs[array_rand($divPairs)];
        $questionText = "{$p[0]} ÷ {$p[1]}";
        $answer = $p[2];
        $explanation = getDivisionExplanation($p[0], $p[1], $p[2]);
    }
}

$stmt = $pdo->prepare('INSERT INTO questions (game_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, difficulty, is_ai_generated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)');
$stmt->execute([$gameId, 'Operacoes Basicas', $questionText, '', '', '', '', (string)$answer, $explanation, $difficulty]);

$question = [
    'id' => (int)$pdo->lastInsertId(),
    'question_text' => $questionText,
    'difficulty' => $difficulty,
    'level' => $level,
    'level_name' => $levelName
];
echo json_encode(['success' => true, 'question' => $question]);
