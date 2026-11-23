#!/usr/bin/env python3
"""Module 1 public pages and administrator access smoke tests."""

import http.cookiejar
import os
import re
import sys
import urllib.parse
import urllib.request


FRONT = os.environ.get("WC_TEST_FRONT", "http://127.0.0.1:8080")
BACK = os.environ.get("WC_TEST_BACK", "http://127.0.0.1:8081")
PASSWORD = os.environ.get("WC_TEST_ADMIN_PASSWORD", "admin123")


def fetch(opener, url, data=None):
    encoded = urllib.parse.urlencode(data).encode() if data else None
    with opener.open(url, encoded, timeout=20) as response:
        return response.status, response.read().decode("utf-8", "replace")


def check(condition, message):
    if not condition:
        print(f"FAIL: {message}", file=sys.stderr)
        raise SystemExit(1)
    print(f"PASS: {message}")


jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))

status, html = fetch(opener, f"{FRONT}/index.php?r=team-info%2Findex")
check(status == 200 and "团队成员" in html, "前台团队展示页")
check(html.count("member-card") == 4, "展示四位成员")

status, html = fetch(opener, f"{FRONT}/index.php?r=team-info%2Fdownloads")
check(status == 200 and "作业下载" in html, "前台作业下载页")

status, login_html = fetch(opener, f"{BACK}/index.php?r=site%2Flogin")
match = re.search(r'name="_csrf-backend"\s+value="([^"]+)"', login_html)
check(status == 200 and match is not None, "后台登录页和 CSRF 令牌")

status, html = fetch(
    opener,
    f"{BACK}/index.php?r=site%2Flogin",
    {
        "_csrf-backend": match.group(1),
        "LoginForm[username]": "admin",
        "LoginForm[password]": PASSWORD,
        "LoginForm[rememberMe]": "1",
    },
)
check(status == 200 and "退出登录" in html, "管理员登录")

for route, marker in [
    ("team-info%2Findex", "团队信息"),
    ("member%2Findex", "成员管理"),
    ("work%2Findex", "作业管理"),
]:
    status, html = fetch(opener, f"{BACK}/index.php?r={route}")
    check(status == 200 and marker in html, f"后台 {marker} 页面")

print("module1 HTTP smoke tests passed")
