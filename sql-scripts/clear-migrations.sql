SELECT CONCAT('DROP TABLE `', t.table_name, '`;') AS drop_stmt
FROM information_schema.tables t
WHERE t.table_schema = DATABASE()
  AND t.table_name LIKE 'tt_%'
  AND t.table_name != 'tt_migrations'
ORDER BY CHAR_LENGTH(t.table_name) DESC, t.table_name ASC;