"""Check Apache/LiteSpeed repository-root hosting (default: local Apache at 8081)."""
import urllib.error
import urllib.request
BASE = 'http://127.0.0.1:8081'
for path, content in [('/',b'A little hope'),('/index.php',b'A little hope'),('/about',b'A foundation built'),('/login',b'Welcome back'),('/assets/css/site.css',b'--ink'),('/assets/js/site.js',b'menu-toggle')]:
    with urllib.request.urlopen(BASE+path) as response:
        assert response.status==200 and content in response.read(), path
for path in ['/app/config/site.php','/storage/hope.sqlite','/vendor/autoload.php','/composer.json','/README.md','/.git/config','/.env','/public/index.php','/bin/admin.php','/tests/fixture.php']:
    try:
        urllib.request.urlopen(BASE+path)
        raise AssertionError('Private path accessible: '+path)
    except urllib.error.HTTPError as error:
        assert error.code==403, (path,error.code)
try:
    urllib.request.urlopen(BASE+'/index.html')
    raise AssertionError('Original sample is still served')
except urllib.error.HTTPError as error:
    assert error.code==404
print('PASS: repository-root homepage, nested routes and assets work; private paths denied; original sample remains removed.')
