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

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
