<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$dbFile = __DIR__ . '/data/survey.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = $_GET['action'] ?? '';

switch ($action) {
    // 管理员登录
    case 'login':
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => '用户名或密码错误']);
        }
        break;

    // 管理员退出
    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    // 检查登录状态
    case 'check_auth':
        echo json_encode(['logged_in' => isset($_SESSION['admin_id'])]);
        break;

    // 获取问卷列表
    case 'list_surveys':
        $stmt = $db->query("SELECT * FROM surveys ORDER BY created_at DESC");
        $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($surveys);
        break;

    // 获取单个问卷详情（含问题）
    case 'get_survey':
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$id]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($survey) {
            $stmt = $db->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order");
            $stmt->execute([$id]);
            $survey['questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode($survey);
        break;

    // 创建问卷
    case 'create_survey':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $questions = $data['questions'] ?? [];

        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO surveys (title, description) VALUES (?, ?)");
        $stmt->execute([$title, $description]);
        $surveyId = $db->lastInsertId();

        $qStmt = $db->prepare("INSERT INTO questions (survey_id, type, title, options, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($questions as $i => $q) {
            $qStmt->execute([
                $surveyId,
                $q['type'],
                $q['title'],
                $q['options'] ?? '',
                $i
            ]);
        }
        $db->commit();
        echo json_encode(['success' => true, 'id' => $surveyId]);
        break;

    // 提交问卷答案
    case 'submit_response':
        $data = json_decode(file_get_contents('php://input'), true);
        $surveyId = $data['survey_id'] ?? 0;
        $name = $data['name'] ?? '';
        $contact = $data['contact'] ?? '';
        $answers = $data['answers'] ?? [];

        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO responses (survey_id, respondent_name, respondent_contact) VALUES (?, ?, ?)");
        $stmt->execute([$surveyId, $name, $contact]);
        $responseId = $db->lastInsertId();

        $aStmt = $db->prepare("INSERT INTO answers (response_id, question_id, answer) VALUES (?, ?, ?)");
        foreach ($answers as $qid => $ans) {
            $aStmt->execute([$responseId, $qid, is_array($ans) ? implode(', ', $ans) : $ans]);
        }
        $db->commit();
        echo json_encode(['success' => true]);
        break;

    // 获取问卷结果（管理员）
    case 'get_results':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $surveyId = $_GET['id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM responses WHERE survey_id = ? ORDER BY submitted_at DESC");
        $stmt->execute([$surveyId]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($responses as $r) {
            $stmt = $db->prepare("SELECT question_id, answer FROM answers WHERE response_id = ?");
            $stmt->execute([$r['id']]);
            $r['answers'] = [];
            while ($a = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $r['answers'][$a['question_id']] = $a['answer'];
            }
            $result[] = $r;
        }
        echo json_encode($result);
        break;

    // 导出 CSV（管理员）
    case 'export_csv':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $surveyId = $_GET['id'] ?? 0;

        // 获取问卷和问题
        $stmt = $db->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->execute([$surveyId]);
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order");
        $stmt->execute([$surveyId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 获取答案
        $stmt = $db->prepare("SELECT * FROM responses WHERE survey_id = ? ORDER BY submitted_at");
        $stmt->execute([$surveyId]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="survey_' . $surveyId . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM for Excel

        $out = fopen('php://output', 'w');
        $header = ['提交时间', '姓名', '联系方式'];
        foreach ($questions as $q) {
            $header[] = $q['title'];
        }
        fputcsv($out, $header);

        foreach ($responses as $r) {
            $row = [$r['submitted_at'], $r['respondent_name'], $r['respondent_contact']];
            $stmt = $db->prepare("SELECT question_id, answer FROM answers WHERE response_id = ?");
            $stmt->execute([$r['id']]);
            $answerMap = [];
            while ($a = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $answerMap[$a['question_id']] = $a['answer'];
            }
            foreach ($questions as $q) {
                $row[] = $answerMap[$q['id']] ?? '';
            }
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
        break;

    // 编辑问卷（管理员）
    case 'update_survey':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $questions = $data['questions'] ?? [];

        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE surveys SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$title, $description, $id]);

        // 删除旧问题
        $stmt = $db->prepare("DELETE FROM questions WHERE survey_id = ?");
        $stmt->execute([$id]);

        // 插入新问题
        $qStmt = $db->prepare("INSERT INTO questions (survey_id, type, title, options, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($questions as $i => $q) {
            $qStmt->execute([$id, $q['type'], $q['title'], $q['options'] ?? '', $i]);
        }
        $db->commit();
        echo json_encode(['success' => true]);
        break;

    // 删除问卷（管理员）
    case 'delete_survey':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $id = $_GET['id'] ?? 0;
        $db->beginTransaction();
        // 删除答案
        $stmt = $db->prepare("DELETE FROM answers WHERE response_id IN (SELECT id FROM responses WHERE survey_id = ?)");
        $stmt->execute([$id]);
        // 删除回复
        $stmt = $db->prepare("DELETE FROM responses WHERE survey_id = ?");
        $stmt->execute([$id]);
        // 删除问题
        $stmt = $db->prepare("DELETE FROM questions WHERE survey_id = ?");
        $stmt->execute([$id]);
        // 删除问卷
        $stmt = $db->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->execute([$id]);
        $db->commit();
        echo json_encode(['success' => true]);
        break;

    // 切换问卷状态（管理员）
    case 'toggle_status':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare("UPDATE surveys SET status = CASE WHEN status = 'open' THEN 'closed' ELSE 'open' END WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    // 获取统计数据（管理员）
    case 'get_stats':
        if (!isset($_SESSION['admin_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'msg' => '未登录']);
            break;
        }
        $surveyId = $_GET['id'] ?? 0;

        // 获取问题
        $stmt = $db->prepare("SELECT * FROM questions WHERE survey_id = ? ORDER BY sort_order");
        $stmt->execute([$surveyId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($questions as $q) {
            $qid = $q['id'];
            $qStat = [
                'id' => $qid,
                'title' => $q['title'],
                'type' => $q['type'],
                'total' => 0,
                'options' => []
            ];

            if ($q['type'] === 'radio' || $q['type'] === 'checkbox') {
                // 选择题：统计每个选项的数量
                $opts = array_filter(array_map('trim', explode(',', $q['options'])));
                foreach ($opts as $opt) {
                    $qStat['options'][$opt] = 0;
                }

                $stmt = $db->prepare("SELECT answer FROM answers WHERE question_id = ?");
                $stmt->execute([$qid]);
                while ($a = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $qStat['total']++;
                    $ansOpts = array_map('trim', explode(', ', $a['answer']));
                    foreach ($ansOpts as $opt) {
                        if (isset($qStat['options'][$opt])) {
                            $qStat['options'][$opt]++;
                        }
                    }
                }
            } else {
                // 文本题：统计总数
                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM answers WHERE question_id = ? AND answer != ''");
                $stmt->execute([$qid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $qStat['total'] = $row['cnt'];
            }

            $stats[] = $qStat;
        }
        echo json_encode($stats);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
