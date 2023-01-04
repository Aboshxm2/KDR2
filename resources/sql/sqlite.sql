-- #!sqlite
-- #{ KDR2.init
CREATE TABLE IF NOT EXISTS KDR (playerName TEXT PRIMARY KEY, kills INTEGER DEFAULT 0, deaths INTEGER DEFAULT 0, killstreak INTEGER DEFAULT 0)
-- #}
-- #{ KDR2.selectPlayer
-- # :playerName string
SELECT * FROM KDR WHERE playerName=:playerName
-- #}
-- #{ KDR2.insertOrUpdate
-- # :playerName string
-- # :kills int
-- # :deaths int
-- # :killstreak int
INSERT OR REPLACE INTO KDR (playerName, kills, deaths, killstreak)
VALUES (:playerName, :kills, :deaths, :killstreak)
-- #}
