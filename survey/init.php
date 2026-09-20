<?php
// 数据库初始化
$dbFile = __DIR__ . '/data/survey.db';

// 创建 data 目录
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 创建问卷表
$db->exec("CREATE TABLE IF NOT EXISTS surveys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TEXT DEFAULT 'open'
)");

// 创建问题表
$db->exec("CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    type TEXT NOT NULL DEFAULT 'text',
    title TEXT NOT NULL,
    options TEXT,
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
)");

// 创建答案表
$db->exec("CREATE TABLE IF NOT EXISTS responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    survey_id INTEGER NOT NULL,
    respondent_name TEXT,
    respondent_contact TEXT,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id)
)");

// 创建答案详情表
$db->exec("CREATE TABLE IF NOT EXISTS answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    response_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    answer TEXT,
    FOREIGN KEY (response_id) REFERENCES responses(id),
    FOREIGN KEY (question_id) REFERENCES questions(id)
)");

// 创建管理员表
$db->exec("CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL
)");

// 默认管理员账号（密码: admin123）
$stmt = $db->prepare("INSERT OR IGNORE INTO admins (username, password_hash) VALUES (?, ?)");
$stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);

echo "数据库初始化完成！\n";
echo "默认管理员账号: admin / admin123\n";
?>
