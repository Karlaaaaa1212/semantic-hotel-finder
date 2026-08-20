# 安裝與設定指南

本文件說明如何在本機以 XAMPP 環境架設 semantic-hotel-finder。

## 前置需求

- Windows 10/11 或 macOS 12+
- XAMPP（內含 Apache + MySQL + PHP）
- Google 帳號（用於申請 Gemini API Key，免費）

## Step 1：安裝 XAMPP

前往官網下載對應作業系統的版本：<https://www.apachefriends.org/download.html>

- Windows：選 `xampp-windows-x64-*-installer.exe`，安裝路徑保持預設 `C:\xampp`，元件確認勾選 **Apache** 與 **MySQL**
- macOS：開啟 `.dmg` 並拖曳 XAMPP 到 Applications

## Step 2：啟動伺服器

需同時啟動 **Apache**（網頁伺服器）與 **MySQL**（資料庫）。

- Windows：開啟 XAMPP Control Panel，分別點擊 Apache 與 MySQL 的 `Start`，變綠色表示成功（Apache 佔用 Port 80/443，MySQL 佔用 Port 3306）
- macOS：開啟 XAMPP → Manage Servers，分別啟動 Apache Web Server 與 MySQL Database

>  **Port 衝突**：若 Apache 無法啟動，可能是 Port 80 被其他程式（如 Skype、IIS）佔用。請關閉該程式，或在 XAMPP 設定中改用 Port 8080。

## Step 3：放置專案檔案

```bash
git clone https://github.com/Karlaaaaa1212/semantic-hotel-finder.git
```

將專案資料夾移動（或直接 clone）到：

| 作業系統 | 目標路徑 |
| --- | --- |
| Windows | `C:\xampp\htdocs\semantic-hotel-finder\` |
| macOS | `/Applications/XAMPP/xamppfiles/htdocs/semantic-hotel-finder/` |

> 資料夾名稱會對應網址路徑，例如放在 `htdocs/semantic-hotel-finder/` 則網址為 `http://localhost/semantic-hotel-finder/`。

## Step 4：建立資料庫

1. 瀏覽器開啟 `http://localhost/phpmyadmin`（預設帳號 `root`，密碼空白）
2. 左側點「新增（New）」建立資料庫：名稱 `hotel_db`，編碼 `utf8mb4_unicode_ci`
3. 點上方「匯入（Import）」，選取 `db/schema.sql` 並執行 → 左側應出現 5 張資料表
4. 再次匯入 `db/add_hotels_v3.sql` → 匯入約 20 筆範例飯店資料

## Step 5：設定 Gemini API Key

1. 前往 Google AI Studio（<https://aistudio.google.com/apikey>）建立 API Key
2. 在專案根目錄（與 `index.php` 同層）建立 `.env` 檔案，內容：

```
GEMINI_API_KEY=你的APIKey
```

>  `.env` 不可上傳到 GitHub，請確認 `.gitignore` 已包含此檔案。
>
> - Windows 記事本存檔時「存檔類型」選「所有檔案」，檔名輸入 `.env`
> - macOS 在 Finder 按 `Cmd + Shift + .` 可顯示隱藏檔案

## Step 6：開啟網站

確認 Apache 與 MySQL 執行中，瀏覽器開啟：

```
http://localhost/semantic-hotel-finder/
```

| 頁面 | 網址 |
| --- | --- |
| 首頁 | `/index.php` |
| 登入 / 註冊 | `/login.php`、`/register.php` |
| 個人頁面 | `/profile.php` |
| 管理後台 | `/admin/dashboard.php` |

## Step 7：產生語意搜尋向量（Embeddings）

語意搜尋需先為每間飯店產生 embedding，**只需執行一次**：

1. 於 `/register.php` 註冊帳號
2. 在 phpMyAdmin 的 `Users` 資料表中，將該帳號 `role` 欄位改為 `admin`
3. 以管理員身分登入，進入 `/admin/dashboard.php`
4. 點擊「重新產生 Embeddings」，等待完成（約 1–3 分鐘）

## 常見問題

**網頁空白或 500 錯誤**
- 確認 Apache 與 MySQL 已啟動
- 檢查 `db/config.php` 的資料庫設定（host: `localhost`、dbname: `hotel_db`、user: `root`、pass 空白）

**phpMyAdmin 無法登入**
- 帳號 `root`、密碼留空；若曾設定過 MySQL 密碼，需同步更新 `db/config.php`

**搜尋沒有結果 / AI 搜尋沒反應**
- 確認已匯入飯店資料（Step 4）並產生 Embeddings（Step 7）
- 開啟瀏覽器 DevTools → Console 檢查 API 錯誤訊息

**AI 搜尋出現錯誤**
- 確認 `.env` 存在於專案根目錄、API Key 格式正確（`AIzaSy` 開頭）、Key 未被停用或超額
