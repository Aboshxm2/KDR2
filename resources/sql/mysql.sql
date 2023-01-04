-- #!mysql
-- #{ KDR2.init
CREATE TABLE IF NOT EXISTS KDR (playerName VARCHAR(64) PRIMARY KEY, kills INT(10) DEFAULT 0, deaths INT(10) DEFAULT 0, killstreak INT(10) DEFAULT 0)
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
REPLACE INTO KDR (playerName, kills, deaths, killstreak)
VALUES (:playerName, :kills, :deaths, :killstreak)
-- #}
