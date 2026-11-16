CREATE DATABASE IF NOT EXISTS hita_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON hita_test.* TO 'hita'@'%';
FLUSH PRIVILEGES;
-- ADR-0037: the restore drill tests restore backups into this second database.
CREATE DATABASE IF NOT EXISTS hita_drill CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
GRANT ALL PRIVILEGES ON hita_drill.* TO 'hita'@'%';
FLUSH PRIVILEGES;
