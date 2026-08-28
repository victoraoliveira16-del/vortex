<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if(!isLoggedIn()){echo json_encode(['error'=>'Nao autenticado']);exit;}
$data=json_decode(file_get_contents('php://input'),true);
$userId=(int)$_SESSION['user_id'];
$gameId=(int)($data['game_id']??0);
$score=(int)($data['score']??0);
$correct=(int)($data['correct']??0);
$wrong=(int)($data['wrong']??0);
$time=(int)($data['time_spent']??0);
$diff=$data['difficulty']??'easy';
if(!$gameId){echo json_encode(['error'=>'game_id invalido']);exit;}
$pdo->prepare('INSERT INTO game_sessions (user_id,game_id,score,correct_answers,wrong_answers,time_spent,difficulty) VALUES (?,?,?,?,?,?,?)')->execute([$userId,$gameId,$score,$correct,$wrong,$time,$diff]);
$xpGained=max(5,(int)round($score*0.1));
addXP($userId,$xpGained);
checkAndUnlockAchievements($userId);
$stmt=$pdo->prepare('SELECT topic FROM games WHERE id=?');
$stmt->execute([$gameId]);
$game=$stmt->fetch();
if($game){
    $total=$correct+$wrong;
    $pct=$total>0?(int)round($correct/$total*100):0;
    $pdo->prepare('INSERT INTO learning_trail (user_id,topic,progress_pct) VALUES (?,?,?) ON DUPLICATE KEY UPDATE progress_pct=GREATEST(progress_pct,VALUES(progress_pct))')->execute([$userId,$game['topic'],$pct]);
    if($total>0&&($wrong/$total)>0.6){
        $ts=$pdo->prepare('SELECT id FROM users WHERE role="teacher"');
        $ts->execute();
        foreach($ts->fetchAll() as $t){
            $pdo->prepare('INSERT INTO notifications (user_id,type,title,message) VALUES (?,"alert","Alerta de Desempenho",?)')->execute([$t['id'],'Aluno ID '.$userId.' errou mais de 60% em: '.$game['topic']]);
        }
    }
}
echo json_encode(['success'=>true,'xp_gained'=>$xpGained]);
