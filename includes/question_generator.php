<?php
/**
 * MathPlay - Motor Algorítmico de Geração Procedural de Questões
 * 
 * Gera perguntas aleatórias e infinitas para Chef das Frações, Construtor de Cidades
 * e Missão de Reforço, com dificuldade adaptativa por fases e validação em sessão.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Função utilitária para embaralhar alternativas e retornar mapa com gabarito
 */
function buildMultipleChoiceQuestion(string $text, string $correctOpt, array $distractors, string $explanation, string $difficulty, string $topic, int $gameId, string $idPrefix): array {
    // Garante que não haja distratores duplicados ou iguais à resposta certa
    $uniqueDistractors = [];
    foreach ($distractors as $d) {
        $dStr = trim((string)$d);
        if ($dStr !== '' && $dStr !== $correctOpt && !in_array($dStr, $uniqueDistractors, true)) {
            $uniqueDistractors[] = $dStr;
        }
    }

    // Se faltarem distratores, adiciona alternativas plausíveis
    $fallbacks = ['1/2', '2/3', '3/4', '1', '12', '24', '36', '48 m²', '20 metros'];
    foreach ($fallbacks as $fb) {
        if (count($uniqueDistractors) >= 3) break;
        if ($fb !== $correctOpt && !in_array($fb, $uniqueDistractors, true)) {
            $uniqueDistractors[] = $fb;
        }
    }

    $opts = array_slice($uniqueDistractors, 0, 3);
    $opts[] = $correctOpt;
    shuffle($opts);

    $letters = ['A', 'B', 'C', 'D'];
    $correctLetter = 'A';
    $mappedOptions = [];

    foreach ($opts as $idx => $opt) {
        $letter = $letters[$idx];
        $mappedOptions['option_' . strtolower($letter)] = $opt;
        if ($opt === $correctOpt) {
            $correctLetter = $letter;
        }
    }

    $uid = $idPrefix . '_' . bin2hex(random_bytes(6));

    return [
        'id'             => $uid,
        'game_id'        => $gameId,
        'topic'          => $topic,
        'question_text'  => $text,
        'option_a'       => $mappedOptions['option_a'],
        'option_b'       => $mappedOptions['option_b'],
        'option_c'       => $mappedOptions['option_c'],
        'option_d'       => $mappedOptions['option_d'],
        'correct_answer' => $correctLetter,
        'explanation'    => $explanation,
        'difficulty'     => $difficulty,
    ];
}

/**
 * Gerador de Questões de Frações (Chef das Frações)
 * Dificuldade adaptativa: Fase 1 (Fácil), Fase 2 (Médio), Fase 3 (Difícil)
 */
function generateDynamicFractionQuestion(int $phase = 1): array {
    $gameId = 1;
    $topic  = 'Frações e Razões';

    if ($phase === 1) {
        // FASE 1: Fácil - Partes do todo, dobro/metade culinária e visual
        $types = ['pizza', 'recipe_double', 'recipe_half', 'remaining_juice'];
        $type = $types[array_rand($types)];

        if ($type === 'pizza') {
            $slices = [4, 6, 8, 10, 12][array_rand([4, 6, 8, 10, 12])];
            $eaten = rand(1, $slices - 1);
            $text = "Uma pizza napolitana foi cortada em $slices fatias iguais. Um cliente saboreou $eaten fatias. Que fração da pizza ele comeu?";
            $correct = "$eaten/$slices da pizza";
            $distractors = [
                ($eaten + 1) . "/$slices da pizza",
                ($slices - $eaten) . "/$slices da pizza",
                "$eaten/" . ($slices + 2) . " da pizza"
            ];
            $exp = "O cliente consumiu $eaten fatias de um total de $slices partes, representando a fração $eaten/$slices.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_frac');
        } elseif ($type === 'recipe_double') {
            $den = [2, 3, 4][array_rand([2, 3, 4])];
            $text = "Uma receita de bolo pede 1/$den de xícara de baunilha. Para preparar o dobro da receita, quanto você deve utilizar?";
            if ($den === 2) {
                $correct = "1 xícara inteira";
                $distractors = ["1/2 xícara", "2 xícaras", "1/4 de xícara"];
                $exp = "O dobro de 1/2 é 2 x (1/2) = 2/2 = 1 xícara inteira.";
            } elseif ($den === 4) {
                $correct = "2/4 (ou 1/2) de xícara";
                $distractors = ["1/4 de xícara", "1 xícara inteira", "3/4 de xícara"];
                $exp = "O dobro de 1/4 é 2 x (1/4) = 2/4, que simplificado é 1/2 de xícara.";
            } else {
                $correct = "2/3 de xícara";
                $distractors = ["1/3 de xícara", "3/3 de xícara", "1/6 de xícara"];
                $exp = "O dobro de 1/3 é 2 x (1/3) = 2/3 de xícara.";
            }
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_frac');
        } elseif ($type === 'recipe_half') {
            $num = [2, 4, 6][array_rand([2, 4, 6])];
            $den = 7;
            $text = "Um confeiteiro tem uma tigela com $num/$den de calda de chocolate. Se ele usar exatamente a metade dessa calda, quanto usará?";
            $halfNum = $num / 2;
            $correct = "$halfNum/$den de calda";
            $distractors = [
                "$num/" . ($den * 2) . " de calda",
                ($halfNum + 1) . "/$den de calda",
                "1/$den de calda"
            ];
            $exp = "A metade de $num/$den corresponde a dividir o numerador por 2: ($num / 2)/$den = $halfNum/$den.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_frac');
        } else {
            $den = [4, 5, 8][array_rand([4, 5, 8])];
            $used = rand(1, $den - 2);
            $rest = $den - $used;
            $text = "Uma jarra de refresco de laranja estava cheia ($den/$den). Durante o almoço, foram consumidos $used/$den. Qual fração restou na jarra?";
            $correct = "$rest/$den da jarra";
            $distractors = [
                "$used/$den da jarra",
                ($rest - 1) . "/$den da jarra",
                ($rest + 1) . "/$den da jarra"
            ];
            $exp = "Subtraindo da quantidade total: $den/$den - $used/$den = $rest/$den da jarra restante.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_frac');
        }
    } elseif ($phase === 2) {
        // FASE 2: Médio - Soma/Subtração com mesmo denominador, frações equivalentes e frações de quantidades
        $types = ['add_same_den', 'equivalent', 'fraction_of_total'];
        $type = $types[array_rand($types)];

        if ($type === 'add_same_den') {
            $den = [5, 6, 8, 10][array_rand([5, 6, 8, 10])];
            $a = rand(1, 3);
            $b = rand(1, $den - $a - 1);
            $sum = $a + $b;
            $text = "Para fazer um doce gourmet, o chef misturou $a/$den de xícara de açúcar cristal com $b/$den de xícara de açúcar mascavo. Qual é o total da mistura?";
            $correct = "$sum/$den de xícara";
            $distractors = [
                "$sum/" . ($den * 2) . " de xícara",
                ($sum + 1) . "/$den de xícara",
                abs($a - $b) . "/$den de xícara"
            ];
            $exp = "Com denominadores iguais, somamos apenas os numeradores e mantemos a base: $a/$den + $b/$den = ($a + $b)/$den = $sum/$den.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'medium', $topic, $gameId, 'dyn_frac');
        } elseif ($type === 'equivalent') {
            $basePairs = [[1, 2], [1, 3], [2, 3], [3, 4], [2, 5]];
            $pair = $basePairs[array_rand($basePairs)];
            $mult = rand(2, 4);
            $n1 = $pair[0];
            $d1 = $pair[1];
            $n2 = $n1 * $mult;
            $d2 = $d1 * $mult;
            $text = "Qual destas frações é estritamente equivalente à fração $n1/$d1?";
            $correct = "$n2/$d2";
            $distractors = [
                ($n2 + 1) . "/$d2",
                "$n2/" . ($d2 + 1),
                "$n1/" . ($d1 * 2)
            ];
            $exp = "Multiplicando o numerador e o denominador por $mult: ($n1 x $mult) / ($d1 x $mult) = $n2/$d2. Logo, são frações equivalentes.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'medium', $topic, $gameId, 'dyn_frac');
        } else {
            $den = [3, 4, 5][array_rand([3, 4, 5])];
            $unit = rand(3, 7);
            $total = $den * $unit;
            $num = rand(1, $den - 1);
            $ans = $num * $unit;
            $text = "Uma caixa contém $total ovos para a confeitaria. O chef utilizou $num/$den do total para os pães de mel. Quantos ovos foram utilizados?";
            $correct = "$ans ovos";
            $distractors = [
                ($ans + $unit) . " ovos",
                ($ans - $unit > 0 ? $ans - $unit : $ans + 2) . " ovos",
                ($total - $ans) . " ovos"
            ];
            $exp = "Calculando a fração: primeiro divide o total pelo denominador ($total ÷ $den = $unit), e multiplica pelo numerador ($unit x $num = $ans ovos).";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'medium', $topic, $gameId, 'dyn_frac');
        }
    } else {
        // FASE 3: Difícil - Operações com múltiplos/denominadores diferentes e frações irredutíveis
        $types = ['sub_diff_den', 'add_diff_den', 'simplification'];
        $type = $types[array_rand($types)];

        if ($type === 'add_diff_den') {
            $pairs = [
                ['text' => '1/2 + 1/4', 'ans' => '3/4', 'exp' => 'Transformando 1/2 em 2/4: 2/4 + 1/4 = 3/4.', 'dist' => ['2/6', '2/4', '1/8']],
                ['text' => '1/3 + 1/6', 'ans' => '3/6 (ou 1/2)', 'exp' => 'Transformando 1/3 em 2/6: 2/6 + 1/6 = 3/6 = 1/2.', 'dist' => ['2/9', '2/6', '1/9']],
                ['text' => '2/5 + 3/10', 'ans' => '7/10', 'exp' => 'Transformando 2/5 em 4/10: 4/10 + 3/10 = 7/10.', 'dist' => ['5/15', '5/10', '6/10']]
            ];
            $choice = $pairs[array_rand($pairs)];
            $text = "Qual é o valor exato da soma das frações " . $choice['text'] . "?";
            return buildMultipleChoiceQuestion($text, $choice['ans'], $choice['dist'], $choice['exp'], 'hard', $topic, $gameId, 'dyn_frac');
        } elseif ($type === 'sub_diff_den') {
            $pairs = [
                ['text' => '3/4 - 1/2', 'ans' => '1/4', 'exp' => 'Como 1/2 é igual a 2/4: 3/4 - 2/4 = 1/4.', 'dist' => ['2/2', '2/4', '1/8']],
                ['text' => '5/6 - 1/3', 'ans' => '3/6 (ou 1/2)', 'exp' => 'Convertendo 1/3 para 2/6: 5/6 - 2/6 = 3/6 = 1/2.', 'dist' => ['4/3', '2/6', '4/6']],
                ['text' => '7/10 - 2/5', 'ans' => '3/10', 'exp' => 'Convertendo 2/5 para 4/10: 7/10 - 4/10 = 3/10.', 'dist' => ['5/5', '5/10', '1/5']]
            ];
            $choice = $pairs[array_rand($pairs)];
            $text = "Um chef retirou " . explode(' - ', $choice['text'])[1] . " de uma medida de " . explode(' - ', $choice['text'])[0] . " de manteiga. Quanto restou?";
            return buildMultipleChoiceQuestion($text, $choice['ans'], $choice['dist'], $choice['exp'], 'hard', $topic, $gameId, 'dyn_frac');
        } else {
            $fractions = [
                ['orig' => '12/16', 'irred' => '3/4', 'divisor' => 4, 'dist' => ['6/8', '2/3', '4/5']],
                ['orig' => '15/20', 'irred' => '3/4', 'divisor' => 5, 'dist' => ['5/4', '3/5', '1/2']],
                ['orig' => '14/21', 'irred' => '2/3', 'divisor' => 7, 'dist' => ['7/10', '1/3', '4/7']],
                ['orig' => '18/24', 'irred' => '3/4', 'divisor' => 6, 'dist' => ['9/12', '2/3', '3/8']]
            ];
            $f = $fractions[array_rand($fractions)];
            $text = "Ao simplificar a fração " . $f['orig'] . " até sua forma irredutível, qual fração obtemos?";
            $exp = "Dividindo o numerador e o denominador pelo divisor comum (" . $f['divisor'] . "): obtemos " . $f['irred'] . ".";
            return buildMultipleChoiceQuestion($text, $f['irred'], $f['dist'], $exp, 'hard', $topic, $gameId, 'dyn_frac');
        }
    }
}

/**
 * Gerador de Questões de Geometria (Construtor de Cidades)
 * Dificuldade adaptativa: Fase 1 (Fácil), Fase 2 (Médio), Fase 3 (Difícil)
 */
function generateDynamicGeometryQuestion(int $phase = 1): array {
    $gameId = 2;
    $topic  = 'Geometria Plana';

    if ($phase === 1) {
        // FASE 1: Fácil - Área e Perímetro de retângulos e quadrados com números amigáveis
        $type = ['rect_area', 'square_perim', 'rect_fence'][array_rand(['rect_area', 'square_perim', 'rect_fence'])];

        if ($type === 'rect_area') {
            $w = rand(4, 9);
            $l = rand(6, 14);
            $area = $w * $l;
            $text = "Para erguer um novo edifício empresarial, foi demarcado um terreno retangular com {$w}m de largura e {$l}m de comprimento. Qual é a sua área total?";
            $correct = "$area m²";
            $perim = 2 * ($w + $l);
            $distractors = [
                "$perim m²",
                ($area + $w) . " m²",
                ($area - $l > 0 ? $area - $l : $area + 10) . " m²"
            ];
            $exp = "A área de um retângulo é calculada pela fórmula Base x Altura: $w x $l = $area m².";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_geo');
        } elseif ($type === 'square_perim') {
            $side = rand(5, 12);
            $perim = 4 * $side;
            $text = "Uma praça comunitária tem o formato de um quadrado perfeito com lado medindo {$side}m. Qual é o perímetro total dessa praça?";
            $correct = "$perim metros";
            $area = $side * $side;
            $distractors = [
                "$area metros",
                (2 * $side) . " metros",
                ($perim + 4) . " metros"
            ];
            $exp = "O perímetro do quadrado é a soma dos 4 lados iguais (4 x $side = $perim metros).";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_geo');
        } else {
            $w = rand(3, 7);
            $l = rand(5, 12);
            $fence = 2 * ($w + $l);
            $text = "Um canteiro de obras retangular mede {$w}m de largura por {$l}m de comprimento. Quantos metros lineares de tapume de proteção são necessários para cercar todo o seu contorno?";
            $correct = "$fence metros";
            $area = $w * $l;
            $distractors = [
                "$area metros",
                ($w + $l) . " metros",
                ($fence + 4) . " metros"
            ];
            $exp = "Para cercar o contorno, calculamos o perímetro do retângulo: 2 x ($w + $l) = 2 x " . ($w + $l) . " = $fence metros.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'easy', $topic, $gameId, 'dyn_geo');
        }
    } elseif ($phase === 2) {
        // FASE 2: Médio - Lado a partir da área quadrada, área do triângulo e ladrilhamento/pisos
        $type = ['square_side', 'triangle_area', 'floor_tiles'][array_rand(['square_side', 'triangle_area', 'floor_tiles'])];

        if ($type === 'square_side') {
            $sides = [4, 5, 6, 7, 8, 9, 10, 11, 12];
            $side = $sides[array_rand($sides)];
            $area = $side * $side;
            $text = "O saguão de um arranha-céu possui formato quadrado e uma área total de {$area}m². Qual é a medida de cada lado desse saguão?";
            $correct = "$side metros";
            $distractors = [
                ($side + 2) . " metros",
                ($side - 1 > 0 ? $side - 1 : $side + 4) . " metros",
                (int)($area / 2) . " metros"
            ];
            $exp = "Em um quadrado, Área = Lado². Para descobrir o lado, calculamos a raiz quadrada da área: √$area = $side metros.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'medium', $topic, $gameId, 'dyn_geo');
        } elseif ($type === 'triangle_area') {
            $base = rand(6, 14);
            $height = rand(4, 10);
            // garante produto par
            if (($base * $height) % 2 !== 0) $base++;
            $area = ($base * $height) / 2;
            $text = "Um parque ecológico urbano tem formato triangular com base de {$base}m e altura relativa de {$height}m. Qual é a área verde desse parque?";
            $correct = "$area m²";
            $fullRect = $base * $height;
            $distractors = [
                "$fullRect m²",
                ($area + $base) . " m²",
                ($base + $height) . " m²"
            ];
            $exp = "A área do triângulo é calculada por (Base x Altura) ÷ 2: ($base x $height) ÷ 2 = $fullRect ÷ 2 = $area m².";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'medium', $topic, $gameId, 'dyn_geo');
        } else {
            $w = rand(4, 8);
            $l = rand(5, 12);
            $area = $w * $l;
            $tileSize = [1, 2][array_rand([1, 2])];
            $tiles = (int)($area / $tileSize);
            $text = "Um salão comercial retangular de {$w}m por {$l}m será revestido. Se cada placa de piso cobre exatamente {$tileSize}m², quantas placas completas serão necessárias?";
            $correct = "$tiles placas";
            $distractors = [
                ($tiles + 10) . " placas",
                ($w + $l) . " placas",
                (int)($tiles / 2 > 0 ? $tiles / 2 : $tiles + 5) . " placas"
            ];
            $exp = "Área da sala = $w x $l = $area m². Dividindo pelo rendimento de cada placa ($area ÷ $tileSize), obtemos $tiles placas.";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'medium', $topic, $gameId, 'dyn_geo');
        }
    } else {
        // FASE 3: Difícil - Terrenos compostos em formato L e Áreas Circulares
        $type = ['circle_area', 'l_shaped_land'][array_rand(['circle_area', 'l_shaped_land'])];

        if ($type === 'circle_area') {
            $radius = rand(3, 7);
            // Pi aproximado = 3
            $r2 = $radius * $radius;
            $area = 3 * $r2;
            $text = "Uma rotatória moderna da metrópole possui formato circular com raio de {$radius}m. Considerando π ≈ 3, qual é a área aproximada dessa rotatória?";
            $correct = "$area m²";
            $distractors = [
                (2 * 3 * $radius) . " m²",
                (3 * $radius) . " m²",
                ($area + 15) . " m²"
            ];
            $exp = "A área do círculo é dada por π x R². Com π ≈ 3 e R = $radius: Área = 3 x ({$radius}²) = 3 x $r2 = $area m².";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'hard', $topic, $gameId, 'dyn_geo');
        } else {
            $bigW = rand(8, 12);
            $bigL = rand(10, 16);
            $cutW = rand(3, $bigW - 2);
            $cutL = rand(3, $bigL - 2);
            $totalArea = $bigW * $bigL;
            $cutArea = $cutW * $cutL;
            $finalArea = $totalArea - $cutArea;

            $text = "Um lote urbano em formato de 'L' foi projetado a partir de um retângulo {$bigW}x{$bigL}m do qual foi retirado um recuo predial de {$cutW}x{$cutL}m. Qual é a área construível restante?";
            $correct = "$finalArea m²";
            $distractors = [
                "$totalArea m²",
                ($finalArea + $cutW * 2) . " m²",
                ($bigW * $cutL) . " m²"
            ];
            $exp = "Área total do retângulo = $bigW x $bigL = $totalArea m². Área recortada = $cutW x $cutL = $cutArea m². Área restante = $totalArea - $cutArea = $finalArea m².";
            return buildMultipleChoiceQuestion($text, $correct, $distractors, $exp, 'hard', $topic, $gameId, 'dyn_geo');
        }
    }
}

/**
 * Função principal: orquestra a geração de questões adaptativas para a rodada
 * e registra com segurança na sessão PHP para conferência de gabarito sem banco
 */
function generateDynamicGameQuestions(string $gameSlug, int $count = 8, ?string $topic = null): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $questions = [];

    // Distribuição adaptativa:
    // Questões 1 a 3: Fase 1 (Fácil)
    // Questões 4 a 6: Fase 2 (Médio)
    // Questões 7 a 8: Fase 3 (Difícil)
    for ($i = 0; $i < $count; $i++) {
        $phase = 1;
        if ($i >= 3 && $i < 6) {
            $phase = 2;
        } elseif ($i >= 6) {
            $phase = 3;
        }

        if ($gameSlug === 'fractions') {
            $q = generateDynamicFractionQuestion($phase);
        } elseif ($gameSlug === 'geometry') {
            $q = generateDynamicGeometryQuestion($phase);
        } else {
            // Reforço ou misto
            if ($topic && stripos($topic, 'geom') !== false) {
                $q = generateDynamicGeometryQuestion($phase);
            } elseif ($topic && stripos($topic, 'frac') !== false) {
                $q = generateDynamicFractionQuestion($phase);
            } else {
                $q = ($i % 2 === 0) ? generateDynamicFractionQuestion($phase) : generateDynamicGeometryQuestion($phase);
            }
        }

        $questions[] = $q;
    }

    // Armazena na sessão para validação posterior via check_answer.php
    if (!isset($_SESSION['dynamic_questions'])) {
        $_SESSION['dynamic_questions'] = [];
    }

    foreach ($questions as $q) {
        $_SESSION['dynamic_questions'][$q['id']] = [
            'correct_answer' => $q['correct_answer'],
            'explanation'    => $q['explanation'],
            'difficulty'     => $q['difficulty']
        ];
    }

    return $questions;
}
