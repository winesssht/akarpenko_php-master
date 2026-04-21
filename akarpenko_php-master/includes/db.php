<?php
// SQLite database connection
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new PDO('sqlite:' . __DIR__ . '/../school.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        initDB($db);
    }
    return $db;
}

function initDB($db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('teacher', 'student')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS homework (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            teacher_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            subject TEXT NOT NULL,
            due_date DATE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(teacher_id) REFERENCES users(id)
        );

        CREATE TABLE IF NOT EXISTS submissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            homework_id INTEGER NOT NULL,
            student_id INTEGER NOT NULL,
            answer TEXT NOT NULL,
            grade INTEGER DEFAULT NULL,
            teacher_comment TEXT DEFAULT NULL,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(homework_id) REFERENCES homework(id),
            FOREIGN KEY(student_id) REFERENCES users(id)
        );
    ");

    // Seed default users if not exist
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("
            INSERT INTO users (username, password, full_name, role) VALUES
            ('teacher', '" . password_hash('teacher123', PASSWORD_DEFAULT) . "', 'Анна Петровна Смирнова', 'teacher'),
            ('student1', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'Иван Александров', 'student'),
            ('student2', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'Мария Козлова', 'student')
        ");
    }
}
