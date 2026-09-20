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
  button.danger { background: #e06060; color: white; }
  button.small { padding: 6px 12px; font-size: 12px; }
  .question-item {
    background: #0d131a; border: 1px solid var(--border);
    border-radius: 10px; padding: 16px; margin-bottom: 12px;
  }
  .row { display: flex; gap: 12px; align-items: center; }
  .row input { margin: 0; }
  .list-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 0; border-bottom: 1px solid var(--border);
  }
  .list-item:last-child { border-bottom: none; }
  .hidden { display: none; }
  .tag { font-size: 12px; padding: 2px 8px; border-radius: 999px; background: rgba(52,209,127,.15); color: var(--accent); }
  .tag.closed { background: rgba(224,96,96,.15); color: #e06060; }
  a { color: var(--accent); text-decoration: none; }
  .btn-group { display: flex; gap: 8px; flex-wrap: wrap; }

  /* 统计图表样式 */
  .stat-block { margin-bottom: 24px; }
  .stat-title { font-weight: 600; margin-bottom: 10px; }
  .stat-type { font-size: 12px; color: var(--muted); margin-bottom: 12px; }
  .bar-row { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
  .bar-label { width: 120px; font-size: 13px; color: var(--muted); }
  .bar-track { flex: 1; height: 24px; background: #0d131a; border-radius: 6px; overflow: hidden; }
  .bar-fill { height: 100%; background: var(--accent); display: flex; align-items: center; padding-left: 8px; font-size: 12px; font-weight: 600; color: #0d1014; }
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
        <div class="btn-group">
          <button onclick="showCreate()">新建问卷</button>
          <button class="secondary" onclick="logout()">退出登录</button>
        </div>
      </div>
      <div id="survey-list"></div>
    </div>

    <!-- 创建/编辑问卷 -->
    <div id="edit-page" class="card hidden">
      <h2 id="edit-title">新建问卷</h2>
      <input type="hidden" id="edit-id" value="" />
      <label>问卷标题</label>
      <input type="text" id="survey-title" placeholder="例如：招新意向调查" />
      <label>问卷说明</label>
      <textarea id="survey-desc" rows="3" placeholder="简单说明问卷用途"></textarea>

      <h3 style="margin: 20px 0 12px; font-size: 16px;">问题列表</h3>
      <div id="questions"></div>
      <button class="secondary" onclick="addQuestion()" style="margin-bottom: 20px;">+ 添加问题</button>

      <div class="btn-group">
        <button onclick="saveSurvey()">保存问卷</button>
        <button class="secondary" onclick="showList()">返回列表</button>
      </div>
    </div>

    <!-- 查看结果 -->
    <div id="result-page" class="card hidden">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <h2 id="result-title" style="margin: 0;">问卷结果</h2>
        <div class="btn-group">
          <button onclick="loadStats()">统计图表</button>
          <button onclick="loadResponses()">原始数据</button>
          <button id="export-btn" onclick="exportCSV()">导出 CSV</button>
          <button class="secondary" onclick="showList()">返回列表</button>
        </div>
      </div>

      <!-- 统计图表 -->
      <div id="stats-view"></div>

      <!-- 原始数据 -->
      <div id="responses-view" class="hidden"></div>
    </div>
  </div>
</div>

<script>
var currentSurveyId = null;
var currentMode = 'responses';

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
        var statusTag = s.status === 'open'
          ? '<span class="tag">收集开放中</span>'
          : '<span class="tag closed">已关闭</span>';
        html += '<div class="list-item">';
        html += '<div><b>' + s.title + '</b> ' + statusTag + '<br><small style="color: var(--muted);">' + s.created_at + '</small></div>';
        html += '<div class="btn-group">';
        html += '<button class="small secondary" onclick="viewResults(' + s.id + ', \'' + s.title + '\')">查看结果</button>';
        html += '<button class="small secondary" onclick="editSurvey(' + s.id + ', \'' + s.title + '\')">编辑</button>';
        html += '<button class="small secondary" onclick="toggleStatus(' + s.id + ')">' + (s.status === 'open' ? '关闭' : '开启') + '</button>';
        html += '<a href="index.php?id=' + s.id + '" target="_blank"><button class="small">填写问卷</button></a>';
        html += '<button class="small danger" onclick="deleteSurvey(' + s.id + ')">删除</button>';
        html += '</div></div>';
      });
      document.getElementById('survey-list').innerHTML = html || '<p style="color: var(--muted);">暂无问卷，点击"新建问卷"创建</p>';
    });
}

function showCreate() {
  document.getElementById('edit-title').textContent = '新建问卷';
  document.getElementById('edit-id').value = '';
  document.getElementById('survey-title').value = '';
  document.getElementById('survey-desc').value = '';
  document.getElementById('questions').innerHTML = '';
  addQuestion();
  document.getElementById('edit-page').classList.remove('hidden');
  document.getElementById('result-page').classList.add('hidden');
  document.getElementById('survey-list').parentElement.style.display = 'none';
}

function showList() {
  document.getElementById('edit-page').classList.add('hidden');
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
      <button class="secondary small" onclick="this.parentElement.parentElement.remove()">删除</button>
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

function saveSurvey() {
  var id = document.getElementById('edit-id').value;
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

  var url = id ? 'api.php?action=update_survey' : 'api.php?action=create_survey';
  var body = id ? { id: parseInt(id), title: title, description: desc, questions: questions }
                : { title: title, description: desc, questions: questions };

  fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert(id ? '修改成功！' : '创建成功！');
        showList();
      }
    });
}

function editSurvey(id, title) {
  fetch('api.php?action=get_survey&id=' + id)
    .then(r => r.json())
    .then(survey => {
      document.getElementById('edit-title').textContent = '编辑问卷：' + title;
      document.getElementById('edit-id').value = id;
      document.getElementById('survey-title').value = survey.title;
      document.getElementById('survey-desc').value = survey.description || '';
      document.getElementById('questions').innerHTML = '';

      survey.questions.forEach(q => {
        var div = document.createElement('div');
        div.className = 'question-item';
        div.innerHTML = `
          <div class="row" style="margin-bottom: 10px;">
            <select style="width: 140px;" onchange="toggleOptions(this)">
              <option value="text" ${q.type === 'text' ? 'selected' : ''}>单行文本</option>
              <option value="textarea" ${q.type === 'textarea' ? 'selected' : ''}>多行文本</option>
              <option value="radio" ${q.type === 'radio' ? 'selected' : ''}>单选题</option>
              <option value="checkbox" ${q.type === 'checkbox' ? 'selected' : ''}>多选题</option>
            </select>
            <input type="text" placeholder="问题内容" style="flex: 1;" value="${q.title}" />
            <button class="secondary small" onclick="this.parentElement.parentElement.remove()">删除</button>
          </div>
          <input type="text" class="options-input ${(q.type === 'radio' || q.type === 'checkbox') ? '' : 'hidden'}" placeholder="选项（用逗号分隔）" value="${q.options || ''}" />
        `;
        document.getElementById('questions').appendChild(div);
      });

      document.getElementById('edit-page').classList.remove('hidden');
      document.getElementById('result-page').classList.add('hidden');
      document.getElementById('survey-list').parentElement.style.display = 'none';
    });
}

function deleteSurvey(id) {
  if (!confirm('确定删除这个问卷吗？所有回复数据也会一起删除！')) return;
  fetch('api.php?action=delete_survey&id=' + id)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('删除成功！');
        loadSurveys();
      }
    });
}

function toggleStatus(id) {
  fetch('api.php?action=toggle_status&id=' + id)
    .then(r => r.json())
    .then(data => {
      if (data.success) loadSurveys();
    });
}

function viewResults(id, title) {
  currentSurveyId = id;
  document.getElementById('result-title').textContent = title + ' - 结果';
  document.getElementById('edit-page').classList.add('hidden');
  document.getElementById('result-page').classList.remove('hidden');
  document.getElementById('survey-list').parentElement.style.display = 'none';
  loadResponses();
}

function loadResponses() {
  currentMode = 'responses';
  document.getElementById('stats-view').classList.add('hidden');
  document.getElementById('responses-view').classList.remove('hidden');

  fetch('api.php?action=get_results&id=' + currentSurveyId)
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
      document.getElementById('responses-view').innerHTML = html || '<p style="color: var(--muted);">暂无回复</p>';
    });
}

function loadStats() {
  currentMode = 'stats';
  document.getElementById('responses-view').classList.add('hidden');
  document.getElementById('stats-view').classList.remove('hidden');

  fetch('api.php?action=get_stats&id=' + currentSurveyId)
    .then(r => r.json())
    .then(stats => {
      var html = '<p style="color: var(--muted); margin-bottom: 16px;">统计概览</p>';
      var maxTotal = 1;
      stats.forEach(s => { if (s.type === 'radio' || s.type === 'checkbox') { var opts = Object.values(s.options); if (opts.length) maxTotal = Math.max(maxTotal, Math.max(...opts)); } });

      stats.forEach(s => {
        html += '<div class="stat-block">';
        html += '<div class="stat-title">' + s.title + '</div>';
        if (s.type === 'radio' || s.type === 'checkbox') {
          html += '<div class="stat-type">选择题 · 共 ' + s.total + ' 人作答</div>';
          Object.entries(s.options).forEach(([opt, count]) => {
            var pct = Math.round(count / maxTotal * 100);
            html += '<div class="bar-row">';
            html += '<div class="bar-label">' + opt + '</div>';
            html += '<div class="bar-track"><div class="bar-fill" style="width: ' + pct + '%">' + count + '</div></div>';
            html += '</div>';
          });
        } else {
          html += '<div class="stat-type">文本题 · 共 ' + s.total + ' 人作答</div>';
        }
        html += '</div>';
      });
      document.getElementById('stats-view').innerHTML = html || '<p style="color: var(--muted);">暂无数据</p>';
    });
}

function exportCSV() {
  window.location.href = 'api.php?action=export_csv&id=' + currentSurveyId;
}
</script>
</body>
</html>
