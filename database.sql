-- ============================================================
-- ChatApp - Base de Datos
-- ============================================================
CREATE DATABASE IF NOT EXISTS chatapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chatapp;

CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50) UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','user') DEFAULT 'user',
    color       VARCHAR(7) DEFAULT '#6366f1',
    is_online   TINYINT(1) DEFAULT 0,
    last_seen   DATETIME DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS chats (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    type        ENUM('direct','group') DEFAULT 'direct',
    created_by  INT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS chat_members (
    chat_id     INT NOT NULL,
    user_id     INT NOT NULL,
    joined_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (chat_id, user_id),
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    chat_id     INT NOT NULL,
    user_id     INT NOT NULL,
    content     TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS sessions (
    id              VARCHAR(128) PRIMARY KEY,
    user_id         INT NOT NULL,
    last_activity   DATETIME NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ADMIN por defecto (password: admin123)
INSERT IGNORE INTO users (username, password, role, color)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '#6366f1');
-- Nota: el hash corresponde a "admin123" con password_hash de PHP

CREATE INDEX idx_messages_chat ON messages(chat_id, created_at);
CREATE INDEX idx_sessions_activity ON sessions(last_activity);
CREATE INDEX idx_members_user ON chat_members(user_id);
