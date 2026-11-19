<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// --- TABLES ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT 'business',
    price DECIMAL(10,2) DEFAULT 0,
    image VARCHAR(500),
    description TEXT,
    is_live_class INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    video_url VARCHAR(500),
    content TEXT,
    order_num INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    progress INTEGER DEFAULT 0,
    is_completed INTEGER DEFAULT 0,
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(course_id, student_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS lesson_progress (
    lesson_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    is_done INTEGER DEFAULT 1,
    done_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (lesson_id, student_id)
);
");

// --- CREATE COURSE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_title']) && $user_id) {
    $pdo->prepare("INSERT INTO courses (teacher_id, title, category, price, image, description) VALUES (?,?,?,?,?,?)")
        ->execute([$user_id, trim($_POST['course_title']), $_POST['category'] ?? 'business', (float)$_POST['price'], trim($_POST['image'] ?? ''), trim($_POST['description'] ?? '')]);
    header("Location: academy.php");
    exit;
}

// --- ADD LESSON ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lesson_title'], $_POST['course_id']) && $user_id) {
    $c = $pdo->prepare("SELECT * FROM courses WHERE id=? AND teacher_id=?");
    $c->execute([(int)$_POST['course_id'], $user_id]);
    if ($c->fetch()) {
        $pdo->prepare("INSERT INTO lessons (course_id, title, video_url, content) VALUES (?,?,?,?)")
            ->execute([(int)$_POST['course_id'], trim($_POST['lesson_title']), trim($_POST['video_url'] ?? ''), trim($_POST['lesson_content'] ?? '')]);
    }
    header("Location: academy.php?course=" . (int)$_POST['course_id']);
    exit;
}

// --- ENROLL ---
if (isset($_GET['enroll']) && $user_id) {
    $pdo->prepare("INSERT OR IGNORE INTO enrollments (course_id, student_id) VALUES (?,?)")->execute([(int)$_GET['enroll'], $user_id]);
    header("Location: academy.php?course=" . (int)$_GET['enroll']);
    exit;
}

// --- MARK LESSON DONE ---
if (isset($_GET['done'], $_GET['course']) && $user_id) {
    $pdo->prepare("INSERT OR IGNORE INTO lesson_progress (lesson_id, student_id) VALUES (?,?)")->execute([(int)$_GET['done'], $user_id]);
    // Check if all lessons done -> mark course completed
    $total = $pdo->prepare("SELECT COUNT(*) as c FROM lessons WHERE course_id=?");
    $total->execute([(int)$_GET['course']]);
    $total = $total->fetch()['c'];
    $done = $pdo->prepare("SELECT COUNT(*) as c FROM lesson_progress lp JOIN lessons l ON l.id=lp.lesson_id WHERE l.course_id=? AND lp.student_id=?");
    $done->execute([(int)$_GET['course'], $user_id]);
    $done = $done->fetch()['c'];
    if ($total > 0 && $done >= $total) {
        $pdo->prepare("UPDATE enrollments SET is_completed=1, progress=100 WHERE course_id=? AND student_id=?")->execute([(int)$_GET['course'], $user_id]);
    } else if ($total > 0) {
        $prog = round(($done / $total) * 100);
        $pdo->prepare("UPDATE enrollments SET progress=? WHERE course_id=? AND student_id=?")->execute([$prog, (int)$_GET['course'], $user_id]);
    }
    header("Location: academy.php?course=" . (int)$_GET['course']);
    exit;
}

// --- FILTER ---
$filter = $_GET['cat'] ?? 'all';
$allowed = ['all','selling','music','tech','business','driving','school'];
if (!in_array($filter, $allowed)) $filter = 'all';

$viewCourse = null;
$viewLessons = [];
$myEnrollment = null;
if (isset($_GET['course'])) {
    $st = $pdo->prepare("SELECT c.*, u.username as teacher FROM courses c JOIN users u ON u.id=c.teacher_id WHERE c.id=?");
    $st->execute([(int)$_GET['course']]);
    $viewCourse = $st->fetch();
    if ($viewCourse) {
        $ls = $pdo->prepare("SELECT * FROM lessons WHERE course_id=? ORDER BY order_num, id ASC");
        $ls->execute([$viewCourse['id']]);
        $viewLessons = $ls->fetchAll(PDO::FETCH_ASSOC);
        if ($user_id) {
            $e = $pdo->prepare("SELECT * FROM enrollments WHERE course_id=? AND student_id=?");
            $e->execute([$viewCourse['id'], $user_id]);
            $myEnrollment = $e->fetch();
        }
    }
}

if ($filter === 'all') {
    $courses = $pdo->query("SELECT c.*, u.username as teacher, (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) as students, (SELECT COUNT(*) FROM lessons WHERE course_id=c.id) as lesson_count FROM courses c JOIN users u ON u.id=c.teacher_id ORDER BY c.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $st = $pdo->prepare("SELECT c.*, u.username as teacher, (SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) as students, (SELECT COUNT(*) FROM lessons WHERE course_id=c.id) as lesson_count FROM courses c JOIN users u ON u.id=c.teacher_id WHERE c.category=? ORDER BY c.created_at DESC");
    $st->execute([$filter]);
    $courses = $st->fetchAll(PDO::FETCH_ASSOC);
}

function catLabel($c) {
    return match($c) {
        'selling' => '🛍️ Selling',
        'music' => '🎵 Music',
        'tech' => '💻 Tech / Coding',
        'business' => '💼 Business',
        'driving' => '🚗 Driving',
        'school' => '📚 School',
        default => '📚 Course'
    };
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shack Academy — Learn. Build. Earn. | 1989 Shack</title>
<style>
  body { background:#0f0f0f; color:#fff; font-family:Arial,sans-serif; margin:0; }
  a { color:#fff; text-decoration:none; }
  .wrap { max-width:1000px; margin:0 auto; padding:12px; }
  .topbar { background:#000; border-bottom:1px solid #333; padding:10px 12px; display:flex; justify-content:space-between; align-items:center; }
  .hero { background:linear-gradient(135deg,#00b894,#0984e3); padding:20px; border-radius:16px; margin:12px 0; }
  .hero h1 { margin:0; font-size:26px; }
  .btn { display:inline-block; background:#fff; color:#000; padding:11px 20px; border-radius:20px; font-weight:bold; border:none; cursor:pointer; }
  .btn-blue { background:#0984e3; color:#fff; }
  .btn-dark { background:#272727; color:#fff; border:1px solid #444; }
  .cats { display:flex; gap:8px; flex-wrap:wrap; margin:12px 0; }
  .cats a { background:#272727; padding:8px 14px; border-radius:20px; font-size:14px; font-weight:bold; }
  .cats a.active { background:#00b894; color:#000; }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
  .card { background:#1f1f1f; border-radius:12px; overflow:hidden; display:block; }
  .card img { width:100%; height:160px; object-fit:cover; background:#333; }
  .card-body { padding:12px; }
  .price { color:#00b894; font-weight:bold; }
  .free { color:#00b894; }
  .box { background:#1f1f1f; padding:14px; border-radius:12px; margin:12px 0; }
  input, select, textarea { width:100%; padding:11px; border-radius:10px; border:1px solid #444; background:#272727; color:#fff; margin:6px 0; }
  .lesson { background:#272727; padding:12px; border-radius:10px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; gap:10px; }
  .progress { background:#333; height:10px; border-radius:10px; overflow:hidden; margin:10px 0; }
  .progress div { background:#00b894; height:100%; }
  .cert { background:#ffeaa7; color:#000; padding:20px; border-radius:12px; text-align:center; margin:14px 0; }
</style>
</head>
<body>
<div class="topbar">
  <a href="index.php" style="font-weight:900; font-size:20px;">1989 <span style="color:#ff0000;">SHACK</span></a>
  <div>
    <a href="index.php" style="margin-right:10px;">Home</a>
    <a href="origin.php" style="margin-right:10px;">Origin Core</a>
    <a href="academy.php" style="color:#00b894; font-weight:bold;">Academy</a>
  </div>
</div>

<div class="wrap">
  <div class="hero">
    <h1>🎓 Shack Academy — Learn. Build. Earn.</h1>
    <p>Learn how to sell live, produce music, code, drive, and build business. Taught by real people in The Shack.</p>
    <?php if ($user_id): ?>
      <a class="btn" href="#teach">Become a Teacher →</a>
    <?php else: ?>
      <a class="btn" href="login.php">Login to Learn</a>
    <?php endif; ?>
  </div>

  <!-- VIEW SINGLE COURSE -->
  <?php if ($viewCourse): ?>
    <a href="academy.php" class="btn btn-dark" style="font-size:13px;">← Back to all courses</a>
    <div class="box">
      <h2><?= htmlspecialchars($viewCourse['title']) ?></h2>
      <p style="color:#aaa;">Teacher: <?= htmlspecialchars($viewCourse['teacher']) ?> • <?= catLabel($viewCourse['category']) ?> • <?= count($viewLessons) ?> lessons</p>
      <p><?= htmlspecialchars($viewCourse['description']) ?></p>
      <p><b class="price"><?= $viewCourse['price'] == 0 ? 'FREE' : '$' . number_format($viewCourse['price'],2) ?></b></p>

      <?php if ($myEnrollment && $myEnrollment['is_completed']): ?>
        <div class="cert">
          <h2>🏆 SHACK CERTIFIED</h2>
          <p><b><?= htmlspecialchars($username) ?></b> has completed<br><b><?= htmlspecialchars($viewCourse['title']) ?></b><br>1989 Shack Academy • Origin Core • Liberia 🇱🇷</p>
          <p style="font-size:12px;">Certificate ID: SHACK-<?= $viewCourse['id'] ?>-<?= $user_id ?>-<?= date('Y') ?></p>
        </div>
      <?php elseif ($myEnrollment): ?>
        <p><b>Your Progress: <?= $myEnrollment['progress'] ?>%</b></p>
        <div class="progress"><div style="width:<?= $myEnrollment['progress'] ?>%"></div></div>
      <?php elseif ($user_id): ?>
        <a class="btn btn-blue" href="academy.php?enroll=<;?= $viewCourse['id'] ?>">Enroll Now — <?= $viewCourse['price']==0 ? 'FREE' : '$'.number_format($viewCourse['price'],2) ?></a>
      <?php else: ?>
        <a class="btn btn-blue" href="login.php">Login to Enroll</a>
      <?php endif; ?>
    </div>

    <h3>Lessons (<?= count($viewLessons) ?>)</h3>
    <?php if (empty($viewLessons)): ?>
      <p style="color:#aaa;">No lessons yet. Teacher is still preparing.</p>
    <?php endif; ?>
    <?php foreach ($viewLessons as $idx => $l): 
      $done = false;
      if ($user_id) {
        $d = $pdo->prepare("SELECT 1 FROM lesson_progress WHERE lesson_id=? AND student_id=?");
        $d->execute([$l['id'], $user_id]);
        $done = (bool)$d->fetch();
      }
    ?>
    <div class="lesson">
      <div>
        <b>Lesson <?= $idx+1 ?>: <?= htmlspecialchars($l['title']) ?></b><br>
        <span style="color:#aaa; font-size:13px;"><?= htmlspecialchars(substr($l['content'] ?? '',0,100)) ?></span><br>
        <?php if ($l['video_url']): ?><a href="<?= htmlspecialchars($l['video_url']) ?>" target="_blank" style="color:#00b894; font-size:13px;">▶ Watch Video</a><?php endif; ?>
      </div>
      <div>
        <?php if ($myEnrollment && !$done): ?>
          <a class="btn btn-blue" style="padding:8px 14px; font-size:13px;" href="academy.php?course=<;?= $viewCourse['id'] ?>&done=<?= $l['id'] ?>">Mark Done ✓</a>
        <?php elseif ($done): ?>
          <span style="color:#00b894; font-weight:bold;">✓ Done</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- ADD LESSON (teacher only) -->
    <?php if ($user_id && $viewCourse['teacher_id'] == $user_id): ?>
    <div class="box">
      <h3>+ Add Lesson to This Course</h3>
      <form method="POST">
        <input type="hidden" name="course_id" value="<?= $viewCourse['id'] ?>">
        <input type="text" name="lesson_title" placeholder="Lesson title — e.g. How to set up your first live" required>
        <input type="text" name="video_url" placeholder="YouTube video link (optional)">
        <textarea name="lesson_content" placeholder="What will students learn in this lesson?" rows="2"></textarea>
        <button class="btn btn-blue" type="submit">Add Lesson</button>
      </form>
    </div>
    <?php endif; ?>

  <?php else: ?>

  <!-- CATEGORIES -->
  <div class="cats">
    <a href="academy.php?cat=all" class="<?= $filter==='all'?'active':'' ?>">All</a>
    <a href="academy.php?cat=selling" class="<?= $filter==='selling'?'active':'' ?>">🛍️ Selling</a>
    <a href="academy.php?cat=music" class="<?= $filter==='music'?'active':'' ?>">🎵 Music</a>
    <a href="academy.php?cat=tech" class="<?= $filter==='tech'?'active':'' ?>">💻 Tech</a>
    <a href="academy.php?cat=business" class="<?= $filter==='business'?'active':'' ?>">💼 Business</a>
    <a href="academy.php?cat=driving" class="<?= $filter==='driving'?'active':'' ?>">🚗 Driving</a>
    <a href="academy.php?cat=school" class="<?= $filter==='school'?'active':'' ?>">📚 School</a>
  </div>

  <!-- TEACH -->
  <?php if ($user_id): ?>
  <details class="box" id="teach">
    <summary style="cursor:pointer; font-weight:bold;">🎓 Teach in Shack Academy — Create a Course</summary>
    <form method="POST" style="margin-top:10px;">
      <input type="text" name="course_title" placeholder="Course title — e.g. How to Sell Live in The Shack" required>
      <select name="category">
        <option value="selling">🛍️ Selling</option>
        <option value="music">🎵 Music</option>
        <option value="tech">💻 Tech / Coding</option>
        <option value="business">💼 Business</option>
        <option value="driving">🚗 Driving</option>
        <option value="school">📚 School</option>
      </select>
      <input type="number" step="0.01" name="price" placeholder="Price — 0 for FREE" value="0">
      <input type="text" name="image" placeholder="Course cover image link">
      <textarea name="description" placeholder="What will students learn?" rows="2"></textarea>
      <button class="btn btn-blue" type="submit">Create Course</button>
    </form>
    <p style="color:#aaa; font-size:12px;">Tip: Start with FREE courses to get students. Then make paid advanced courses.</p>
  </details>
  <?php endif; ?>

  <!-- COURSE GRID -->
  <h2>📚 Courses — <?= ucfirst($filter) ?> (<?= count($courses) ?>)</h2>
  <?php if (empty($courses)): ?>
    <div style="background:#1f1f1f; padding:30px; border-radius:12px; text-align:center; color:#aaa;">
      No courses in this category yet.<br>Be the first teacher!
    </div>
  <?php endif; ?>
  <div class="grid">
    <?php foreach ($courses as $c): ?>
    <a class="card" href="academy.php?course=<;?= $c['id'] ?>">
      <img src="<?= htmlspecialchars($c['image'] ?: 'https://via.placeholder.com/600x340?text=SHACK+ACADEMY') ?>">
      <div class="card-body">
        <span style="background:#00b894; color:#000; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:bold;"><?= catLabel($c['category']) ?></span>
        <div style="font-weight:bold; margin-top:8px; font-size:16px;"><?= htmlspecialchars($c['title']) ?></div>
        <div style="color:#aaa; font-size:13px;">Teacher: <?= htmlspecialchars($c['teacher']) ?> • <?= $c['students'] ?> students • <?= $c['lesson_count'] ?> lessons</div>
        <div style="margin-top:8px;"><span class="price"><?= $c['price']==0 ? 'FREE' : '$'.number_format($c['price'],2) ?></span> • <span style="color:#0984e3; font-weight:bold;">View Course →</span></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <p style="text-align:center; color:#666; font-size:12px; margin-top:30px;">
    Shack Academy — Learn in The Shack, Earn in The Shack.<br>
    Next: Shack Shop (E-commerce) — Where students become sellers.
  </p>
</div>
</body>
</html>