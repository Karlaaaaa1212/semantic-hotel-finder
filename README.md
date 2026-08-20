# 飯店推薦系統 — 安裝與使用指南

> 資料庫管理期末專案 第六組  
> AI 飯店語意搜尋平台（台灣地區）

---

## 專案簡介

本系統是一個以 **Google Gemini AI** 驅動的飯店推薦網站，支援自然語言語意搜尋（例如輸入「蜜月海景飯店」即可找到最相關的飯店）、關鍵字篩選、使用者收藏與評論功能。

| 層級 | 技術 |
|------|------|
| 前端 | HTML5、Bootstrap 5、jQuery、Font Awesome |
| 後端 | PHP 7.4+ |
| 資料庫 | MySQL 5.7+（hotel_db） |
| AI 服務 | Google Gemini API（語意搜尋、推薦摘要） |
| 開發環境 | XAMPP |

---

## 前置需求

- 電腦：Windows 10/11 或 macOS 12+
- XAMPP（內含 Apache + MySQL + PHP）
- Google 帳號（用於申請 Gemini API Key，免費）

---

## Step 1：安裝 XAMPP

### 下載

前往官網下載對應作業系統的版本：

**https://www.apachefriends.org/download.html**

- Windows：選 `xampp-windows-x64-*-installer.exe`
- macOS：選 `xampp-osx-*-installer.dmg`

### 安裝（Windows）

1. 執行下載的 `.exe` 安裝程式
2. 安裝路徑保持預設：`C:\xampp`
3. 勾選元件時確認 **Apache** 和 **MySQL** 有勾選
4. 完成安裝後開啟 **XAMPP Control Panel**

### 安裝（macOS）

1. 開啟 `.dmg` 並拖曳 XAMPP 到 Applications
2. 開啟 **XAMPP** 應用程式（在 Applications 資料夾內）
3. 進入 **Manage Servers** 分頁

---

## Step 2：啟動伺服器

本專案需要同時啟動 **Apache**（網頁伺服器）和 **MySQL**（資料庫）。

### Windows — XAMPP Control Panel

1. 開啟 XAMPP Control Panel
2. 點擊 **Apache** 列的 `Start` 按鈕 → 變成綠色表示成功
3. 點擊 **MySQL** 列的 `Start` 按鈕 → 變成綠色表示成功

```
[Apache]   [Start]  ✅ 成功後顯示 Port 80, 443
[MySQL]    [Start]  ✅ 成功後顯示 Port 3306
```

### macOS — XAMPP Manager

1. 開啟 XAMPP → 點擊 **Manage Servers**
2. 選取 **Apache Web Server** → 點擊 `Start`
3. 選取 **MySQL Database** → 點擊 `Start`

> ⚠️ **Port 衝突**：若 Apache 無法啟動，可能是 Port 80 被其他程式（例如 Skype、IIS）佔用。請關閉該程式後再試，或在 XAMPP 設定中改用 Port 8080。

---

## Step 3：下載並放置專案檔案

### 從 GitHub 下載

1. 前往本專案的 GitHub Repository
2. 點擊綠色 `Code` 按鈕 → `Download ZIP`
3. 解壓縮下載的 ZIP 檔案，得到資料夾 `final-main`（或類似名稱）

### 放置到正確位置

將資料夾**重新命名為 `final`**，然後移動到下列路徑：

| 作業系統 | 目標路徑 |
|----------|---------|
| Windows | `C:\xampp\htdocs\final\` |
| macOS | `/Applications/XAMPP/xamppfiles/htdocs/final/` |

放置完成後，資料夾結構應如下：

```
htdocs/
└── final/
    ├── index.php        ← 首頁
    ├── hotel.php
    ├── login.php
    ├── register.php
    ├── profile.php
    ├── api/
    ├── admin/
    ├── db/
    │   ├── config.php
    │   ├── schema.sql   ← 資料庫結構
    │   └── add_hotels_v3.sql  ← 範例飯店資料
    ├── css/
    ├── js/
    └── .env             ← 需自行建立（見 Step 5）
```

> ⚠️ 資料夾名稱必須是 `final`，否則網址路徑會不正確。

---

## Step 4：建立資料庫

### 4-1 開啟 phpMyAdmin

在瀏覽器輸入：

```
http://localhost/phpmyadmin
```

登入資訊（預設）：
- 使用者名稱：`root`
- 密碼：（空白，不用輸入）

### 4-2 匯入資料庫結構

1. 在左側欄點擊 **「新增」（New）** 建立新資料庫
   - 資料庫名稱：`hotel_db`
   - 編碼：`utf8mb4_unicode_ci`
   - 點擊 **建立**

   > ⚠️ 若已存在 `hotel_db`，請直接點擊它，不需重新建立。

2. 點擊上方 **「匯入」（Import）** 分頁

3. 點擊 **「選擇檔案」** → 選取專案內的：
   ```
   final/db/schema.sql
   ```

4. 點擊 **「執行」（Go）** → 應看到成功訊息，左側出現 5 張資料表

### 4-3 匯入範例飯店資料（選擇性但建議）

1. 確認左側仍選取 `hotel_db`
2. 再次點擊 **「匯入」（Import）**
3. 選取：
   ```
   final/db/add_hotels_v3.sql
   ```
4. 點擊 **「執行」** → 匯入約 20 筆範例飯店資料

---

## Step 5：設定 Gemini API Key

AI 語意搜尋功能需要 Google Gemini API Key。

### 5-1 取得 API Key

1. 前往 Google AI Studio：**https://aistudio.google.com/apikey**
2. 使用 Google 帳號登入
3. 點擊 **「Create API Key」**
4. 複製產生的 Key（格式類似 `AIzaSy...`）

### 5-2 建立 .env 檔案

在專案根目錄（`final/` 資料夾內，與 `index.php` 同層）建立一個名為 `.env` 的純文字檔案：

**Windows** — 在 `C:\xampp\htdocs\final\` 資料夾內，右鍵 → 新增文字文件，命名為 `.env`（注意：副檔名是 `.env` 而不是 `.env.txt`）

**macOS** — 開啟終端機，執行：
```bash
touch /Applications/XAMPP/xamppfiles/htdocs/final/.env
```

### 5-3 填入 API Key

用文字編輯器開啟 `.env`，貼上以下內容（替換成你的 Key）：

```
GEMINI_API_KEY=你的APIKey貼在這裡
```

範例：
```
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

> ⚠️ `.env` 檔案不可上傳到 GitHub，請確認 `.gitignore` 已包含此檔案。

---

## Step 6：開啟網站

確認 Apache 和 MySQL 都在執行中，在瀏覽器輸入：

```
http://localhost/final/
```

你應該看到 LUMINA 設計風格的飯店首頁，上方有搜尋列和飯店卡片列表。

### 其他頁面路徑

| 頁面 | 網址 |
|------|------|
| 首頁 | `http://localhost/final/` |
| 登入 | `http://localhost/final/login.php` |
| 註冊 | `http://localhost/final/register.php` |
| 個人頁面 | `http://localhost/final/profile.php` |
| 管理後台 | `http://localhost/final/admin/dashboard.php` |

---

## Step 7：產生 AI 語意搜尋向量（Embeddings）

AI 語意搜尋需要先對每間飯店產生向量資料（Embeddings），**只需執行一次**。

### 7-1 建立管理員帳號

1. 前往 `http://localhost/final/register.php` 註冊一個帳號
2. 開啟 phpMyAdmin → `hotel_db` → `Users` 資料表
3. 找到剛註冊的帳號，點擊 **編輯（Edit）**
4. 將 `role` 欄位從 `user` 改為 `admin`
5. 儲存

### 7-2 執行 Embedding

1. 以管理員帳號登入網站
2. 前往：`http://localhost/final/admin/dashboard.php`
3. 點擊 **「重新產生 Embeddings」** 按鈕
4. 等待頁面完成（約需 1-3 分鐘，依飯店數量而定）

完成後，AI 語意搜尋功能即可正常使用。

---

## 常見問題 FAQ

### Q：網頁顯示空白或 500 錯誤

- 確認 Apache 和 MySQL 都已啟動（XAMPP Control Panel 顯示綠色）
- 確認專案資料夾名稱是 `final`，放在 `htdocs/` 下
- 開啟 `db/config.php` 確認資料庫設定：
  ```php
  $host = 'localhost';
  $dbname = 'hotel_db';
  $user = 'root';
  $pass = '';
  ```

### Q：phpMyAdmin 無法登入

- 使用者名稱填 `root`，密碼留空
- 如果設定過 MySQL 密碼，則填入你設定的密碼，並同步更新 `db/config.php`

### Q：搜尋沒有結果 / AI 搜尋沒有反應

- 確認已完成 Step 4-3（匯入飯店資料）
- 確認已完成 Step 7（產生 Embeddings）
- 開啟瀏覽器 Developer Tools → Console，查看是否有 API 錯誤訊息

### Q：AI 搜尋出現錯誤訊息

- 確認 `.env` 檔案存在於 `final/` 根目錄
- 確認 API Key 格式正確（開頭為 `AIzaSy`）
- 確認 Google AI Studio 上的 Key 未被停用或超額

### Q：macOS 上看不到 .env 檔案

- 在 Finder 按 `Cmd + Shift + .` 可顯示隱藏檔案（以點開頭的檔案）

### Q：Windows 無法命名 .env（沒有副檔名）

- 開啟記事本 → 儲存時在「存檔類型」選「所有檔案」，檔名輸入 `.env`

---

## 技術支援

如有問題請聯繫組員，或在 GitHub Issues 回報。
