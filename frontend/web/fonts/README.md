# 自托管字体

## Saira Condensed

- 来源：Google Fonts（<https://fonts.google.com/specimen/Saira+Condensed>）
- 许可：SIL Open Font License 1.1（OFL）
- 文件：`SairaCondensed-600.woff2`、`SairaCondensed-700.woff2`（拉丁子集）
- 用途：仅用于年份、比分、统计数字。

## Smiley Sans / 得意黑

- 来源：atelierAnchor（<https://github.com/atelier-anchor/smiley-sans>），v2.0.1
- 许可：SIL Open Font License 1.1，见 `SmileySans-OFL.txt`
- 文件：
  - `SmileySans-Oblique.woff2`：完整字体（约 1.1MB，保留作重新生成字集的源文件）
  - `SmileySans-Subset.woff2`：站内字集（1234 字形，约 135KB）
- 用途：只用于标题、队名、展签等展示字；**不用于正文、表格和小字号**。
- 注意：字形自带斜度，拉丁人名（如 Mbappe）不要用它排，避免看起来像斜体引用。
- 字集覆盖：前后台 PHP 文案、页面实际渲染的标题字，以及数据库中的队名 / 球员名 / 届次 / 资讯 / 留言正文。
- 重新生成：`php tools/build_font_subset.php`，把 `runtime/SmileySans-Subset.woff2` 复制到 `frontend/web/fonts/` 与 `backend/web/fonts/`。新增生僻字（例如新的队名、译名）后需要重跑，否则这些字会回退到系统字体。
- 加载：`font-display: optional` + 布局中的 `<link rel="preload">`；字体未在首屏前就绪时直接使用回退字，不产生切页时的字体跳变。
