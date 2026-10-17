<?php
// Run via the CLI, never through the public document root.
if (PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/app/database.php';
$email=$argv[1] ?? ''; $name=$argv[2] ?? 'Administrator';
if (!filter_var($email,FILTER_VALIDATE_EMAIL)) exit("Usage: php bin/admin.php admin@example.org 'Admin Name'\nPassword is read from standard input (minimum 12 characters).\n");
if (function_exists('shell_exec') && DIRECTORY_SEPARATOR==='/') system('stty -echo');
fwrite(STDOUT,"New admin password: ");
try { $password=rtrim(fgets(STDIN) ?: '',"\r\n"); } finally { if (DIRECTORY_SEPARATOR==='/') system('stty echo'); fwrite(STDOUT,"\n"); }
if (strlen($password)<12 || strlen($password)>72) exit("Use 12–72 characters.\n");
query('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?) ON CONFLICT(email) DO UPDATE SET password=excluded.password,role=excluded.role,name=excluded.name',[$name,strtolower($email),password_hash($password,PASSWORD_DEFAULT),'admin']);
echo "Administrator saved. Sign in at /login.\n";
