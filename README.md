# semantic-hotel-finder

以向量檢索為核心的語意飯店搜尋引擎。輸入自然語言描述（例如「適合蜜月的海景飯店」），系統會將查詢轉換為 embedding 向量，與資料庫中的飯店向量進行相似度比對並排序，回傳最相關的結果——而不是只靠關鍵字比對。

<!-- TODO: 放一張首頁截圖，觀感差很多 -->
<!-- ![screenshot](docs/screenshot-home.png) -->

## Features

- **語意搜尋**：自然語言查詢 → embedding → 向量相似度排序，理解「意思」而非只比對字面
- **關鍵字篩選**：地區、價格等傳統條件過濾，可與語意搜尋並用
- **使用者系統**：註冊登入、收藏飯店、撰寫評論
- **管理後台**：飯店資料管理、一鍵重建全站 embeddings

## How It Works

```
使用者查詢（自然語言）
        │
        ▼
  Embedding API（Gemini text-embedding）
        │
        ▼
  查詢向量 ←─ 相似度計算（cosine similarity）─→ 飯店向量（預先計算，存於 MySQL）
        │
        ▼
  依相似度排序 + 條件過濾 → 回傳結果
```

每間飯店的描述會預先透過 embedding model 轉為向量存入資料庫；查詢時只需為使用者輸入產生一次向量，再與全部飯店向量做相似度比對。詳細的資料庫 schema 與系統設計請見 [docs/architecture.pdf](docs/architecture.pdf)。

## Tech Stack

| 層級 | 技術 |
| --- | --- |
| 前端 | HTML5、Bootstrap 5、jQuery |
| 後端 | PHP 7.4+ |
| 資料庫 | MySQL 5.7+ |
| Embedding | Google Gemini API（text embedding） |

## Quick Start

```bash
git clone https://github.com/Karlaaaaa1212/semantic-hotel-finder.git
```

1. 將專案放入 web server 目錄（如 XAMPP 的 `htdocs/`）
2. 建立 MySQL 資料庫並匯入 `db/schema.sql` 與 `db/add_hotels_v3.sql`
3. 在專案根目錄建立 `.env`，填入 `GEMINI_API_KEY=你的Key`
4. 以管理員身分進入後台產生 embeddings

完整的逐步安裝教學（含 XAMPP 設定、phpMyAdmin 操作、常見問題）請見 **[docs/SETUP.md](docs/SETUP.md)**。

## License

MIT
