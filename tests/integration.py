"""Run against an isolated Docker test server, never a production database.

docker run -d --name hope-full-test -p 8081:8080 \
 -e HOPE_URL=http://localhost:8081 -e HOPE_STORAGE=/tmp/hope-test \
 -v "$PWD":/app -w /app php:8.3-cli \
 php -S 0.0.0.0:8080 -t public public/router.php
python3 tests/integration.py

docker rm -f hope-full-test after testing. No production credentials used.
"""
import http.cookiejar
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:8081'

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def get(self, path):
        try:
            with self.opener.open(BASE + path) as r:
                return r.status, r.read(), r.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.headers
    def post(self, path, fields, csrf=True):
        if csrf:
            _, html, _ = self.get(path)
            fields['csrf'] = re.search(rb'name="csrf" value="([^"]+)"', html).group(1).decode()
        try:
            with self.opener.open(BASE + path, urllib.parse.urlencode(fields).encode()) as r:
                return r.status, r.read(), r.headers
        except urllib.error.HTTPError as e:
            return e.code, e.read(), e.headers
    def upload(self, fields, name, content):
        _, html, _ = self.get('/admin?tab=documents')
        fields['csrf'] = re.search(rb'name="csrf" value="([^"]+)"', html).group(1).decode()
        boundary = 'hope-test-boundary'
        body = b''
        for key, value in fields.items():
            body += f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
        body += f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{name}"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode() + content + f'\r\n--{boundary}--\r\n'.encode()
        req = urllib.request.Request(BASE+'/admin?tab=documents', body, {'Content-Type': 'multipart/form-data; boundary='+boundary})
        with self.opener.open(req) as res:
            return res.status, res.read()

fixture = subprocess.check_output(['docker','exec','hope-full-test','php','tests/fixture.php']).decode()
assert 'Fixture created' in fixture, fixture
visitor, user, admin, other = Client(), Client(), Client(), Client()
for page in ['', 'about', 'work', 'initiatives', 'impact', 'certificates', 'partnerships', 'membership', 'get-involved', 'donate', 'events', 'contact', 'gallery', 'donor-wall', 'privacy', 'login', 'register', 'verify']:
    code, html, _ = visitor.get('/'+page)
    assert code == 200 and b'<footer>' in html, (page, code, html[:200])
for path in ['/missing','/index.html','/app/schema.sql','/storage/hope.sqlite','/.git/config']:
    assert visitor.get(path)[0] == 404, path
assert b'Sign in' in visitor.get('/admin')[1]
assert visitor.post('/contact', {'action':'enquiry','csrf':'invalid'},False)[0] == 403
print('PASS: public routes, removed sample, private paths and CSRF')

code, html, _ = user.post('/register',dict(action='register',name='Test Member',email='member@example.test',password='MemberPassword!123',consent=1))
assert code == 200 and b'Submit' in html, html[:300]
code, html, _ = user.post('/membership',dict(action='membership',name='Test Member',email='member@example.test',mobile='9999999999',address='Test address',interest='Individual',message='I would like to help.',consent=1))
assert b'pending' in html.lower() and b'application was submitted' in html, html[:500]
assert user.get('/admin')[0] == 403
assert user.get('/member-card')[0] == 403
visitor.post('/contact',dict(action='enquiry',name='=Unsafe CSV',email='visitor@example.test',mobile='123',message='<script>alert(1)</script>',consent=1))
code, html, _ = admin.post('/login',dict(action='login',email='admin@example.test',password='TestAdminPassword!123'))
assert b'Unpaid pledges' in html and code == 200, html[:500]
assert b'&lt;script&gt;' in admin.get('/admin?tab=enquiries')[1]
code, html, _ = admin.post('/admin?tab=members',dict(action='review',id=1,status='approved',valid_until='2030-12-31',review_note='Welcome to the Foundation.'))
assert b'HOPE-000001' in html, html[:500]
for route in ['/member-card','/member-certificate']:
    code, pdf, headers = user.get(route)
    assert code == 200 and pdf.startswith(b'%PDF-'), (route, code, pdf[:300])
    assert headers.get_content_type() == 'application/pdf'
_, html, _ = visitor.get('/verify?member_id=HOPE-000001')
assert b'Approved' in html and b'member@example.test' not in html
other.post('/register',dict(action='register',name='Other User',email='other@example.test',password='OtherPassword!123',consent=1))
assert other.get('/member-card?id=1')[0] == 403
assert other.get('/export?kind=members')[0] == 403
print('PASS: registration, application, admin approval, private PDF cards/certificates, verification and account isolation')

code, html, _ = user.post('/donate',dict(action='donation',name='Test Member',email='member@example.test',mobile='9999999999',address='Test address',purpose='Rehabilitation',amount='1000',declaration='foreign',consent=1))
assert b'Foreign contributions are not accepted' in html
code, html, _ = user.post('/donate',dict(action='donation',name='Test Member',email='member@example.test',mobile='9999999999',address='Test address',purpose='Rehabilitation',amount='1000',declaration='domestic',consent=1,public_consent=1))
assert b'Unpaid pledge' in html and b'no donation receipt' in html
assert user.get('/receipt?id=1')[0] == 404
assert b'Test Member' not in visitor.get('/donor-wall')[1]
assert visitor.get('/webhook')[0] == 503
print('PASS: unpaid pledge recorded, foreign flow rejected, no false receipt or donor-wall publication')

admin.post('/admin?tab=content',dict(action='post',type='event',title='Community Camp',body='Published camp information',published=1))
assert b'Community Camp' in visitor.get('/events')[1]
admin.post('/admin?tab=content',dict(action='post',type='event',title='Private Draft',body='Draft information'))
assert b'Private Draft' not in visitor.get('/events')[1]
admin.post('/admin?tab=settings',dict(action='settings',phone='123456789',email='hello@example.test',address='Foundation office',mission='Our test mission',vision='Our test vision',founder_message='Test leader message',leadership='Test leader',registration_details=''))
assert b'Our test mission' in visitor.get('/about')[1]
assert b'Foundation office' in visitor.get('/contact')[1]
# A minimal valid PDF upload, private first then publish.
pdf=b'%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF'
admin.upload(dict(action='upload',title='Verified certificate',category='certificate'),'test.pdf',pdf)
assert visitor.get('/download?id=1')[0] == 404
assert admin.get('/download?id=1')[1] == pdf
admin.post('/admin?tab=documents',dict(action='document-status',id=1,published=1))
assert visitor.get('/download?id=1')[1] == pdf
assert b'Verified certificate' in visitor.get('/certificates')[1]
_, html = admin.upload(dict(action='upload',title='Invalid script',category='gallery'),'test.php',b'<?php echo "unsafe";')
assert b'Only PDF, JPEG, and PNG' in html
for kind in ['members','donations','enquiries']:
    code, data, headers = admin.get('/export?kind='+kind)
    assert code == 200 and headers.get_content_type() == 'text/csv'
    if kind == 'enquiries': assert b"'=Unsafe CSV" in data
    assert admin.get('/export?kind='+kind+'&format=pdf')[1].startswith(b'%PDF-')
admin.post('/admin?tab=members',dict(action='review',id=1,status='rejected',valid_until='2030-12-31',review_note='Test revocation'))
assert b'Rejected' in visitor.get('/verify?member_id=HOPE-000001')[1]
assert user.get('/member-card')[0] == 403
print('PASS: publishing/drafts, settings, uploads/publication, file rejection, CSV/PDF reports and member revocation')
print('All integration checks passed.')
