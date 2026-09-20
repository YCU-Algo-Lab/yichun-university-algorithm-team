<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>问卷管理 · 宜春学院算法队</title>
<link href="https://cdn.jsdelivr.net/npm/@fontsource-variable/space-grotesk@5.2.10/index.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fontsource-variable/manrope@5.2.8/index.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fontsource-variable/noto-sans-sc@5.2.10/index.css" rel="stylesheet">
<style>
  :root {
    --bg: #0d1014; --surface: #151a20; --fg: #e8ecf1; --muted: #93a0ae;
    --border: #242c37; --accent: #34d17f; --radius: 10px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    background: var(--bg); color: var(--fg);
    font-family: 'Manrope Variable', 'Noto Sans SC Variable', system-ui, sans-serif;
    min-height: 100vh; padding: 40px 20px;
  }
  .container { max-width: 800px; margin: 0 auto; }
  .card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 16px; padding: 32px; margin-bottom: 20px;
  }
  h1 { font-size: 28px; margin-bottom: 8px; }
  h2 { font-size: 20px; margin-bottom: 16px; }
  .eyebrow { color: var(--accent); font-size: 12px; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 8px; }
  input, textarea, select {
    width: 100%; padding: 10px 14px; margin: 6px 0 16px;
    background: #0d131a; border: 1px solid var(--border); border-radius: 8px;
    color: var(--fg); font-size: 15px; font-family: inherit;
  }
  input:focus, textarea:focus { outline: none; border-color: var(--accent); }
  button {
    padding: 10px 20px; border: none; border-radius: 8px;
    background: var(--accent); color: #0d1014; font-weight: 600;
    cursor: pointer; font-size: 14px;
  }
  button:hover { opacity: 0.9; }
  button.secondary { background: transparent; border: 1px solid var(--border); color: var(--fg); }
  .question-item {
    background: #0d131a; border: 1px solid var(--border);
    border-radius: 10px; padding: 16px; margin-bottom: 12px;
  }
  .row { display: flex; gap: 12px; align-items: center; }
  .row input { margin: 0; }
  .list-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 0; border-bottom: 1px solid var(--border);
  }
  .list-item:last-child { border-bottom: none; }
  .hidden { display: none; }
  .tag { font-size: 12px; padding: 2px 8px; border-radius: 999px; background: rgba(52,209,127,.15); color: var(--accent); }
  a { color: var(--accent); text-decoration: none; }
</style>
</head>
<body>
<div class="container">
  <div class="eyebrow">Survey Admin</div>
  <h1>问卷管理后台</h1>

  <!-- 登录页 -->
  <div id="login-page" class="card">
    <h2>管理员登录</h2>
    <label>用户名</label>
    <input type="text" id="login-user" placeholder="admin" />
    <label>密码</label>
    <input type="password" id="login-pass" placeholder="密码" />
    <button onclick="login()">登录</button>
    <p id="login-err" style="color: #e06060; margin-top: 12px; font-size: 14px;"></p>
  </div>

  <!-- 主页面 -->
  <div id="main-page" class="hidden">
    <div class="card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="margin: 0;">问卷列表</h2>
        <div style="gap: 8px; display: flex;">
          <button onclick="showCreate()">新建问卷</button>
          <button class="secondary" onclick="logout()">退出登录</button>
        </div>
      </div>
      <div id="survey-list"></div>
    </div>

    <!-- 创建问卷 -->
    <div id="create-page" class="card hidden">
      <h2>新建问卷</h2>
      <label>问卷标题</label>
      <input type="text" id="survey-title" placeholder="例如：招新意向调查" />
      <label>问卷说明</label>
      <textarea id="survey-desc" rows="3" placeholder="简单说明问卷用途"></textarea>

      <h3 style="margin: 20px 0 12px; font-size: 16px;">问题列表</h3>
      <div id="questions"></div>
      <button class="secondary" onclick="addQuestion()" style="margin-bottom: 20px;">+ 添加问题</button>

      <div style="display: flex; gap: 12px;">
        <button onclick="createSurvey()">创建问卷</button>
        <button class="secondary" onclick="showList()">返回列表</button>
      </div>
    </div>

    <!-- 查看结果 -->
    <div id="result-page" class="card hidden">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 id="result-title" style="margin: 0;">问卷结果</h2>
        <div style="gap: 8px; display: flex;">
          <button id="export-btn" onclick="exportCSV()">导出 CSV</button>
          <button class="secondary" onclick="showList()">返回列表</button>
        </div>
      </div>
      <div id="result-list"></div>
    </div>
  </div>
</div>

<script>
var currentSurveyId = null;

// 检查登录状态
fetch('api.php?action=check_auth')
  .then(r => r.json())
  .then(data => {
    if (data.logged_in) {
      showMain();
      loadSurveys();
    }
  });

function login() {
  var u = document.getElementById('login-user').value;
  var p = document.getElementById('login-pass').value;
  var fd = new FormData();
  fd.append('username', u);
  fd.append('password', p);
  fetch('api.php?action=login', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showMain();
        loadSurveys();
      } else {
        document.getElementById('login-err').textContent = data.msg;
      }
    });
}

function logout() {
  fetch('api.php?action=logout').then(() => location.reload());
}

function showMain() {
  document.getElementById('login-page').classList.add('hidden');
  document.getElementById('main-page').classList.remove('hidden');
}

function loadSurveys() {
  fetch('api.php?action=list_surveys')
    .then(r => r.json())
    .then(surveys => {
      var html = '';
      surveys.forEach(s => {
        html += '<div class="list-item">';
        html += '<div><b>' + s.title + '</b> <span class="tag">' + s.status + '</span><br><small style="color: var(--muted);">' + s.created_at + '</small></div>';
        html += '<div style="gap: 8px; display: flex;">';
        html += '<button class="secondary" onclick="viewResults(' + s.id + ', \'' + s.title + '\')">查看结果</button>';
        html += '<a href="index.php?id=' + s.id + '" target="_blank"><button>填写问卷</button></a>';
        html += '</div></div>';
      });
      document.getElementById('survey-list').innerHTML = html || '<p style="color: var(--muted);">暂无问卷，点击"新建问卷"创建</p>';
    });
}

function showCreate() {
  document.getElementById('create-page').classList.remove('hidden');
  document.getElementById('result-page').classList.add('hidden');
  document.getElementById('survey-list').parentElement.style.display = 'none';
  document.getElementById('survey-title').value = '';
  document.getElementById('survey-desc').value = '';
  document.getElementById('questions').innerHTML = '';
  addQuestion();
}

function showList() {
  document.getElementById('create-page').classList.add('hidden');
  document.getElementById('result-page').classList.add('hidden');
  document.getElementById('survey-list').parentElement.style.display = '';
  loadSurveys();
}

function addQuestion() {
  var div = document.createElement('div');
  div.className = 'question-item';
  div.innerHTML = `
    <div class="row" style="margin-bottom: 10px;">
      <select style="width: 140px;" onchange="toggleOptions(this)">
        <option value="text">单行文本</option>
        <option value="textarea">多行文本</option>
        <option value="radio">单选题</option>
        <option value="checkbox">多选题</option>
      </select>
      <input type="text" placeholder="问题内容" style="flex: 1;" />
      <button class="secondary" onclick="this.parentElement.parentElement.remove()">删除</button>
    </div>
    <input type="text" class="options-input hidden" placeholder="选项（用逗号分隔，如：大一,大二,大三）" />
  `;
  document.getElementById('questions').appendChild(div);
}

function toggleOptions(sel) {
  var input = sel.closest('.question-item').querySelector('.options-input');
  if (sel.value === 'radio' || sel.value === 'checkbox') {
    input.classList.remove('hidden');
  } else {
    input.classList.add('hidden');
  }
}

function createSurvey() {
  var title = document.getElementById('survey-title').value;
  var desc = document.getElementById('survey-desc').value;
  if (!title) { alert('请填写问卷标题'); return; }

  var questions = [];
  document.querySelectorAll('.question-item').forEach(item => {
    var type = item.querySelector('select').value;
    var qTitle = item.querySelectorAll('input')[0].value;
    var options = item.querySelector('.options-input').value;
    if (qTitle) questions.push({ type: type, title: qTitle, options: options });
  });

  fetch('api.php?action=create_survey', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ title: title, description: desc, questions: questions })
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('创建成功！');
        showList();
      }
    });
}

function viewResults(id, title) {
  currentSurveyId = id;
  document.getElementById('result-title').textContent = title + ' - 结果';
  document.getElementById('create-page').classList.add('hidden');
  document.getElementById('result-page').classList.remove('hidden');
  document.getElementById('survey-list').parentElement.style.display = 'none';

  fetch('api.php?action=get_results&id=' + id)
    .then(r => r.json())
    .then(responses => {
      var html = '<p style="color: var(--muted); margin-bottom: 16px;">共 ' + responses.length + ' 份回复</p>';
      responses.forEach((r, i) => {
        html += '<div class="question-item">';
        html += '<b>#' + (i+1) + ' ' + (r.respondent_name || '匿名') + '</b> <small style="color: var(--muted);">' + r.submitted_at + '</small>';
        if (r.respondent_contact) html += '<br><small style="color: var(--muted);">联系方式：' + r.respondent_contact + '</small>';
        html += '<pre style="margin-top: 8px; white-space: pre-wrap; font-size: 13px; color: var(--muted);">' + JSON.stringify(r.answers, null, 2) + '</pre>';
        html += '</div>';
      });
      document.getElementById('result-list').innerHTML = html || '<p style="color: var(--muted);">暂无回复</p>';
    });
}

function exportCSV() {
  window.location.href = 'api.php?action=export_csv&id=' + currentSurveyId;
}
</script>
</body>
</html>
