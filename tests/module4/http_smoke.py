"""模块④ HTTP 验收。

仅用于已导入 install.sql 和 demo_teams_players.sql 的 worldcup_module4_test
测试库。测试会创建带唯一前缀的资讯和留言，软删除留言并物理删除测试资讯。
测试库应可随时重建，不要在业务库上运行。

依赖：requests。

PowerShell 示例：
    $env:WC_TEST_DSN = 'mysql:host=127.0.0.1;dbname=worldcup_module4_test'
    $env:WC_TEST_ADMIN_PASSWORD = '测试库管理员密码'
    python tests/module4/http_smoke.py
"""

import html
import os
import re
import sys
import uuid
from urllib.parse import urlparse

import requests

sys.stdout.reconfigure(encoding="utf-8")

assert "dbname=worldcup_module4_test" in os.environ.get(
    "WC_TEST_DSN", ""
), "仅允许专用 worldcup_module4_test 测试库"

admin_password = os.environ["WC_TEST_ADMIN_PASSWORD"]
user_password = os.environ.get("WC_TEST_USER_PASSWORD", admin_password)
front = os.environ.get("WC_TEST_FRONT", "http://localhost:18080")
back = os.environ.get("WC_TEST_BACK", "http://localhost:18081")
assert all(
    urlparse(url).hostname in ("127.0.0.1", "localhost")
    for url in (front, back)
), "测试仅允许访问本机服务"

prefix = "MODULE4HTTP" + uuid.uuid4().hex[:10]
checks = 0
created_comments = []
news_id = None
admin = None
demo = None

PNG = (
    b"\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01"
    b"\x00\x00\x00\x01\x08\x04\x00\x00\x00\xb5\x1c\x0c\x02"
    b"\x00\x00\x00\x0bIDATx\xda\x63d\xf8\x0f\x00\x01\x05"
    b"\x01\x01'\x18\xe3f\x00\x00\x00\x00IEND\xaeB`\x82"
)


def check(condition, message):
    global checks
    assert condition, message
    checks += 1
    print("PASS:", message, flush=True)


def get(session, base, route, **params):
    return session.get(
        base + "/index.php",
        params={"r": route, **params},
        timeout=20,
        allow_redirects=True,
    )


def get_csrf(content, scope):
    patterns = [
        rf'name="_csrf-{scope}" value="([^"]+)"',
        r'name="csrf-token" content="([^"]+)"',
    ]
    for pattern in patterns:
        match = re.search(pattern, content)
        if match:
            return html.unescape(match.group(1))
    raise AssertionError(f"{scope} CSRF token not found")


def login(session, base, scope, username, password):
    page = get(session, base, "site/login")
    check(page.status_code == 200, f"{scope} login page is available")
    token = get_csrf(page.text, scope)
    return session.post(
        base + "/index.php",
        params={"r": "site/login"},
        data={
            f"_csrf-{scope}": token,
            "LoginForm[username]": username,
            "LoginForm[password]": password,
        },
        timeout=20,
        allow_redirects=True,
    )


def submit_comment(session, target_type, target_id, content, parent_id=None):
    page = get(session, front, "team/view", id=1)
    token = get_csrf(page.text, "frontend")
    data = {
        "_csrf-frontend": token,
        "CommentForm[targetType]": target_type,
        "CommentForm[targetId]": target_id,
        "CommentForm[content]": content,
    }
    if parent_id is not None:
        data["CommentForm[parentId]"] = parent_id
    response = session.post(
        front + "/index.php",
        params={"r": "comment/create"},
        data=data,
        timeout=20,
        allow_redirects=True,
    )
    comment_id = comment_id_for(response.text, content)
    if comment_id is not None:
        created_comments.append(comment_id)
    return response, comment_id


def comment_id_for(content, marker):
    starts = list(
        re.finditer(r'<article class="comment-item" id="comment-(\d+)">', content)
    )
    for index, match in enumerate(starts):
        end = starts[index + 1].start() if index + 1 < len(starts) else len(content)
        block = content[match.start() : end]
        if marker in block:
            return int(match.group(1))
    return None


def post_action(session, base, scope, route, **params):
    page = get(session, base, "team/view", id=1)
    token = get_csrf(page.text, scope)
    return session.post(
        base + "/index.php",
        params={"r": route, **params},
        data={f"_csrf-{scope}": token},
        timeout=20,
        allow_redirects=True,
    )


def delete_comment(session, comment_id):
    try:
        return post_action(session, front, "frontend", "comment/delete", id=comment_id)
    except (AssertionError, requests.RequestException):
        return None


try:
    guest = requests.Session()
    team_page = get(guest, front, "team/view", id=1)
    check(team_page.status_code == 200, "游客可以打开球队详情")
    check("comment-login-prompt" in team_page.text, "游客看到留言登录提示")
    check("CommentForm[content]" not in team_page.text, "游客看不到留言输入框")
    check("community.css" in team_page.text, "留言组件加载公共样式")

    demo = requests.Session()
    result = login(demo, front, "frontend", "demo", user_password)
    check("Logout (demo)" in result.text, "普通用户登录前台")

    root_content = prefix + "-root"
    root_response, root_id = submit_comment(demo, "team", 1, root_content)
    check(root_id is not None and root_content in root_response.text, "发表球队留言")

    reply_content = prefix + "-reply"
    reply_response, reply_id = submit_comment(
        demo, "team", 1, reply_content, root_id
    )
    check(reply_id is not None and reply_content in reply_response.text, "发表同目标回复")

    player_content = prefix + "-player"
    player_response, player_comment_id = submit_comment(
        demo, "player", 1, player_content
    )
    check(
        player_comment_id is not None and player_content in player_response.text,
        "球员详情页可以发表留言",
    )

    xss_content = prefix + "-xss<script>alert(1)</script>"
    xss_response, xss_id = submit_comment(demo, "team", 1, xss_content)
    if xss_id is None:
        xss_id = comment_id_for(xss_response.text, prefix + "-xss")
    check(
        xss_id is not None
        and "&lt;script&gt;alert(1)&lt;/script&gt;" in xss_response.text,
        "留言脚本内容被转义",
    )
    check(
        "<script>alert(1)</script>" not in xss_response.text,
        "页面不输出可执行留言脚本",
    )

    cross_content = prefix + "-cross"
    cross_response, cross_id = submit_comment(
        demo, "player", 1, cross_content, root_id
    )
    check(cross_id is None, "跨实体回复被拒绝")
    check("回复必须属于同一个留言目标" in cross_response.text, "显示跨实体回复错误")

    liked = post_action(demo, front, "frontend", "comment/like", id=root_id)
    check("赞 1" in liked.text, "留言可以点赞")
    liked_again = post_action(demo, front, "frontend", "comment/like", id=root_id)
    check(liked_again.text.count("赞 1") >= 1, "重复点赞不增加计数")

    no_csrf = demo.post(
        front + "/index.php",
        params={"r": "comment/create"},
        data={
            "CommentForm[targetType]": "team",
            "CommentForm[targetId]": 1,
            "CommentForm[content]": prefix + "-no-csrf",
        },
        timeout=20,
    )
    check(no_csrf.status_code == 400, "缺少 CSRF 的留言被拒绝")

    admin = requests.Session()
    admin_login = login(admin, back, "backend", "admin", admin_password)
    check("Logout (admin)" in admin_login.text, "管理员登录后台")

    create_page = get(admin, back, "news/create")
    token = get_csrf(create_page.text, "backend")
    draft = admin.post(
        back + "/index.php",
        params={"r": "news/create"},
        data={
            "_csrf-backend": token,
            "News[title]": prefix + "-draft",
            "News[author]": "Tester",
            "News[status]": "0",
            "News[content]": "<p>Draft</p>",
        },
        timeout=20,
        allow_redirects=True,
    )
    match = re.search(r"[?&]id=(\d+)", draft.url)
    check(match is not None, "后台新增草稿资讯")
    news_id = int(match.group(1)) if match else None
    check(
        news_id is not None
        and get(guest, front, "news/view", id=news_id).status_code == 404,
        "草稿资讯不在前台公开",
    )

    update_page = get(admin, back, "news/update", id=news_id)
    token = get_csrf(update_page.text, "backend")
    published = admin.post(
        back + "/index.php",
        params={"r": "news/update", "id": news_id},
        data={
            "_csrf-backend": token,
            "News[title]": prefix + "-published",
            "News[author]": "Tester",
            "News[status]": "1",
            "News[content]": "<p>Published</p><script>alert(1)</script>",
        },
        files={"News[coverFile]": ("cover.png", PNG, "image/png")},
        timeout=20,
        allow_redirects=True,
    )
    check("资讯已保存" in published.text, "后台发布资讯并上传封面")

    news_page = get(guest, front, "news/view", id=news_id)
    check(news_page.status_code == 200 and "Published" in news_page.text, "发布资讯前台可见")
    check("<script>alert(1)</script>" not in news_page.text, "资讯危险脚本被清理")
    cover_match = re.search(r'src="([^"]*uploads/news/[^"]+)"', news_page.text)
    check(cover_match is not None, "资讯详情展示上传封面")
    if cover_match:
        cover_path = html.unescape(cover_match.group(1))
        check(
            guest.get(front + cover_path, timeout=20).status_code == 200,
            "上传封面可由前台访问",
        )

    news_comment_content = prefix + "-news"
    news_comment_response, news_comment_id = submit_comment(
        demo, "news", news_id, news_comment_content
    )
    check(
        news_comment_id is not None
        and news_comment_content in news_comment_response.text,
        "资讯详情页可以发表留言",
    )

    comment_index = get(admin, back, "comment/index")
    check(comment_index.status_code == 200, "管理员可以打开留言管理")
    check(prefix + "-root" in comment_index.text, "后台显示留言")
    by_content = get(admin, back, "comment/index", q=root_content)
    check(root_content in by_content.text and player_content not in by_content.text, "后台按内容筛选")
    by_target = get(admin, back, "comment/index", targetType="player")
    check(player_content in by_target.text and root_content not in by_target.text, "后台按目标筛选")
    by_user = get(admin, back, "comment/index", q="demo")
    check(root_content in by_user.text, "后台按用户名筛选")

    normal_backend = requests.Session()
    normal_login = login(normal_backend, back, "backend", "demo", user_password)
    check("Logout (demo)" not in normal_login.text, "普通用户不能登录后台")
    normal_comment_index = get(normal_backend, back, "comment/index")
    check("留言管理" not in normal_comment_index.text, "普通用户不能访问留言管理")

    admin_delete = post_action(
        admin, back, "backend", "comment/delete", id=player_comment_id
    )
    admin_active = get(admin, back, "comment/index", status=0)
    check(player_content not in admin_active.text, "管理员可以删除他人留言")

    owner_delete = post_action(
        demo, front, "frontend", "comment/delete", id=root_id
    )
    check(
        root_content not in owner_delete.text and reply_content not in owner_delete.text,
        "作者删除父留言并隐藏回复树",
    )
    delete_comment(demo, xss_id)

    delete_page = get(admin, back, "news/view", id=news_id)
    token = get_csrf(delete_page.text, "backend")
    deleted_news = admin.post(
        back + "/index.php",
        params={"r": "news/delete", "id": news_id},
        data={"_csrf-backend": token},
        timeout=20,
        allow_redirects=True,
    )
    check("资讯及其留言已删除" in deleted_news.text, "删除资讯及其留言")
    check(get(guest, front, "news/view", id=news_id).status_code == 404, "删除后资讯前台不可见")
    news_id = None

    print(f"\n{checks} module4 HTTP checks passed.")
finally:
    if news_id is not None and admin is not None:
        try:
            page = get(admin, back, "news/view", id=news_id)
            if page.status_code == 200:
                token = get_csrf(page.text, "backend")
                admin.post(
                    back + "/index.php",
                    params={"r": "news/delete", "id": news_id},
                    data={"_csrf-backend": token},
                    timeout=20,
                )
        except (AssertionError, requests.RequestException):
            pass
    if demo is not None:
        for comment_id in reversed(created_comments):
            delete_comment(demo, comment_id)
