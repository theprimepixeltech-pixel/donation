<?php
function process_webhook(array $config): void { http_response_code(503); header('Content-Type: application/json'); echo json_encode(['error'=>'Payments are disabled.']); }
function create_donation(array $config): never {
 $u=require_user(); $name=field('name',120); $email=field('email',254); $mobile=field('mobile',30); $address=field('address',1000); $purpose=field('purpose',120); $amount=field('amount',20);
 if (field('declaration',30)!=='domestic') throw new DomainException('Foreign contributions are not accepted through this flow.');
 if (!$name || !filter_var($email,FILTER_VALIDATE_EMAIL) || !$mobile || !$address || !in_array($purpose,['Where needed most','Rehabilitation','Special education','Child development'],true)) throw new DomainException('Complete all donor details and select a valid purpose.');
 if (!preg_match('/^\d{1,7}(\.\d{1,2})?$/',$amount) || (float)$amount<1 || (float)$amount>1000000) throw new DomainException('Enter an amount between ₹1 and ₹10,00,000.');
 if (empty($_POST['consent'])) throw new DomainException('Please acknowledge this is an unpaid pledge.');
 query('INSERT INTO donations(user_id,name,email,mobile,address,purpose,amount,public_consent,anonymous) VALUES(?,?,?,?,?,?,?,?,?)',[$u['id'],$name,$email,$mobile,$address,$purpose,(int)round((float)$amount*100),isset($_POST['public_consent'])?1:0,isset($_POST['anonymous'])?1:0]);
 audit('donation.pledge','Unpaid pledge recorded'); flash('Your pledge has been saved. No payment was taken and no donation receipt has been issued.'); redirect('/donor-dashboard');
}
function upload_document(): void {
 require_user(true); $file=$_FILES['file'] ?? null;
 if (!$file || $file['error']!==UPLOAD_ERR_OK || $file['size']>5*1024*1024) throw new DomainException('Choose a PDF, JPEG, or PNG file up to 5 MB.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']); $allowed=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'];
 if (!isset($allowed[$mime])) throw new DomainException('Only PDF, JPEG, and PNG files are accepted.');
 $category=field('category',30); if (!in_array($category,['certificate','gallery','report'],true)) throw new DomainException('Invalid file category.');
 if ($category==='gallery' && $mime==='application/pdf') throw new DomainException('Gallery uploads must be images.');
 $title=field('title',160); if (!$title) throw new DomainException('A title is required.');
 $dir=storage_dir().'/uploads'; if (!is_dir($dir)) mkdir($dir,0700,true);
 $filename=bin2hex(random_bytes(24)).'.'.$allowed[$mime];
 if (!move_uploaded_file($file['tmp_name'],$dir.'/'.$filename)) throw new RuntimeException('Upload could not be saved.');
 try { query('INSERT INTO documents(title,category,filename,mime,published) VALUES(?,?,?,?,?)',[$title,$category,$filename,$mime,isset($_POST['published'])?1:0]); }
 catch (Throwable $e) { unlink($dir.'/'.$filename); throw $e; }
 audit('document.upload',$title);
}
function deliver_document(): void {
 $doc=query('SELECT * FROM documents WHERE id=?',[(int)($_GET['id'] ?? 0)])->fetch();
 if (!$doc || (!$doc['published'] && (current_user()['role'] ?? '')!=='admin')) { http_response_code(404); echo 'Document not found.'; return; }
 $path=storage_dir().'/uploads/'.$doc['filename']; if (!is_file($path)) { http_response_code(404); return; }
 header('Content-Type: '.$doc['mime']); header('Content-Disposition: inline; filename="document-'.(int)$doc['id'].'.'.pathinfo($doc['filename'],PATHINFO_EXTENSION).'"');
 header('Content-Length: '.filesize($path)); readfile($path);
}
function member_status(array $member): string {
 return $member['status']==='approved' && $member['valid_until']<date('Y-m-d') ? 'expired' : $member['status'];
}
function qr_svg(string $value): string {
 $renderer=new BaconQrCode\Renderer\ImageRenderer(new BaconQrCode\Renderer\RendererStyle\RendererStyle(180),new BaconQrCode\Renderer\Image\SvgImageBackEnd());
 return (new BaconQrCode\Writer($renderer))->writeString($value);
}
function output_pdf(string $html, string $filename): never {
 $pdf=new Dompdf\Dompdf(['isRemoteEnabled'=>false,'defaultFont'=>'DejaVu Sans']); $pdf->loadHtml($html); $pdf->setPaper('A4'); $pdf->render(); $pdf->stream($filename,['Attachment'=>true]); exit;
}
function pdf_style(): string { return '<style>body{font-family:DejaVu Sans;color:#214b3b;font-size:13px}h1{font-size:30px}h2{font-size:23px}.box{border:3px solid #245744;padding:35px}small{color:#667}table{width:100%;border-collapse:collapse}td,th{padding:8px;border-bottom:1px solid #ddd;text-align:left}</style>'; }
function render_pdf(string $type): never {
 global $config; $u=require_user();
 if ($type==='receipt') {
  $d=query('SELECT * FROM donations WHERE id=?',[(int)($_GET['id'] ?? 0)])->fetch();
  if (!$d || ($d['user_id']!==$u['id'] && $u['role']!=='admin') || $d['status']!=='paid' || !$d['receipt_no']) { http_response_code(404); exit('Receipt not available.'); }
  output_pdf(pdf_style().'<h1>The Hope Foundation</h1><h2>Donation acknowledgement</h2><p>Receipt: '.e($d['receipt_no']).'</p><p>Donor: '.e($d['name']).'</p><p>Amount INR: '.number_format($d['amount']/100,2).'</p><p>Date: '.e($d['paid_at']).'</p><p>Transaction: '.e($d['payment_id']).'</p><p>'.nl2br(e(setting('registration_details'))).'</p><small>No 80G tax benefit is claimed by this acknowledgement.</small>','receipt.pdf');
 }
 $id=(int)($_GET['id'] ?? 0);
 $m=$u['role']==='admin' && $id ? query('SELECT m.*,u.name FROM memberships m JOIN users u ON u.id=m.user_id WHERE m.id=?',[$id])->fetch() : query('SELECT m.*,u.name FROM memberships m JOIN users u ON u.id=m.user_id WHERE user_id=?',[$u['id']])->fetch();
 if (!$m || member_status($m)!=='approved') { http_response_code(403); exit('An active approved membership is required.'); }
 $url=$config['url'].'/verify?token='.urlencode($m['token']); $qr='data:image/svg+xml;base64,'.base64_encode(qr_svg($url));
 $heading=$type==='member-card' ? 'Membership ID Card' : 'Certificate of Membership';
 output_pdf(pdf_style().'<div class="box"><p>THE HOPE FOUNDATION</p><h1>'.$heading.'</h1><h2>'.e($m['name']).'</h2><p>Member ID: '.e($m['member_id']).'</p><p>Valid until: '.e($m['valid_until']).'</p><p>Recognised as an approved member of The Hope Foundation.</p><img width="150" src="'.$qr.'"><p>Scan to verify current membership status.</p><small>System-generated membership document. No handwritten signature is implied.</small></div>',$type.'.pdf');
}
function csv_cell(mixed $value): string { $s=(string)$value; return preg_match('/^[=+@\-\t\r\n]/',$s) ? "'".$s : $s; }
function export_report(): never {
 require_user(true); $kind=(string)($_GET['kind'] ?? 'donations');
 $rows=match($kind) {
  'members'=>query('SELECT u.name,u.email,m.mobile,m.member_id,m.status,m.valid_until,m.created_at FROM memberships m JOIN users u ON u.id=m.user_id ORDER BY m.id DESC')->fetchAll(),
  'enquiries'=>query('SELECT name,email,mobile,category,organisation,interest,message,status,created_at FROM enquiries ORDER BY id DESC')->fetchAll(),
  default=>query('SELECT name,email,mobile,purpose,amount,status,payment_id,receipt_no,paid_at,created_at FROM donations ORDER BY id DESC')->fetchAll(),
 };
 audit('report.export',$kind);
 if (($_GET['format'] ?? '')==='pdf') {
  $html=pdf_style().'<h1>The Hope Foundation</h1><h2>'.e(ucfirst($kind)).' report</h2><p>'.date('Y-m-d H:i').' UTC</p>';
  foreach ($rows as $row) { $html.='<p>'; foreach ($row as $key=>$value) $html.=e($key).': '.e((string)$value).'<br>'; $html.='</p>'; }
  if (!$rows) $html.='<p>No records.</p>'; output_pdf($html,'hope-report.pdf');
 }
 header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="hope-report.csv"');
 $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF");
 if ($rows) { fputcsv($out,array_keys($rows[0]),',','"',''); foreach($rows as $row) fputcsv($out,array_map('csv_cell',array_values($row)),',','"',''); }
 else fputcsv($out,['No records'],',','"',''); fclose($out); exit;
}
