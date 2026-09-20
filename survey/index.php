<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>填写问卷 · 宜春学院算法队</title>
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
  .container { max-width: 680px; margin: 0 auto; }
  .card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 16px; padding: 32px; margin-bottom: 20px;
  }
  h1 { font-size: 28px; margin-bottom: 8px; }
  .eyebrow { color: var(--accent); font-size: 12px; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 8px; }
  .q-item { margin-bottom: 24px; }
  .q-title { font-weight: 600; margin-bottom: 10px; }
  .q-title .req { color: #e06060; }
  input, textarea {
    width: 100%; padding: 10px 14px;
    background: #0d131a; border: 1px solid var(--border); border-radius: 8px;
    color: var(--fg); font-size: 15px; font-family: inherit;
  }
  input:focus, textarea:focus { outline: none; border-color: var(--accent); }
  .option-row { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
  .option-row input { width: auto; }
  button {
    width: 100%; padding: 12px; border: none; border-radius: 8px;
    background: var(--accent); color: #0d1014; font-weight: 600;
    cursor: pointer; font-size: 15px;
  }
  button:hover { opacity: 0.9; }
  .success { text-align: center; padding: 40px 0; }
  .success h2 { color: var(--accent); margin-bottom: 12px; }
  a { color: var(--accent); text-decoration: none; }
  .meta { color: var(--muted); font-size: 14px; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="container">
  <div id="form-page">
    <div class="eyebrow">Survey</div>
    <h1 id="survey-title">加载中...</h1>
    <p class="meta" id="survey-desc"></p>
    <div class="card">
      <div id="questions"></div>
      <div class="q-item">
        <div class="q-title">你的姓名 <span class="req">*</span></div>
        <input type="text" id="resp-name" placeholder="请输入姓名" />
      </div>
      <div class="q-item">
        <div class="q-title">联系方式</div>
        <input type="text" id="resp-contact" placeholder="QQ / 微信 / 邮箱（选填）" />
      </div>
      <button onclick="submitForm()">提交问卷</button>
    </div>
  </div>

  <div id="success-page" class="card hidden" style="display: none;">
    <div class="success">
      <h2>✓ 提交成功！</h2>
      <p style="color: var(--muted);">感谢你的填写，我们会尽快处理。</p>
      <br>
      <a href="../index.html">返回首页</a>
    </div>
  </div>
</div>

<script>
var surveyId = new URLSearchParams(location.search).get('id');

if (!surveyId) {
  document.getElementById('survey-title').textContent = '问卷不存在';
} else {
  loadSurvey(surveyId);
}

function loadSurvey(id) {
  fetch('api.php?action=get_survey&id=' + id)
    .then(r => r.json())
    .then(survey => {
      document.getElementById('survey-title').textContent = survey.title;
      document.getElementById('survey-desc').textContent = survey.description || '';
      var html = '';
      survey.questions.forEach(q => {
        html += '<div class="q-item">';
        html += '<div class="q-title">' + q.title + '</div>';
        if (q.type === 'text') {
          html += '<input type="text" data-qid="' + q.id + '" />';
        } else if (q.type === 'textarea') {
          html += '<textarea rows="4" data-qid="' + q.id + '"></textarea>';
        } else if (q.type === 'radio' || q.type === 'checkbox') {
          var opts = q.options.split(',').map(s => s.trim()).filter(Boolean);
          opts.forEach(opt => {
            html += '<label class="option-row">';
            html += '<input type="' + q.type + '" name="q' + q.id + '" value="' + opt + '" data-qid="' + q.id + '" />';
            html += '<span>' + opt + '</span>';
            html += '</label>';
          });
        }
        html += '</div>';
      });
      document.getElementById('questions').innerHTML = html;
    });
}

function submitForm() {
  var answers = {};
  document.querySelectorAll('[data-qid]').forEach(el => {
    var qid = el.dataset.qid;
    if (el.type === 'checkbox') {
      if (!answers[qid]) answers[qid] = [];
      if (el.checked) answers[qid].push(el.value);
    } else if (el.type === 'radio') {
      if (el.checked) answers[qid] = el.value;
    } else {
      answers[qid] = el.value;
    }
  });

  var data = {
    survey_id: parseInt(surveyId),
    name: document.getElementById('resp-name').value,
    contact: document.getElementById('resp-contact').value,
    answers: answers
  };

  fetch('api.php?action=submit_response', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  })
    .then(r => r.json())
    .then(result => {
      if (result.success) {
        document.getElementById('form-page').style.display = 'none';
        document.getElementById('success-page').style.display = 'block';
      } else {
        alert('提交失败，请重试');
      }
    });
}
</script>
</body>
</html>
