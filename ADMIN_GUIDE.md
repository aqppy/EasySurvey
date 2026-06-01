# 📊 EasySurvey - Admin Console Operation Guide / 后台管理操作指导手册

Welcome to the EasySurvey Administrator Console Guide! This manual is designed to help you quickly master the admin features of this survey platform—including questionnaire design, custom brand themes, local import/export, HD print物料 downloads, and statistical data visualization.

欢迎使用 EasySurvey 后台管理控制台！本手册旨在帮助您快速掌握各项管理功能，以最高效的方式完成问卷设计、品牌定制、本地导入导出、高清物料下载及数据分析工作。

[English Manual](#english-manual) | [简体中文使用指南](#%E7%AE%80%E4%BD%93%E4%B8%AD%E6%96%87%E4%BD%BF%E7%94%A8%E6%8C%87%E5%8D%97)

---

## English Manual

### 🧭 Table of Contents
1. [🎨 Multi-Indicator Dashboard](#1--multi-indicator-dashboard)
2. [📝 Survey Creator & Smart Editor](#2--survey-creator--smart-editor)
   - [Designing a New Survey](#designing-a-new-survey)
   - [Answer Consistency Guard](#answer-consistency-guard)
   - [⚡ High-Octane: CSV Schema Import & Export](#-high-octane-csv-schema-import--export)
3. [📊 Responses Viewer & Statistical Analytics](#3--responses-viewer--statistical-analytics)
   - [Data Charts & Offline Visualization](#data-charts--offline-visualization)
   - [Exporting Answer Sheets](#exporting-answer-sheets)
4. [📱 Printing-Grade HD QR Code Generator](#4--printing-grade-hd-qr-code-generator)
5. [⚙️ Global Settings & Brand Custonizer](#5--global-settings--brand-custonizer)
   - [Console Accent Themes picker](#console-accent-themes-picker)
   - [Responsive Deployed URL Adaptive mapping](#responsive-deployed-url-adaptive-mapping)
6. [🛠️ System Maintenance & Diagnostics](#6--system-maintenance--diagnostics)

---

### 1. 🎨 Multi-Indicator Dashboard
The **Dashboard** is the administrative homepage. It tracks live metrics across your platform:
- **Total Surveys**: All created surveys in the system.
- **Active Surveys**: Questionnaires currently online accepting submissions.
- **Total Responses**: Aggregate answer cards collected since deployment.
- **Quick Links**: Click cards to jump directly to target settings.

---

### 2. 📝 Survey Creator & Smart Editor
Navigate to **"Surveys"** in the navigation bar to manage the lifecycle of all surveys.

#### Designing a New Survey
Click **"Create New Survey"** to launch the questionnaire editor:
1. **Metadata**: Fill in the **Survey Title** and **Description** (supports leaving description blank).
2. **Adding Questions**: Click **"+ Add Question"** at the bottom:
   - **Question Types**: Supports **Single Choice (Radio)**, **Multiple Choice (Checkbox)**, and **Text Input (Textarea)**.
   - **Options**: Add/remove choice rows. Drag and drop rows or use buttons to reposition.
   - **Required Toggle**: Check **"Required"** to block submission if empty.
3. **Ordering**: Click **`↑`** or **`↓`** on the top-right of any question to immediately swap positions in real-time.
4. **Theme Customization** (located in the bottom/right panel):
   - **Survey Logo**: Upload a brand logo (PNG, JPG, WEBP; limit: 2MB).
   - **Header Image**: Upload a visual banner for the top of the survey.
   - **Background Image**: Set a tiled background visual.
   - **Button text**: Customize the submit button string (e.g., `Submit Questionnaire`).
   - **Layout switches**: Toggle whether to show the `Title`, `Description`, and `Question Numbers` to respondents.

#### Answer Consistency Guard
- **Cascade Deletion Protection**: When editing a survey that has already collected response sheets, trying to delete a question in the editor triggers a **loud red warning modal**. This prevents administrators from accidentally purging active question IDs and wipeout historical answers.

#### ⚡ High-Octane: CSV Schema Import & Export
To allow bulk local creation or questionnaire migrations across servers, EasySurvey features a fully bilingual **CSV Import & Export Engine**:

##### 1. Export CSV Schema
Click **"Export CSV Schema"** in the action list of any survey row:
- The system immediately compiles all questions, types, choice list, and validation flags into a single CSV.
- **Excel Native Integration**: The downloaded CSV has **embedded UTF-8 BOM signatures**, meaning it opens instantly inside Microsoft Excel on Windows **without any character scrambling**.

##### 2. Local One-Click CSV Import
In the editing page, click **"Import CSV Survey"** and choose a local file:
- **Auto-Charset Recognition**: Parses both `UTF-8` and Chinese standard `GBK / GB2312` structures automatically.
- **Flexible Separators**: Choice cells support separation by common characters like `|`, `/`, `,`, or `;`.
- **Sandbox Preview**: Parsed questions populate the editor immediately in your browser **before hitting the database**. You can drag, sort, edit, and review, then scroll to the bottom and click **"Save Survey"** to finalize.

---

### 3. 📊 Responses Viewer & Statistical Analytics
Navigate to the **"Data Viewer"** to analyze responses once your survey collection period closes.

#### Data Charts & Offline Visualization
- **Offline Chart.js Engine**: Uses a localized, dependency-free chart bundle. Even when deployed in an isolated intranet or VPS with strictly closed outbound traffic, it flawlessly renders responsive, colorful pie and bar charts to display answers proportion.
- **Response list**: Displays a reverse-chronological list of submissions with IP address and exact timestamp. Click **"View Details"** to inspect a responder's complete answers card.

#### Exporting Answer Sheets
- Click **"Export Submissions (CSV)"** to instantly download the raw responses data sheet (one responder per row, complete with timestamp, IP, and answer values) for professional SPSS, R-Studio, or Excel analysis.

---

### 4. 📱 Printing-Grade HD QR Code Generator
Navigate to **"QR Code"** in the navigation bar to distribute your surveys.
- **Zero-Config Protocol Mapping**: The generator auto-detects your active deployed host protocol (HTTPS or HTTP, Localhost, private LAN IP, or public domain) to build a link.
- **Off-screen Canvas Engine**:
  - **Fluid UI Preview**: Displays a 290x290 layout for instant loading.
  - **1024x1024 PNG Download**: When clicking **"Download QR Code"**, the system calls an off-screen off-RAM Canvas element to generate a lossless, crisp **1024x1024 pixels PNG**. This HD file is suitable for print ads, roll-up banners, and high-DPI posters without edge blur.

---

### 5. ⚙️ Global Settings & Brand Custonizer
Navigate to the **"Settings"** control panel to customize your platform.

#### Console Accent Themes picker
- **System Title**: Customize the system name shown on page titles and sidebars.
- **System Theme Color Picker**: Custom Color Picker updates sidebars, buttons, active highlights, and state labels across the entire admin panel.
- **System Logo**: Upload a logo to update the console header.
  - **Garbage collection**: Changing or removing the logo automatically deletes the old file from the filesystem.
- **Tab template**: Customize tab titles (e.g. `{page} - {app}` dynamically renders `Customer Review - Survey Platform`).
- **Footer copyright**: Customize bottom footer strings (e.g., `Copyright © 2026 Corp`) synchronized across public and admin pages.
- **System Default Language**: Change default language between Chinese and English for browsers without a preset language preference.

#### Responsive Deployed URL Adaptive mapping
- **Web Site Base URL**: Enter your main VPS domain or local base URL (e.g. `https://test.gdmrcare.com`). Used as fallback for formatting preview URLs.

---

### 6. 🛠️ System Maintenance & Diagnostics
1. **One-Click PDO Database Backup**:
   - EasySurvey includes a CLI database backup script located in `scripts/backup.php` (no dependency on physical `mysqldump`).
   - Run the script in your terminal to export structures and records:
     ```bash
     php scripts/backup.php
     ```
2. **Environment Diagnostics**:
   - Visit `/check_env.php` to run the diagnostic panel, auditing PHP versions, PDO configurations, upload folder write-permissions, and system memory limits.
3. **Upload Constraints**:
   - The platform strictly bans `.svg` format uploads at the server level, neutralizing potential XML-XSS attacks.
   - **Write permissions**: Ensure the `public/uploads/` directory on your VPS is writable by the web server process (e.g., `chmod -R 777 public/uploads`).

---
---

## 简体中文使用指南

### 🧭 目录
1. [🎨 多维指标仪表盘](#1--多维%E6%8C%87%E6%A0%87%E4%BB%AA%E8%A1%A8%E7%9B%98)
2. [📝 问卷管理与可视化编辑器](#2--%E9%97%AE%E5%8D%B7%E7%AE%A1%E7%90%86%E4%B8%8E%E5%8F%AF%E8%A7%86%E5%8C%96%E7%BC%96%E8%BE%91%E5%99%A8)
   - [设计新问卷](#设计%E6%96%B0%E9%97%AE%E5%8D%B7)
   - [历史答案防丢失保护](#%E5%8E%86%E5%B0%8F%E7%AD%94%E6%A1%88%E9%98%B2%E4%B8%A2%E5%A4%B1%E4%BF%9D%E6%8A%A4)
   - [⚡ 进阶特性：CSV 结构导入导出](#-进阶%E7%89%B9%E6%80%A7csv-%E7%BB%93%E6%9E%84%E5%AF%BC%E5%85%A5%E5%AF%BC%E5%87%BA)
3. [📊 数据查看与图表统计](#3--%E6%95%B0%E6%8D%AE%E6%9F%A5%E7%9C%8B%E4%B8%8E%E5%9B%BE%E8%A1%A8%E7%BB%9F%E8%AE%A1)
   - [Chart.js 纯离线图表渲染](#chartjs-%E7%BA%AF%E7%A6%BB%E7%BA%BF%E5%9B%BE%E8%A1%A8%E6%B8%B2%E6%9F%93)
   - [明细数据导出](#%E6%98%8E%E7%BB%86%E6%95%B0%E6%8D%AE%E5%AF%BC%E5%87%BA)
4. [📱 印刷级高清二维码生成](#4--%E5%8D%B0%E5%88%B7%E7%BA%A7%E9%AB%98%E6%B8%85%E4%BA%8C%E7%BB%B4%E7%A0%81%E7%94%9F%E6%88%90)
5. [⚙️ 全局设置与品牌个性化](#5--%E5%85%A8%E5%B1%80%E8%AE%BE%E7%BD%AE%E4%B8%8E%E5%93%81%E7%89%8C%E4%B8%AA%E6%80%A7%E5%8C%96)
   - [系统主题色一键更换](#%E7%B3%BB%E7%BB%9F%E4%B8%BB%E9%A2%98%E8%89%B2%E4%B8%80%E9%94%AE%E6%9B%B4%E6%8D%A2)
   - [零配置自适应分享链接](#%E9%9B%B6%E9%85%8D%E7%BD%AE%E8%87%AA%E9%80%82%E5%BA%94%E5%88%86%E4%BA%AB%E9%93%BE%E6%8E%A5)
6. [🛠️ 系统维护与安全加固](#6--%E7%B3%BB%E7%BB%9F%E7%BB%B4%E6%8A%A4%E4%B8%8E%E5%AE%89%E5%85%A8%E5%8A%A0%E5%9B%BA)

---

### 1. 🎨 多维指标仪表盘
登录进入后台控制台，首屏即展示极富毛玻璃美感的**仪表盘**：
- **核心数据指标**：
  - **问卷总数**：系统中已创建的全部问卷。
  - **启用中问卷**：前台正在开放并可供提交填写的活跃问卷。
  - **收集回答数**：全平台累计收集到的有效答卷总数。
- **快速跳转入口**：点击对应指标卡片可直达相关的管理页面，操作灵敏。

---

### 2. 📝 问卷管理与可视化编辑器
点击后台顶部导航菜单 **「问卷管理」** 查看所有问卷列表，可控制问卷的启用状态、编辑内容、查看数据以及删除问卷。

#### 设计新问卷
点击 **「创建新问卷」** 即可打开高度互动的问卷编辑器：
1. **基础信息**：编辑问卷的标题和展示说明。
2. **题目设计**：点击底部的 **「+ 添加题目」**：
   - **经典题型**：支持**单选题**、**多选题**、以及**文本问答题**。
   - **选项管理**：针对单选和多选题，可任意添加、删除选项。支持手动点击 `↑`/`↓` 或通过拖动实时调整选项顺序。
   - **验证条件**：可勾选开启该题目为 **「必填项」**。
3. **题目排序**：题目编辑卡片右上角提供 `↑` / `↓` 动作键，支持直接调整题目排序，前台展示与数据库将实时自适应智能题号重整。
4. **个性化主题设置**（右侧或底端参数面板）：
   - **问卷专属 Logo**：上传专属 Logo（支持 PNG, JPG, WEBP，大小限制 2MB）。
   - **问卷头图**：上传展示于问卷最上方的大尺寸精美视觉 Banner。
   - **问卷背景图**：可上传整体背景底纹。
   - **提交按钮文字**：自定义前台填写表单按钮的文本（例如：`提交问卷`、`立即发送`）。
   - **元素开关**：支持开关问卷标题、问卷说明以及题目序号在前台页面上的显示。

#### 历史答案防丢失保护
- **级联删除阻断**：当您编辑已收集有答卷的问卷时，如果尝试在编辑器内删除某个旧题目，前端会触发**强警示红色防呆弹窗**。系统在此类编辑保存时，只会物理清除管理员明确要求移除的行，而对其他被调序、修改标题的行执行 `UPDATE`，**彻底杜绝传统问卷系统编辑导致历史数据连带丢失的严重架构缺陷**。

#### ⚡ 进阶特性：CSV 结构导入导出
为了极大提升批量出题的效率，以及实现在不同部署服务器之间的问卷快速迁移，系统集成了强大的 **CSV 双语数据交换引擎**：

##### 1. 导出 CSV 问卷结构
在问卷列表对应行右侧，点击 **「导出 CSV 结构」**：
- 系统会自动导出包含整份问卷题目名称、题型、必填状态、选项的标准 CSV 表格。
- **中文 Excel 友好**：导出的 CSV 文件**内置 UTF-8 BOM 签名**。在 Windows 操作系统中双击使用 Microsoft Excel 打开**绝对不会产生中文乱码**，编辑极其顺畅。

##### 2. 本地一键 CSV 导入
在编辑或创建问卷页面，点击 **「导入 CSV 问卷」** 并选择您的 CSV 文件：
- **高兼容编码识别**：导入解析器可智能识别 `UTF-8` 与 Windows 下 Excel 生成的 `GBK / GB2312` 编码格式。
- **高宽容选项拆分**：选项文本完美兼容 `|`, `/`, `,` 以及 `;` 等多种分隔符。
- **安全沙盒机制**：解析后的题目会**瞬间呈现在当前浏览器的编辑器中**（此时未入库），管理员可对其进行精细微调或增删，确认无误后拉到最下方点击 **「保存问卷」** 即可入库，大幅度提升误操作冗余度。

---

### 3. 📊 数据查看与图表统计
点击导航菜单的 **「数据查看」**。这是收集结束后管理员的核心数据汇总区。

#### Chart.js 纯离线图表渲染
- **本地化离线加载**：系统完全本地化引入了 Chart.js UMD 驱动包。**即使您的服务器部署在完全与互联网断开的专网或局域网纯内网环境下**，系统依然能够零阻碍渲染出动感的彩色统计饼图、柱状图，实时统计单选和多选题的选项占比。
- **答卷明细卡片**：在图表统计下方，以时间倒序详细列出每份答卷的提交 IP、时间。点击 **「查看详情」** 可瞬间打开该填答者的答案答卷详情卡。

#### 明细数据导出
- 点击右上角的 **「导出回答明细 (CSV)」**，一键导出所有原始回答数据明细（一行代表一份答卷，包含提交时间、IP、每道题的完整回答），方便您无缝导入 SPSS、Excel、R 语言等专业分析软件中。

---

### 4. 📱 印刷级高清二维码生成
点击导航菜单的 **「二维码生成」**，将您的问卷推送至广大填答者。
- **自适应部署 URL**：系统会自动侦测并抓取当前的物理部署地址（包括动态适配 HTTPS/HTTP 协议，Localhost 本地测试，局域网 IP，以及公网 VPS 域名），**实现零配置智能映射**。
- **后台 Canvas 超清离线技术**：
  - **流畅前台预览**：前台渲染 290x290 规格的轻量化二维码，加载极速。
  - **1024x1024 超清下载**：当您点击 **「下载二维码」** 按钮时，系统会隐式调取后台离屏 Canvas，渲染并下载**边角极其锐利、1024x1024 像素的印刷级 PNG 图片**，完全能够满足印刷海报、X展架和易拉宝的超高清输出需求。

---

### 5. ⚙️ 全局设置与品牌个性化
点击导航栏 **「系统设置」** 定制平台外观属性。

#### 系统主题色一键更换
- **系统名称**：更改显示在后台和登录大屏的系统大标题。
- **系统主题色 (Accent Theme)**：
  - 提供专业的拾色器。您可以选择符合企业 VI 规范的任意主题色。
  - 保存后，后台系统的左侧菜单、高亮按钮、标签元素**将无缝自适应变换为您的专属主题色**，视觉风格瞬间一体化。
- **系统专属 Logo**：上传系统主 Logo 以自动替换后台左上角的品牌图。
  - **无用垃圾自动物理清理**：在您上传新 Logo 或删除旧 Logo 时，后台会自动彻底删除服务器底层的历史旧图片，**杜绝产生空间占用垃圾**。
- **浏览器标题模板**：支持通配符（如 `{page} - {app}` 将渲染为 `系统设置 - 问卷调查系统`）。
- **页脚版权文本**：输入您的版权文字（如 `Copyright © 2026 XX公司`），前台问卷与后台底部会动态显示。
- **系统默认语言**：可一键在“简体中文”与“English”间切换，用作无预设偏好浏览器访问时的默认兜底语言。

#### 零配置自适应分享链接
- **站点基础地址**：填写您本地局域网地址或 VPS 公网域名（例如 `https://test.gdmrcare.com`）。用于格式化预览和二维码地址。

---

### 6. 🛠️ 系统维护与安全加固
1. **PDO 命令行数据库备份**：
   - 系统在 `scripts/` 目录下放置了 Pure PHP 架构的备份脚本 `backup.php`（不依赖主机的 `mysqldump` 可执行文件）。
   - 在终端或定时任务 Cron 中执行以下命令，一键将当前数据备份至 `backups/` 文件夹中：
     ```bash
     php scripts/backup.php
     ```
2. **环境自检页面**：
   - 随时在浏览器访问 `/check_env.php`，一键可视化复核 PHP 版本、PDO 连通性、文件写权限及上传容量限制。
3. **安全加固规范**：
   - 服务器底层彻底剔除了 `.svg` 格式图片的上传支持，**杜绝了一切借助 XML 嵌入脚本的 XSS 攻击漏洞**。
   - **可写权限**：请确保 VPS 服务器上的 `public/uploads/` 目录可被 Web 进程写入（可执行 `chmod -R 777 public/uploads` 加固）。

---

祝您使用愉快！如有任何二次开发或部署问题，请查阅根目录下的 [README.md](file:///e:/VibeCoding/test.gdmrcare.com/README.md) 或运行环境一键检测页。
