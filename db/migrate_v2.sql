-- migrate_v2.sql
-- 在 phpMyAdmin 選擇 hotel_db 後執行此檔案
-- 作用：更新 hotel1~8 圖片路徑為線上佔位圖，並新增 12 間飯店至共 20 間

USE hotel_db;

-- ── 更新既有 8 間飯店的 img_url ──
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel1/600/400'  WHERE hotel_id = 1;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel2/600/400'  WHERE hotel_id = 2;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel3/600/400'  WHERE hotel_id = 3;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel4/600/400'  WHERE hotel_id = 4;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel5/600/400'  WHERE hotel_id = 5;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel6/600/400'  WHERE hotel_id = 6;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel7/600/400'  WHERE hotel_id = 7;
UPDATE Hotels SET img_url = 'https://picsum.photos/seed/hotel8/600/400'  WHERE hotel_id = 8;

-- ── 新增 12 間飯店 ──
INSERT INTO Hotels (name, region, price_per_night, stars, description, facilities, img_url) VALUES
('台東知本老爺酒店',      '台東', 5600, 4, '依山傍水的溫泉度假酒店，坐擁知本溪谷景色，設有室內外溫泉池及多元水療設施。',              '["溫泉","SPA","游泳池","餐廳","健身房"]',      'https://picsum.photos/seed/hotel9/600/400'),
('阿里山賓館',            '嘉義', 4200, 3, '海拔2200公尺的高山旅宿，近百年歷史建築，可欣賞阿里山日出雲海與神木群。',                  '["高山","觀星","森林","早餐","停車場"]',       'https://picsum.photos/seed/hotel10/600/400'),
('澎湖福朋喜來登酒店',    '澎湖', 7500, 4, '馬公市中心海景酒店，步行即達天后宮，提供出海行程安排與豐富水上活動。',                    '["游泳池","沙灘","水上活動","餐廳","海景"]',   'https://picsum.photos/seed/hotel11/600/400'),
('台中豐邑酒店',          '台中', 5200, 4, '位於台中七期商圈精華地段，頂樓無邊際泳池俯瞰城市，鄰近勤美誠品與新光三越。',              '["游泳池","SPA","健身房","餐廳","商務中心"]',  'https://picsum.photos/seed/hotel12/600/400'),
('宜蘭傳藝老爺行旅',      '宜蘭', 4800, 4, '結合傳統藝術中心與現代飯店，融入台灣工藝美學，提供親子文化體驗與溫泉設施。',              '["親子","溫泉","文化體驗","餐廳","早餐"]',    'https://picsum.photos/seed/hotel13/600/400'),
('花蓮翰品酒店',          '花蓮', 3800, 4, '鄰近花蓮火車站的商務旅館，現代簡約設計，提供舒適住宿與便利的市區交通。',                  '["健身房","商務中心","餐廳","停車場","早餐"]', 'https://picsum.photos/seed/hotel14/600/400'),
('溪頭米堤大飯店',        '南投', 3200, 4, '溪頭自然教育園區內的森林渡假飯店，四周竹林環繞，提供自然生態導覽與健行體驗。',            '["森林","健行","餐廳","停車場","觀鳥"]',       'https://picsum.photos/seed/hotel15/600/400'),
('台南大員皇冠假日酒店',  '台南', 6800, 5, '緊鄰安平漁人碼頭的五星海景酒店，擁有無邊際泳池與多樣化餐飲選擇。',                        '["游泳池","SPA","健身房","餐廳","商務中心"]',  'https://picsum.photos/seed/hotel16/600/400'),
('桃園大溪威斯汀度假酒店','桃園', 8800, 5, '隱身桃園大溪山水之間，提供全方位度假體驗，設有高爾夫球場、SPA與多國料理餐廳。',          '["游泳池","SPA","高爾夫","健身房","餐廳"]',    'https://picsum.photos/seed/hotel17/600/400'),
('新竹老爺行旅',          '新竹', 4200, 4, '位於新竹科學園區旁，是商務人士首選，提供機場接送與高速網路，鄰近玻璃工藝博物館。',        '["商務中心","健身房","餐廳","停車場","早餐"]', 'https://picsum.photos/seed/hotel18/600/400'),
('金門金湖大飯店',        '金門', 2800, 3, '金門最具歷史感的老飯店，提供閩南建築風格客房，可安排戰地文化導覽與高粱酒廠參觀。',        '["文化導覽","停車場","餐廳","早餐"]',          'https://picsum.photos/seed/hotel19/600/400'),
('基隆長榮桂冠酒店',      '基隆', 4500, 4, '俯瞰基隆港夜景的精品酒店，搭乘接駁船可直達廟口夜市，提供海釣與郵輪旅遊套裝。',            '["海景","游泳池","健身房","商務中心","餐廳"]', 'https://picsum.photos/seed/hotel20/600/400');
