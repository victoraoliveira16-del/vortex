<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if(!isLoggedIn()){echo json_encode(['error'=>'Nao autenticado']);exit;}
$data=json_decode(file_get_contents('php://input'),true);
$userId=(int)$_SESSION['user_id'];
$topic=$data['topic']??'';
$gameId=(int)($data['game_id']??0);
$ANTHROPIC_KEY='YOUR_ANTHROPIC_API_KEY_HERE';
$stmt=$pdo->prepare('SELECT SUM(wrong_answers) AS e,SUM(correct_answers) AS a,SUM(wrong_answers+correct_answers) AS t FROM game_sessions gs JOIN games g ON g.id=gs.game_id WHERE gs.user_id=? AND g.topic=?');
$stmt->execute([$userId,$topic]);
$stats=$stmt->fetch();
$errorRate=(int)($stats['t']??0)>0?round((int)$stats['e']/(int)$stats['t']*100):50;
$prompt='O aluno errou '.$errorRate.'% das questoes sobre "'.$topic.'". Gere 3 questoes de multipla escolha em portugues do Brasil, formato JSON puro (array): [{"pergunta":"","opcao_a":"","opcao_b":"","opcao_c":"","opcao_d":"","resposta_correta":"A","explicacao":""}]';
$ch=curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$ANTHROPIC_KEY,'anthropic-version: 2023-06-01'],CURLOPT_POSTFIELDS=>json_encode(['model'=>'claude-sonnet-4-6','max_tokens'=>1500,'messages'=>[['role'=>'user','content'=>$prompt]]])]);
$response=curl_exec($ch);curl_close($ch);
if(!$response){echo json_encode(['error'=>'Falha API Claude']);exit;}
$apiData=json_decode($response,true);
$jsonText=$apiData['content'][0]['text']??'';
$jsonText=preg_replace('/```json|```/','',$jsonText);
$questions=json_decode(trim($jsonText),true);
if(!is_array($questions)){echo json_encode(['error'=>'IA retornou formato inválido','raw'=>$jsonText]);exit;}
$inserted=0;
foreach($questions as $q){
    if(empty($q['pergunta']))continue;
    $pdo->prepare('INSERT INTO questions (game_id,topic,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,is_ai_generated) VALUES (?,?,?,?,?,?,?,?,?,1)')->execute([$gameId,$topic,$q['pergunta'],$q['opcao_a']??'',$q['opcao_b']??'',$q['opcao_c']??'',$q['opcao_d']??'',$q['resposta_correta']??'A',$q['explicacao']??'']);
    $inserted++;
}
$pdo->prepare('INSERT INTO notifications (user_id,type,title,message) VALUES (?,"ai","Reforco Personalizado Disponivel",?)')->execute([$userId,'Geradas '.$inserted.' questões de reforço sobre '.$topic.' com base nas suas dificuldades!']);
echo json_encode(['success'=>true,'generated'=>$inserted]);
