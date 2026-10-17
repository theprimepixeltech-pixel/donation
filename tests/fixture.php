<?php
if (PHP_SAPI !== 'cli' || getenv('HOPE_STORAGE') !== '/tmp/hope-test') exit('Use an isolated test container.');
require dirname(__DIR__).'/app/database.php';
if ((int)query('SELECT COUNT(*) FROM users')->fetchColumn() !== 0) exit('Test database must be fresh.');
query('INSERT INTO users(name,email,password,role) VALUES(?,?,?,?)',['Test Admin','admin@example.test',password_hash('TestAdminPassword!123',PASSWORD_DEFAULT),'admin']);
echo "Fixture created.\n";
