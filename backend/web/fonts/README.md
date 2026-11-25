# 后台字体

## Smiley Sans / 得意黑

- 来源：atelierAnchor（<https://github.com/atelier-anchor/smiley-sans>），v2.0.1
- 许可：SIL Open Font License 1.1，见 `SmileySans-OFL.txt`
- 文件：
  - `SmileySans-Oblique.woff2`：完整字体（保留作源文件）
  - `SmileySans-Subset.woff2`：与前台同一份站内字集（1234 字形，约 135KB）
- 用途：后台页标题、面板标题、工作间品牌字；**不用于表格正文、表单标签和按钮**。
- 与前台同一份字体，但后台只在大字号使用，保持工作间安静。
- 加载：`font-display: block` + 布局预加载；后台单独放一份文件，避免跨端口取字体失败。`optional` 在 100ms 内到达仍会换字，切页能看出标题字形跳变；`block` 让标题等预加载字体，通常一帧内完成。
- 品牌方块的 `WC` 不走外链字体，改用 `admin-r10.css` 内嵌的 Saira 子集；切页时不再先排回退字再跳成 Saira。

## Saira Condensed

- 来源：Google Fonts（<https://fonts.google.com/specimen/Saira+Condensed>）
- 许可：SIL Open Font License 1.1
- 文件：`SairaCondensed-700.woff2`（拉丁子集）
- 用途：工作台数字、编号、日期和构建标记。
