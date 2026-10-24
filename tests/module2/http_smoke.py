"""模块② HTTP 验收。只用于已导入演示数据的 worldcup_module2_test 测试库。

依赖 requests、beautifulsoup4；先设置 WC_TEST_DSN 与 WC_TEST_ADMIN_PASSWORD。
启动前后台测试服务后执行：python tests/module2/http_smoke.py
"""
import os
import re
import sys
from urllib.parse import urlparse, parse_qs

import requests
from bs4 import BeautifulSoup

sys.stdout.reconfigure(encoding='utf-8')

assert 'dbname=worldcup_module2_test' in os.environ.get('WC_TEST_DSN', ''), '仅允许专用测试库'
password = os.environ['WC_TEST_ADMIN_PASSWORD']
front = os.environ.get('WC_TEST_FRONT', 'http://127.0.0.1:18080')
back = os.environ.get('WC_TEST_BACK', 'http://127.0.0.1:18081')
assert all(urlparse(x).hostname in ('127.0.0.1', 'localhost') for x in (front, back)), '测试仅访问本机服务'
checks = 0


def check(condition, message):
    global checks
    assert condition, message
    checks += 1
    print('PASS:', message, flush=True)


def get(session, base, route, **params):
    return session.get(base + '/index.php', params={'r': route, **params}, timeout=20)


def submit(session, base, route, values, **params):
    page = get(session, base, route, **params)
    check(page.status_code == 200, route + ' 表单可打开')
    soup = BeautifulSoup(page.text, 'html.parser')
    token = soup.select_one('meta[name="csrf-token"]')['content']
    token_name = soup.select_one('meta[name="csrf-param"]')['content']
    return session.post(page.url, data={token_name: token, **values}, timeout=20)


guest = requests.Session()
for route in ('team/index', 'player/index'):
    r = get(guest, front, route)
    check(r.status_code == 200 and '共 ' in r.text, '游客可浏览 ' + route)
r = get(guest, front, 'team/index', continent='欧洲')
check('法国</a>' in r.text and '阿根廷</a>' not in r.text, '前台大洲筛选生效')
r = get(guest, front, 'player/index', teamId=1)
check('梅西</a>' in r.text and '姆巴佩</a>' not in r.text, '前台球员球队筛选生效')
r = get(guest, front, 'player/view', id=1)
check(r.status_code == 200 and '世界杯进球' in r.text and '<dd>2</dd>' in r.text, '球员详情展示演示决赛进球统计')
for route, params in [('team/view', {'id': 999999}), ('player/view', {'id': 999999}),
                      ('team/view', {'id': 1, 'tournamentId': 999999}),
                      ('team/view', {'id': 1, 'tournamentId[]': 1}),
                      ('player/view', {'id[]': 1})]:
    expected = (400, 404) if any(k.endswith('[]') for k in params) else (404,)
    check(get(guest, front, route, **params).status_code in expected, '不存在或非法的详情参数被拒绝')
check(get(guest, back, 'team/index').url.find('site%2Flogin') >= 0
      or 'site/login' in get(guest, back, 'team/index').url, '游客后台访问转到登录页')

normal = requests.Session()
submit(normal, back, 'site/login', {'LoginForm[username]': 'demo', 'LoginForm[password]': password})
check(get(normal, back, 'team/index').url.find('team/index') < 0, '普通账号不能通过后台登录')
submit(normal, front, 'site/login', {'LoginForm[username]': 'demo', 'LoginForm[password]': password})
check(get(normal, back, 'team/index').status_code != 200 or '球队管理' not in get(normal, back, 'team/index').text,
      '普通用户登录前台不会获得后台维护权限')

admin = requests.Session()
r = submit(admin, back, 'site/login', {'LoginForm[username]': 'admin', 'LoginForm[password]': password})
check('Logout (admin)' in r.text, '管理员可以登录后台')
for kind, payload in [('team', {'name': '验收球队', 'code': 'CHECK', 'continent': '亚洲'}),
                      ('player', {'name': '验收球员', 'nationality': '测试国籍', 'position': '前锋'})]:
    prefix = kind.title()
    r = submit(admin, back, kind + '/create', {f'{prefix}[{k}]': v for k, v in payload.items()})
    entity_id = parse_qs(urlparse(r.url).query)['id'][0]
    check(r.status_code == 200 and payload['name'] in r.text, '后台新增 ' + kind)
    r = submit(admin, back, kind + '/update', {f'{prefix}[name]': '验收修改<script>alert(1)</script>'}, id=entity_id)
    check('&lt;script&gt;' in r.text and '<script>alert(1)</script>' not in r.text, '修改持久化且后台转义脚本内容')
    r = get(admin, front, kind + '/view', id=entity_id)
    check('&lt;script&gt;' in r.text and '<script>alert(1)</script>' not in r.text, '前台详情转义脚本内容')
    check(get(admin, back, kind + '/delete', id=entity_id).status_code == 405, 'GET 不会删除资料')
    check(admin.post(back + '/index.php', params={'r': kind + '/delete', 'id': entity_id}, timeout=20).status_code == 400,
          '缺少 CSRF 的删除被拒绝')
    page = get(admin, back, kind + '/view', id=entity_id)
    soup = BeautifulSoup(page.text, 'html.parser')
    values = {soup.select_one('meta[name="csrf-param"]')['content']: soup.select_one('meta[name="csrf-token"]')['content']}
    r = admin.post(back + '/index.php', params={'r': kind + '/delete', 'id': entity_id}, data=values, timeout=20)
    check('记录已删除' in r.text and get(admin, front, kind + '/view', id=entity_id).status_code == 404, '后台删除无关联资料')

r = submit(admin, back, 'team/create', {'Team[name]': '阿根廷', 'Team[code]': 'ARG'})
check('已经被占用了' in r.text or 'has already been taken' in r.text, '后台显示重复球队校验错误')
page = get(admin, back, 'team/view', id=1)
soup = BeautifulSoup(page.text, 'html.parser')
values = {soup.select_one('meta[name="csrf-param"]')['content']: soup.select_one('meta[name="csrf-token"]')['content']}
r = admin.post(back + '/index.php', params={'r': 'team/delete', 'id': 1}, data=values, timeout=20)
check('不能删除历史数据' in r.text and get(admin, front, 'team/view', id=1).status_code == 200, '后台显示历史关联删除保护提示')
print(f'\n{checks} HTTP checks passed.')
