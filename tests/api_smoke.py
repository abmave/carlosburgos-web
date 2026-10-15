"""Run only against a disposable test database; publishes then restores homepage content.
CB_TEST_API, CB_TEST_EMAIL and CB_TEST_PASSWORD must be set externally.
"""
import http.cookiejar, json, os, urllib.request, urllib.error
base = os.environ.get('CB_TEST_API', 'http://127.0.0.1:8080/api').rstrip('/')
email, password = os.environ['CB_TEST_EMAIL'], os.environ['CB_TEST_PASSWORD']
jar = http.cookiejar.CookieJar()
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def call(path, method='GET', data=None, csrf=None, status=200):
    headers = {'Content-Type': 'application/json'}
    if csrf: headers['X-CSRF-Token'] = csrf
    req = urllib.request.Request(base+path, json.dumps(data).encode() if data is not None else None, headers, method=method)
    try: response = client.open(req)
    except urllib.error.HTTPError as e: response = e
    raw = response.read()
    try: payload = json.loads(raw)
    except ValueError: raise AssertionError((response.status, raw[:1200]))
    assert response.status == status, (path, response.status, payload)
    return payload
call('/admin/settings', status=401)
session = call('/admin/session')
call('/admin/login','POST',{'email':email,'password':password},status=403)
call('/admin/login','POST',{'email':email,'password':'incorrect-password'},session['csrf'],401)
session = call('/admin/login','POST',{'email':email,'password':password},session['csrf'])
csrf = session['csrf']
assert session['user']['email'] == email
assert call('/admin/session')['user']['id'] == session['user']['id']
assert list(jar)[0].has_nonstandard_attr('HttpOnly')
original = call('/admin/settings')
changed = dict(original['content'], heroTitle='Prueba de publicación CMS')
call('/admin/settings','PUT',{'content':changed,'version':original['version']},status=403)
invalid = dict(changed,whatsapp='javascript:alert(1)')
call('/admin/settings','PUT',{'content':invalid,'version':original['version']},csrf,422)
saved = None
try:
    saved = call('/admin/settings','PUT',{'content':changed,'version':original['version']},csrf)
    assert saved['version'] == original['version']+1
    assert call('/public/settings')['content']['heroTitle'] == changed['heroTitle']
    call('/admin/settings','PUT',{'content':changed,'version':original['version']},csrf,409)
finally:
    if saved: call('/admin/settings','PUT',{'content':original['content'],'version':saved['version']},csrf)
call('/admin/logout','POST',{},csrf)
call('/admin/settings',status=401)
print('PASS: login, cookie, session, access control, CSRF, validation, publication, version conflict and logout')
