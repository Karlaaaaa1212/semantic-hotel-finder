-- 飯店推薦系統 Schema
-- 資料庫管理期末專題 第六組

CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_db;

CREATE TABLE IF NOT EXISTS Users (
    user_id       INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','admin') DEFAULT 'user',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Hotels (
    hotel_id        INT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    region          VARCHAR(50)  NOT NULL,
    price_per_night INT          NOT NULL,
    stars           TINYINT      NOT NULL CHECK (stars BETWEEN 1 AND 5),
    description     TEXT,
    facilities      TEXT,           -- JSON array: ["溫泉","親子","游泳池"]
    img_url         VARCHAR(255) DEFAULT 'uploads/default.jpg',
    embedding       LONGTEXT,       -- Gemini text-embedding-004 vector (768-dim JSON)
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Reviews (
    review_id  INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    hotel_id   INT NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    content    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES Users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES Hotels(hotel_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Favorites (
    fav_id     INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT NOT NULL,
    hotel_id   INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_hotel (user_id, hotel_id),
    FOREIGN KEY (user_id)  REFERENCES Users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES Hotels(hotel_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS SearchLogs (
    log_id     INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT DEFAULT NULL,
    query_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE SET NULL
);

-- 範例飯店資料（無向量，需執行 embed_hotels.php 建立）
INSERT INTO Hotels (name, region, price_per_night, stars, description, facilities, img_url) VALUES
('礁溪老爺酒店',          '宜蘭', 8800,  5, '位於礁溪溫泉區核心，設有兒童專屬溫泉池與親子遊樂設施，室內恆溫設計讓全家大小都能舒適泡湯。',       '["溫泉","親子","SPA","餐廳","健身房"]',        'https://picsum.photos/seed/hotel1/600/400'),
('台北君悅大飯店',        '台北', 12000, 5, '坐落台北市中心，俯瞰城市天際線，提供商務與休閒一體的頂級服務，交通便利近捷運。',              '["游泳池","健身房","商務中心","餐廳","酒吧"]', 'https://picsum.photos/seed/hotel2/600/400'),
('花蓮理想大地',          '花蓮', 5500,  4, '佔地廣闊的莊園式度假村，擁有多座戶外溫泉池與農場體驗，適合親子與深度旅遊。',              '["溫泉","親子","農場","餐廳","腳踏車"]',       'https://picsum.photos/seed/hotel3/600/400'),
('日月潭涵碧樓',          '南投', 18000, 5, '三面環山一面向湖的頂級度假酒店，無邊際泳池直面日月潭，是台灣最頂尖的湖景住宿體驗。',     '["游泳池","SPA","餐廳","划船","健身房"]',      'https://picsum.photos/seed/hotel4/600/400'),
('台南晶英酒店',          '台南', 4200,  4, '位於台南古都核心商圈，鄰近安平古堡與赤崁樓，提供在地美食早餐與文化導覽服務。',            '["餐廳","停車場","旅遊服務","商務中心"]',      'https://picsum.photos/seed/hotel5/600/400'),
('高雄漢來大飯店',        '高雄', 6800,  5, '高雄首屈一指的五星飯店，匯集多間米其林餐廳，俯瞰愛河夜景，距離捷運站步行五分鐘。',       '["游泳池","餐廳","健身房","SPA","商務中心"]',  'https://picsum.photos/seed/hotel6/600/400'),
('清境農場觀星館',        '南投', 3600,  3, '海拔1748公尺的高山民宿，夜晚可觀星，白天俯瞰層疊雲海，周邊設有青青草原與綿羊秀。',        '["觀星","停車場","早餐","高山"]',              'https://picsum.photos/seed/hotel7/600/400'),
('墾丁凱撒大飯店',        '屏東', 7200,  4, '直接面海的海灘度假飯店，擁有私人沙灘與多種水上活動，是南台灣最受歡迎的海島渡假選擇。', '["沙灘","水上活動","游泳池","餐廳","衝浪"]',  'https://picsum.photos/seed/hotel8/600/400'),
('台東知本老爺酒店',      '台東', 5600,  4, '依山傍水的溫泉度假酒店，坐擁知本溪谷景色，設有室內外溫泉池及多元水療設施。',              '["溫泉","SPA","游泳池","餐廳","健身房"]',      'https://picsum.photos/seed/hotel9/600/400'),
('阿里山賓館',            '嘉義', 4200,  3, '海拔2200公尺的高山旅宿，近百年歷史建築，可欣賞阿里山日出雲海與神木群。',                  '["高山","觀星","森林","早餐","停車場"]',       'https://picsum.photos/seed/hotel10/600/400'),
('澎湖福朋喜來登酒店',    '澎湖', 7500,  4, '馬公市中心海景酒店，步行即達天后宮，提供出海行程安排與豐富水上活動。',                    '["游泳池","沙灘","水上活動","餐廳","海景"]',   'https://picsum.photos/seed/hotel11/600/400'),
('台中豐邑酒店',          '台中', 5200,  4, '位於台中七期商圈精華地段，頂樓無邊際泳池俯瞰城市，鄰近勤美誠品與新光三越。',              '["游泳池","SPA","健身房","餐廳","商務中心"]',  'https://picsum.photos/seed/hotel12/600/400'),
('宜蘭傳藝老爺行旅',      '宜蘭', 4800,  4, '結合傳統藝術中心與現代飯店，融入台灣工藝美學，提供親子文化體驗與溫泉設施。',              '["親子","溫泉","文化體驗","餐廳","早餐"]',    'https://picsum.photos/seed/hotel13/600/400'),
('花蓮翰品酒店',          '花蓮', 3800,  4, '鄰近花蓮火車站的商務旅館，現代簡約設計，提供舒適住宿與便利的市區交通。',                  '["健身房","商務中心","餐廳","停車場","早餐"]', 'https://picsum.photos/seed/hotel14/600/400'),
('溪頭米堤大飯店',        '南投', 3200,  4, '溪頭自然教育園區內的森林渡假飯店，四周竹林環繞，提供自然生態導覽與健行體驗。',            '["森林","健行","餐廳","停車場","觀鳥"]',       'https://picsum.photos/seed/hotel15/600/400'),
('台南大員皇冠假日酒店',  '台南', 6800,  5, '緊鄰安平漁人碼頭的五星海景酒店，擁有無邊際泳池與多樣化餐飲選擇。',                        '["游泳池","SPA","健身房","餐廳","商務中心"]',  'https://picsum.photos/seed/hotel16/600/400'),
('桃園大溪威斯汀度假酒店','桃園', 8800,  5, '隱身桃園大溪山水之間，提供全方位度假體驗，設有高爾夫球場、SPA與多國料理餐廳。',          '["游泳池","SPA","高爾夫","健身房","餐廳"]',    'https://picsum.photos/seed/hotel17/600/400'),
('新竹老爺行旅',          '新竹', 4200,  4, '位於新竹科學園區旁，是商務人士首選，提供機場接送與高速網路，鄰近玻璃工藝博物館。',        '["商務中心","健身房","餐廳","停車場","早餐"]', 'https://picsum.photos/seed/hotel18/600/400'),
('金門金湖大飯店',        '金門', 2800,  3, '金門最具歷史感的老飯店，提供閩南建築風格客房，可安排戰地文化導覽與高粱酒廠參觀。',        '["文化導覽","停車場","餐廳","早餐"]',          'https://picsum.photos/seed/hotel19/600/400'),
('基隆長榮桂冠酒店',      '基隆', 4500,  4, '俯瞰基隆港夜景的精品酒店，搭乘接駁船可直達廟口夜市，提供海釣與郵輪旅遊套裝。',            '["海景","游泳池","健身房","商務中心","餐廳"]', 'https://picsum.photos/seed/hotel20/600/400');
