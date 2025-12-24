-- db/seed.sql（あなたの現状に合わせた版）
START TRANSACTION;

-- categories（すでに id=2,3,4 を使っている前提）
INSERT INTO categories (id, name) VALUES
  (2, '丼もの'),
  (3, '飲み物'),
  (4, 'トッピング')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- menus（すでに id=2..7 を使っている前提）
INSERT INTO menus (id, name, price, cook, make, category_id) VALUES
  (2, 'マグロ丼', 780, 1, 'A', 2),
  (3, 'サーモン丼', 700, 1, 'A', 2),
  (4, '温泉卵', 50, 0, NULL, 4),
  (5, 'チーズ', 100, 0, NULL, 4),
  (6, 'コーラ', 200, 1, 'B', 3),
  (7, 'オレンジジュース', 200, 1, 'B', 3)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  price = VALUES(price),
  cook = VALUES(cook),
  make = VALUES(make),
  category_id = VALUES(category_id);

COMMIT;
