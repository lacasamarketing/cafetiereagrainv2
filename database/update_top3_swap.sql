-- =================================================================
-- update_top3_swap.sql
-- Swap des positions du top 3 sur la home :
--   YISSVIC UV LED 4200V (4,4/5, 157 avis) passe en #3
--   HEXA Favex Hexasafe (pas encore d'avis) descend en #4
-- =================================================================

SET NAMES utf8mb4;

-- YISSVIC en position 3 du top
UPDATE products SET rank_global = 3 WHERE slug = 'yissvic-uv-led-rechargeable';

-- HEXA Favex en position 4
UPDATE products SET rank_global = 4 WHERE slug = 'hexa-favex-hexasafe';

-- Mosquito Magnet Pioneer reste #1, Biogents BG-Mosquitaire reste #2
-- BG-GAT reste #5, Lampe LED USB reste #6
