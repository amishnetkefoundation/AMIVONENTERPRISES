<?php
declare(strict_types=1);
$secure=(($_SERVER['HTTPS']??'')==='on') || (getenv('RENDER')==='true');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$secure,'httponly'=>true,'samesite'=>'Lax']); session_start();
function ev(string $k,string $d=''): string { return (string)($_ENV[$k]??$_SERVER[$k]??getenv($k)?:$d); }
function db(): PDO {
 static $p=null; if($p instanceof PDO)return $p;
 $dir=dirname(__DIR__).'/storage'; if(!is_dir($dir))mkdir($dir,0775,true);
 $p=new PDO('sqlite:'.$dir.'/amivon-pay.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $p->exec("PRAGMA foreign_keys=ON;
 CREATE TABLE IF NOT EXISTS users(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT NOT NULL,email TEXT UNIQUE NOT NULL,password_hash TEXT NOT NULL,role TEXT NOT NULL DEFAULT 'customer',created_at TEXT NOT NULL);
 CREATE TABLE IF NOT EXISTS transactions(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER NOT NULL,amount_minor INTEGER NOT NULL,currency TEXT NOT NULL,status TEXT NOT NULL,reference TEXT UNIQUE NOT NULL,session_id TEXT,description TEXT,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,FOREIGN KEY(user_id) REFERENCES users(id));
 CREATE TABLE IF NOT EXISTS wallet_entries(id INTEGER PRIMARY KEY AUTOINCREMENT,user_id INTEGER NOT NULL,transaction_id INTEGER,entry_type TEXT NOT NULL,amount_minor INTEGER NOT NULL,currency TEXT NOT NULL,note TEXT NOT NULL,created_at TEXT NOT NULL,FOREIGN KEY(user_id) REFERENCES users(id),FOREIGN KEY(transaction_id) REFERENCES transactions(id));");
 if((int)$p->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn()===0){
  $pw=ev('ADMIN_PASSWORD'); if(strlen($pw)<14)$pw=bin2hex(random_bytes(18));
  $q=$p->prepare("INSERT OR IGNORE INTO users(name,email,password_hash,role,created_at) VALUES(?,?,?,?,?)");
  $q->execute(['AMIVON PAY Administrator',ev('ADMIN_EMAIL','admin@amivonenterprises.store'),password_hash($pw,PASSWORD_DEFAULT),'admin',date('c')]);
  file_put_contents($dir.'/.initial-admin-password',$pw);
 }
 return $p;
}
function h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function check_csrf():void{if(!hash_equals(csrf(),(string)($_POST['_csrf']??''))){http_response_code(419);exit('Refresh and retry.');}}
function user():?array{return $_SESSION['user']??null;}
function need_user():array{$u=user();if(!$u){header('Location: /?page=login');exit;}return $u;}
function need_admin():array{$u=need_user();if($u['role']!=='admin'){http_response_code(403);exit('Forbidden');}return $u;}
function msg(string $s,string $type='info'):void{$_SESSION['msg']=[$s,$type];}
function flash():?array{$m=$_SESSION['msg']??null;unset($_SESSION['msg']);return $m;}
function cash(int $minor):string{return 'KES '.number_format($minor/100,2);}
function api(string $method,string $path,?array $body=null,bool $auth=true,?string $idem=null):array{
 $base=rtrim(ev('PAYSASA_BASE_URL','https://api.paysasa.com/api/v1'),'/');$headers=['Accept: application/json','Content-Type: application/json'];
 if($auth){$key=ev('PAYSASA_API_KEY');if(!$key)throw new RuntimeException('Set the Paysasa TEST API key in Render environment variables.');$headers[]='x-api-key: '.$key;}
 if($idem)$headers[]='X-Idempotency-Key: '.$idem;if(!function_exists('curl_init'))throw new RuntimeException('PHP cURL extension is required.');
 $c=curl_init($base.'/'.ltrim($path,'/'));curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>30,CURLOPT_POSTFIELDS=>$body===null?null:json_encode($body)]);$raw=curl_exec($c);$err=curl_error($c);$status=(int)curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);
 if($raw===false)throw new RuntimeException('Paysasa connection failed: '.$err);$data=json_decode($raw,true);if(!is_array($data))throw new RuntimeException('Invalid Paysasa response.');if($status<200||$status>=300)throw new RuntimeException((string)($data['message']??$data['error']??'Paysasa HTTP '.$status));return $data;
}
function bal(int $uid):int{$q=db()->prepare("SELECT COALESCE(SUM(CASE WHEN entry_type='credit' THEN amount_minor ELSE -amount_minor END),0) FROM wallet_entries WHERE user_id=? AND currency='KES'");$q->execute([$uid]);return (int)$q->fetchColumn();}
function settle(array $s,int $id):void{$p=db();$p->beginTransaction();try{$q=$p->prepare('SELECT * FROM transactions WHERE id=?');$q->execute([$id]);$t=$q->fetch();if(!$t)throw new RuntimeException('Transaction missing.');if($t['status']==='completed'){$p->commit();return;}$st=strtolower((string)($s['status']??''));if(!in_array($st,['paid','completed'],true)){$p->prepare('UPDATE transactions SET status=?,updated_at=? WHERE id=?')->execute([$st?:'pending',date('c'),$id]);$p->commit();return;}$p->prepare("UPDATE transactions SET status='completed',updated_at=? WHERE id=?")->execute([date('c'),$id]);$q=$p->prepare('SELECT COUNT(*) FROM wallet_entries WHERE transaction_id=?');$q->execute([$id]);if(!(int)$q->fetchColumn())$p->prepare("INSERT INTO wallet_entries(user_id,transaction_id,entry_type,amount_minor,currency,note,created_at) VALUES(?,?,?,?,?,?,?)")->execute([$t['user_id'],$id,'credit',$t['amount_minor'],$t['currency'],'Paysasa-confirmed deposit',date('c')]);$p->commit();}catch(Throwable $e){$p->rollBack();throw $e;}}
