<?php
$action=field('action',40);
if ($action==='login') {
 $email=strtolower(field('email',254)); $key=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').'|'.$email);
 $ipKey=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? '').'|all');
 foreach ([$key,$ipKey] as $rateKey) { $a=query('SELECT * FROM login_attempts WHERE key=?',[$rateKey])->fetch(); if ($a && $a['failures']>=10 && time()-(int)$a['last_attempt']<900) throw new DomainException('Too many attempts. Try again in 15 minutes.'); }
 $user=query('SELECT * FROM users WHERE email=?',[$email])->fetch();
 if (!$user || !password_verify((string)($_POST['password'] ?? ''),$user['password'])) {
  foreach ([$key,$ipKey] as $rateKey) query('INSERT INTO login_attempts(key,failures,last_attempt) VALUES(?,1,?) ON CONFLICT(key) DO UPDATE SET failures=CASE WHEN last_attempt < ? THEN 1 ELSE failures+1 END,last_attempt=excluded.last_attempt',[$rateKey,time(),time()-900]);
  throw new DomainException('Email or password is incorrect.');
 }
 query('DELETE FROM login_attempts WHERE key=?',[$key]);
 session_regenerate_id(true); $_SESSION['user_id']=$user['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
 redirect($user['role']==='admin' ? '/admin' : '/donor-dashboard');
}
if ($action==='logout') { $_SESSION=[]; session_regenerate_id(true); redirect('/'); }
if ($action==='register') {
 $name=field('name',120); $email=strtolower(field('email',254)); $password=(string)($_POST['password'] ?? '');
 if (mb_strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter your full name and a valid email.');
 if (strlen($password)<12 || strlen($password)>72) throw new DomainException('Choose a password between 12 and 72 characters.');
 if (empty($_POST['consent'])) throw new DomainException('Please accept the privacy notice.');
 if (query('SELECT id FROM users WHERE email=?',[$email])->fetch()) throw new DomainException('Unable to register with that email. Try signing in or contact the Foundation.');
 query('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT),'user']);
 session_regenerate_id(true); $_SESSION['user_id']=(int)db()->lastInsertId(); $_SESSION['csrf']=bin2hex(random_bytes(32)); redirect('/membership');
}
if ($action==='enquiry' || $action==='membership') {
 $name=field('name',120); $email=field('email',254); $message=field('message'); $mobile=field('mobile',30);
 if (mb_strlen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$message) throw new DomainException('Enter your name, valid email, and message.');
 if (empty($_POST['consent'])) throw new DomainException('Please accept the privacy notice.');
 if ($action==='membership') {
  $u=require_user(); $address=field('address',1000); if (!$address || !$mobile) throw new DomainException('Address and mobile are required for membership.');
  if (query('SELECT id FROM memberships WHERE user_id=?',[$u['id']])->fetch()) throw new DomainException('You already have an application. Check your member dashboard.');
  query('INSERT INTO memberships(user_id,mobile,address,interest,message) VALUES(?,?,?,?,?)',[$u['id'],$mobile,$address,field('interest',160),$message]);
  audit('membership.apply','Application submitted'); flash('Your membership application was submitted for review.'); redirect('/member-login');
 }
 if (!in_array($page,['contact','get-involved','partnerships'],true)) throw new DomainException('Invalid enquiry category.');
 query('INSERT INTO enquiries(name,email,mobile,category,organisation,interest,message) VALUES(?,?,?,?,?,?,?)',[$name,$email,$mobile,$page,field('organisation',160),field('interest',160),$message]);
 flash('Thank you. Your message has been received by the Foundation.'); redirect('/'.$page);
}
if ($action==='review') {
 require_user(true); $id=(int)field('id',20); $status=field('status',20); $note=field('review_note',1000);
 if (!in_array($status,['approved','rejected'],true)) throw new DomainException('Invalid decision.');
 $member=query('SELECT * FROM memberships WHERE id=?',[$id])->fetch(); if (!$member) throw new DomainException('Application not found.');
 $until=field('valid_until',10); if ($status==='approved' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$until) || !strtotime($until) || $until<date('Y-m-d'))) throw new DomainException('Choose a future membership expiry date.');
 query('UPDATE memberships SET status=?,review_note=?,member_id=COALESCE(member_id,?),token=COALESCE(token,?),valid_until=? WHERE id=?',[$status,$note,'HOPE-'.str_pad((string)$id,6,'0',STR_PAD_LEFT),bin2hex(random_bytes(24)),$status==='approved'?$until:$member['valid_until'],$id]);
 audit('membership.review', $id.': '.$status); flash('Membership decision saved. Cards and certificates are available for approved members.'); redirect('/admin?tab=members');
}
if ($action==='enquiry-status') { require_user(true); query('UPDATE enquiries SET status=? WHERE id=?',[!empty($_POST['closed'])?'closed':'new',(int)field('id',20)]); flash('Enquiry updated.'); redirect('/admin?tab=enquiries'); }
if ($action==='settings') {
 require_user(true);
 foreach (['phone','email','address','founder_message','leadership','mission','vision','registration_details'] as $key) query('INSERT INTO settings(name,value) VALUES(?,?) ON CONFLICT(name) DO UPDATE SET value=excluded.value',[$key,field($key,3000)]);
 audit('settings.update','Foundation details'); flash('Foundation details updated.'); redirect('/admin?tab=settings');
}
if ($action==='post') {
 require_user(true); $type=field('type',20); if (!in_array($type,['event','news','story','initiative'],true)) throw new DomainException('Invalid content type.');
 $title=field('title',160); $body=field('body',10000); if (!$title || !$body) throw new DomainException('Title and content are required.');
 $id=(int)field('id',20); $params=[$type,$title,$body,field('event_date',10),isset($_POST['published'])?1:0];
 if ($id) { $params[]=$id; query('UPDATE posts SET type=?,title=?,body=?,event_date=?,published=? WHERE id=?',$params); }
 else query('INSERT INTO posts(type,title,body,event_date,published) VALUES(?,?,?,?,?)',$params);
 audit('content.save',$title); flash('Content saved.'); redirect('/admin?tab=content');
}
if ($action==='delete-post') { require_user(true); query('DELETE FROM posts WHERE id=?',[(int)field('id',20)]); audit('content.delete',field('id',20)); flash('Content removed.'); redirect('/admin?tab=content'); }
if ($action==='upload') { upload_document(); flash('File uploaded.'); redirect('/admin?tab=documents'); }
if ($action==='document-status') { require_user(true); query('UPDATE documents SET published=? WHERE id=?',[isset($_POST['published'])?1:0,(int)field('id',20)]); flash('Publication status updated.'); redirect('/admin?tab=documents'); }
if ($action==='donation') { create_donation($config); }
throw new DomainException('Unknown action.');
