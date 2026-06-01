# 📝 EasySurvey: Lightweight Premium Survey System / 轻量级高颜值双语问卷系统

[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.0-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/aqppy/EasySurvey)

[English](#english-documentation) | [简体中文](#%E7%AE%80%E4%BD%93%E4%B8%AD%E6%96%87%E6%96%87%E6%A1%A3)

---

## English Documentation

EasySurvey is a minimalist, ultra-lightweight, and premium-designed survey system built on **PHP 8.x + MySQL**. It is completely self-contained with **zero external package dependencies** (no Composer, no Node.js runtime required). It is designed to be intranet-ready, zero-config, and highly secure—perfect for corporate internal surveys, small-scale feedback collection, and quick on-premises deployments.

### 🌟 Key Features

- 🎨 **Dynamic Brand Customization**: Custom color picker dynamically updates the administration panel. For front-end surveys, admins can customize accents, background colors, header images, background textures, and submit buttons.
- 🌐 **Session-based Bilingual Switcher**: Seamlessly toggle between **English** and **简体中文** instantly via a navbar button. The user’s preference is persisted in Session/Cookie storage without messing with query parameters, while a global "System Default Language" is stored in the DB.
- 📝 **Smart Drag-and-Drop Editor**: Build Single Choice, Multiple Choice, and Text questions. Supports seamless drag-and-drop or button-triggered reordering with dynamic question numbering.
- 🛡️ **Answer Consistency Protection**: Avoids the fatal flaw of historical data loss when editing questions. Deleting questions with active responses prompts a warning, and only explicitly deleted questions are removed without wiping unrelated columns.
- 📥 **CSV Schema Excel Import/Export**: Export survey schemas to CSV with an **embedded UTF-8 BOM header** for native, scramble-free opening in Microsoft Excel. Supports uploading structured CSVs to instantly populate the visual editor.
- 📈 **Fully Offline Analytics & Export**: Embeds a fully localized, offline **Chart.js** bundle. Generate gorgeous responsive bar/pie charts and statistical summaries, and export answer sheets to a standard CSV file with one click.
- 📱 **1024x1024 HD Print-Grade QR Code**: Dynamically detects the current deployed protocol and host (localhost/LAN/VPS domain) to generate QR codes with zero config. Taps into off-screen Canvas to let you download printing-grade 1024x1024 PNGs.
- 🔒 **Enterprise-Grade Security**:
  - Double submission restriction using Cookies and Client IP mapping.
  - Comprehensive CSRF protection across all CRUD endpoints.
  - Strict physical isolation of MySQL credentials.
  - **XSS Prevention**: Disabled SVG/XML uploads, and strict back-end validation preventing choice manipulation or spoofed question IDs.

### 🛠️ Server Environment Requirements

- **PHP** >= 8.0
- **MySQL** >= 5.7 or 8.0
- Required PHP Extensions: `PDO`, `pdo_mysql`, `gd`, `session`, `mbstring`
- Web Server: Apache, Nginx, IIS, or PHP Built-in Server
- Writable Directories: `public/uploads/` (automatically created at runtime)

### 🚀 Quick Start

#### Step 1: Clone the Repository
Clone the repository to your local web root:
```bash
git clone https://github.com/aqppy/EasySurvey.git
```

#### Step 2: Initialize Configuration
Copy the configuration template to form your private settings file:
```bash
cp app/config.example.php app/config.php
```
Open `app/config.php` in your editor and configure your **MySQL database credentials** and your secure **admin password hash** (instructions and hashing commands are provided directly in the file).

#### Step 3: Initialize Database Schema
Run the provided SQL script [database.sql](database.sql) against your MySQL database using your favorite client to create the 7 core tables.

#### Step 4: Deploy and Configure Web Directory
- **Document Root Setting**: For security, configure your web server to point its document root to the **`public/`** subdirectory. Do NOT expose the root directory containing private source code!
- **Local Dev Server**: Test locally by launching PHP's built-in server from the root directory:
  ```bash
  php -S 127.0.0.1:8089 -t public
  ```
- **Environment Diagnostics**: Navigate to `http://127.0.0.1:8089/check_env.php` for a comprehensive system health check.
- **Admin Control Panel**: Log into the admin portal at `http://127.0.0.1:8089/admin/login.php` (Default username: `admin`, password: as configured in `config.php`).

### ⚙️ Command Line & Diagnostic Utilities

- **One-Click Database Backup**: Run the database backup tool via the CLI to export structure and data tables to the `backups/` directory (implemented in pure PHP without dependency on `mysqldump` executable):
  ```bash
  php -f scripts/backup.php
  ```
- **Automated Regression Suite**: Run the database regression tests to verify transaction security, foreign key constraints, and dynamic question modifications:
  ```bash
  php -f tests/regression.php
  ```
  *Note: This test runs completely inside database transactions and triggers a painless rollback, meaning it leaves zero dirty testing data in your database.*
- **Glassmorphism Diagnostic Panel**: Visit `/check_env.php` in your browser at any time to verify PHP modules, upload folders, and database connectivity.

---

## 简体中文文档

EasySurvey 是一款基于 **PHP 8.x + MySQL** 开发的极简、轻量级、高颜值问卷调查系统。项目 **零外部复杂包依赖**（无需 Composer，无需 Node.js 编译环境），支持局域网离线独立部署，极其适合企业内部满意度调研、小规模数据收集以及各类敏捷建站场景。

### 🌟 核心特性

- 🎨 **高颜值动态品牌定制**：后台内置颜色选择器，动态渲染侧边栏、状态标与按钮主题色。前台问卷支持深度定义主题色、背景色、问卷头图、背景底纹以及个性化提交按钮。
- 🌐 **双语自主会话切换**：前后台完全支持 **英文** 与 **简体中文**。导航栏内置“中/EN”快捷开关，通过 Session 和 Cookie 瞬间自适应切换，不污染 URL 结构，并提供全局数据库级“系统默认语言”设置。
- 📝 **可视化拖拽编辑器**：支持单选题、多选题、文本问答题。题目支持拖拽/上下移稳定排序，题号智能自适应关联。
- 🛡️ **历史答案物理保留**：彻底解决传统问卷系统编辑导致历史数据丢失的通病。修改已有答卷的问卷时，仅对明确删除的问题进行安全同步，前端加入防误删强警示。
- 📥 **Excel 友好型 CSV 导入导出**：支持一键导出问卷结构为 CSV 格式，**内置 UTF-8 BOM 签名**，在 Windows Excel 中双击打开绝不乱码。支持本地 CSV/Excel 结构一键导入填充编辑器。
- 📈 **纯离线数据可视化与导出**：内置本地离线版 **Chart.js** 报表引擎。在与外网隔离的纯内网环境下依然能完美渲染彩色统计饼图、柱状图，支持一键导出回答明细为通用 CSV 表格。
- 📱 **1024x1024 印刷级高清二维码**：动态侦测部署环境（Localhost/局域网IP/VPS外网），零配置生成扫码卡片。借助后台离屏双 Canvas 贴图，一键下载印刷级 1024x1024 无损 PNG 矢量感二维码。
- 🔒 **企业级安全防护**：
  - 支持 Cookie 与客户端 IP 双重映射防恶意刷票。
  - 核心增删改查接口内置严格的 CSRF 安全校验。
  - 物理隔离数据库敏感账户密码。
  - **安全防注入**：服务器端物理禁用 SVG 上传以防止 XML-XSS 跨站攻击，对非法题目 ID 及篡改选项行为进行深度鉴权拦截。

### 🛠️ 运行环境要求

- **PHP** >= 8.0
- **MySQL** >= 5.7 或 8.0
- PHP 核心扩展：`PDO`, `pdo_mysql`, `gd`, `session`, `mbstring`
- Web 服务器：Apache, Nginx, IIS 或 PHP 内置 Server
- 物理可写目录：`public/uploads/` (子目录在运行时会自动创建)

### 🚀 快速开始

#### 第一步：克隆项目代码
克隆项目到本地 Web 根目录下：
```bash
git clone https://github.com/aqppy/EasySurvey.git
```

#### 第二步：初始化私有配置
系统采用配置隔离保护，请在项目根目录下，将模板复制为本地私有配置文件：
```bash
cp app/config.example.php app/config.php
```
使用编辑器打开 `app/config.php`，根据提示配置您的 **MySQL 数据库参数** 与 **管理员强密码哈希**（文件内有命令行生成示例）。

#### 第三步：导入数据库结构
使用 MySQL 客户端，在对应的数据库中运行项目根目录下的 [database.sql](database.sql) 初始化 7 张核心业务表。

#### 第四步：部署并开始填写
- **Web 根目录指向**：为了保护敏感逻辑，请确保将您 Web 服务器的根目录指向项目的 **`public/`** 子目录，绝不可暴露项目根目录！
- **本地开发测试**：您可以直接在项目根目录下通过 PHP 内置开发服务器快速启动测试：
  ```bash
  php -S 127.0.0.1:8089 -t public
  ```
- **环境诊断**：打开浏览器访问 `http://127.0.0.1:8089/check_env.php` 即可进行一键环境诊断。
- **登录后台**：诊断全部通过后，访问 `http://127.0.0.1:8089/admin/login.php` 登录进入后台（初始账号：`admin`，密码为您在 `config.php` 中配置的管理员密码）。

### ⚙️ 实用运维及管理工具

- **命令行数据库备份**：在终端执行以下命令，即可直接在 `backups/` 目录下生成包含完整结构与数据的 SQL 备份快照（基于 Pure PHP PDO，不依赖系统物理 `mysqldump` 命令）：
  ```bash
  php -f scripts/backup.php
  ```
- **自动化一致性回归测试**：为了保证您二次开发后系统的逻辑稳定性，您可以直接运行我们提供的自动化闭环测试包：
  ```bash
  php -f tests/regression.php
  ```
  *注：该测试会在数据库事务内完成 mock 数据的完整周期流转与断言检测，并在通过后无痛自动回滚事务，不会在您的生产环境中留下任何脏数据。*
- **一键环境自检页面**：部署完毕或系统发生异常时，可以直接在浏览器中访问系统自带的诊断大屏 `http://您的域名/check_env.php`，全面图形化诊断运行状态。

---

## 📄 License

This project is open-sourced software licensed under the [MIT License](LICENSE).
本项目采用 **MIT 开源许可证**，可免费用于个人学习、商业项目等场景。
