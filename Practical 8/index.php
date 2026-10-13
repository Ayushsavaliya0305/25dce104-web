<?php
// Practical 8: StudentHub — students, events, registrations via PDO + Prepared Statements.
declare(strict_types=1);
require_once __DIR__ . '/db.php';   // gives us $pdo

$message = '';
$messageType = '';
$registrations = [];

function flash(string $text, string $type = 'success'): void
{
    global $message, $messageType;
    $message = $text;
    $messageType = $type;
}

// ---------- Handle POST actions (all use PREPARED statements) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        // 1) Add student — named placeholders + bindValue
        if ($action === 'add_student') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $department = trim($_POST['department'] ?? '');
            if ($name === '' || $email === '' || $department === '') {
                throw new RuntimeException('Name, email and department are required.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid email address.');
            }
            // Prepared statement: SQL and data are sent separately, so input
            // can never break out of quotes (prevents SQL injection).
            $stmt = $pdo->prepare(
                'INSERT INTO students (name, email, phone, department)
                 VALUES (:name, :email, :phone, :department)'
            );
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':phone', $phone !== '' ? $phone : null, $phone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':department', $department);
            $stmt->execute();
            flash('Student added (ID ' . (int)$pdo->lastInsertId() . ').');
        }

        // 2) Add event — positional (?) placeholders + execute([...])
        elseif ($action === 'add_event') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $event_date = trim($_POST['event_date'] ?? '');
            $venue = trim($_POST['venue'] ?? '');
            $max_seats = (int)($_POST['max_seats'] ?? 100);
            if ($title === '' || $event_date === '' || $venue === '') {
                throw new RuntimeException('Title, date and venue are required.');
            }
            if ($max_seats < 1) {
                throw new RuntimeException('Max seats must be at least 1.');
            }
            $stmt = $pdo->prepare(
                'INSERT INTO events (title, description, event_date, venue, max_seats)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$title, $description !== '' ? $description : null, $event_date, $venue, $max_seats]);
            flash('Event added (ID ' . (int)$pdo->lastInsertId() . ').');
        }

        // 3) Register student for event — prepared INSERT with UNIQUE guard
        elseif ($action === 'register') {
            $student_id = (int)($_POST['student_id'] ?? 0);
            $event_id = (int)($_POST['event_id'] ?? 0);
            if ($student_id < 1 || $event_id < 1) {
                throw new RuntimeException('Select both a student and an event.');
            }
            $stmt = $pdo->prepare(
                'INSERT INTO registrations (student_id, event_id) VALUES (:sid, :eid)'
            );
            $stmt->execute([':sid' => $student_id, ':eid' => $event_id]);
            flash('Registration successful (Reg ID ' . (int)$pdo->lastInsertId() . ').');
        }

        // 4) Cancel registration — prepared UPDATE
        elseif ($action === 'cancel') {
            $reg_id = (int)($_POST['reg_id'] ?? 0);
            $stmt = $pdo->prepare(
                "UPDATE registrations SET status = 'cancelled' WHERE reg_id = ?"
            );
            $stmt->execute([$reg_id]);
            flash($stmt->rowCount() ? 'Registration cancelled.' : 'No such registration.', $stmt->rowCount() ? 'success' : 'error');
        }
    } catch (PDOException $e) {
        // 23000 = integrity violation: duplicate email / duplicate registration / bad FK
        if ($e->getCode() === '23000') {
            flash('Database rejected it (duplicate email, duplicate registration, or invalid ID).', 'error');
        } else {
            flash('Database error: ' . $e->getMessage(), 'error');
        }
    } catch (RuntimeException $e) {
        flash($e->getMessage(), 'error');
    }
}

// ---------- Reads (prepared SELECTs) for dropdowns + report ----------
// Prepared SELECT with JOIN — shows who registered for which event.
$stmt = $pdo->prepare(
    'SELECT r.reg_id, r.registration_date, r.status,
            s.student_id, s.name AS student_name, s.email,
            e.event_id, e.title AS event_title, e.event_date, e.venue
     FROM registrations r
     JOIN students s ON s.student_id = r.student_id
     JOIN events   e ON e.event_id   = r.event_id
     ORDER BY r.registration_date DESC'
);
$stmt->execute();
$registrations = $stmt->fetchAll();

$students = $pdo->query('SELECT student_id, name, email FROM students ORDER BY name')->fetchAll();
$events = $pdo->query('SELECT event_id, title, event_date FROM events ORDER BY event_date')->fetchAll();

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudentHub — Practical 8</title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; font-family: Arial, sans-serif; background: #f4f7fb; color: #172033; }
  main { max-width: 1000px; margin: auto; padding: 32px 18px 60px; }
  h1 { color: #1e3a8a; margin: 0; }
  .sub { color: #62708a; margin: 6px 0 20px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
  @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } }
  form.card { background: #fff; border-radius: 10px; padding: 18px; box-shadow: 0 4px 16px #182b5012; }
  h2 { margin: 0 0 12px; font-size: 1.05rem; color: #1e3a8a; }
  label { display: block; font-size: .82rem; font-weight: bold; margin: 10px 0 4px; }
  input, select, textarea { width: 100%; padding: 9px; font: inherit; border: 1px solid #cbd5e1; border-radius: 6px; }
  button { margin-top: 12px; padding: 10px 16px; font: inherit; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer; }
  button.danger { background: #dc2626; margin-top: 0; padding: 5px 10px; font-size: .8rem; }
  .msg { padding: 12px; border-radius: 8px; margin-bottom: 16px; }
  .msg.success { background: #dcfce7; color: #166534; }
  .msg.error { background: #fee2e2; color: #991b1b; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; margin-top: 18px; font-size: .9rem; }
  th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
  th { background: #1e3a8a; color: #fff; }
  .pill { padding: 2px 10px; border-radius: 20px; font-size: .78rem; }
  .pill.registered { background: #dcfce7; color: #166534; }
  .pill.cancelled { background: #fee2e2; color: #991b1b; }
  code { background: #e2e8f0; padding: 1px 6px; border-radius: 4px; }
</style>
</head>
<body>
<main>
  <h1>StudentHub</h1>
  <p class="sub">Practical 8: MySQL schema + ER model + PDO connectivity + prepared statements.</p>

  <?php if ($message): ?>
    <div class="msg <?= e($messageType) ?>"><?= e($message) ?></div>
  <?php endif; ?>

  <div class="grid">
    <form class="card" method="post">
      <h2>1. Add Student</h2>
      <input type="hidden" name="action" value="add_student">
      <label>Name <input name="name" maxlength="100" required></label>
      <label>Email <input name="email" type="email" maxlength="100" required></label>
      <label>Phone <input name="phone" maxlength="15"></label>
      <label>Department <input name="department" maxlength="50" required></label>
      <button>Add Student</button>
    </form>

    <form class="card" method="post">
      <h2>2. Add Event</h2>
      <input type="hidden" name="action" value="add_event">
      <label>Title <input name="title" maxlength="150" required></label>
      <label>Description <textarea name="description" rows="2"></textarea></label>
      <label>Date <input name="event_date" type="date" required></label>
      <label>Venue <input name="venue" maxlength="100" required></label>
      <label>Max seats <input name="max_seats" type="number" min="1" value="100"></label>
      <button>Add Event</button>
    </form>

    <form class="card" method="post">
      <h2>3. Register</h2>
      <input type="hidden" name="action" value="register">
      <label>Student
        <select name="student_id" required>
          <option value="">— select —</option>
          <?php foreach ($students as $s): ?>
            <option value="<?= (int)$s['student_id'] ?>"><?= e($s['name']) ?> (<?= e($s['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Event
        <select name="event_id" required>
          <option value="">— select —</option>
          <?php foreach ($events as $ev): ?>
            <option value="<?= (int)$ev['event_id'] ?>"><?= e($ev['title']) ?> — <?= e($ev['event_date']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button>Register</button>
      <p style="font-size:.78rem;color:#64748b">Uses <code>INSERT … VALUES (:sid, :eid)</code> — duplicate registration is blocked by <code>UNIQUE(student_id, event_id)</code>.</p>
    </form>
  </div>

  <h2 style="margin-top:26px">Registrations (JOIN via prepared SELECT)</h2>
  <table>
    <tr><th>Reg ID</th><th>Student</th><th>Event</th><th>Date</th><th>Status</th><th>Action</th></tr>
    <?php if (!$registrations): ?>
      <tr><td colspan="6">No registrations yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($registrations as $r): ?>
      <tr>
        <td><?= (int)$r['reg_id'] ?></td>
        <td><?= e($r['student_name']) ?><br><small><?= e($r['email']) ?></small></td>
        <td><?= e($r['event_title']) ?><br><small><?= e($r['event_date']) ?> · <?= e($r['venue']) ?></small></td>
        <td><?= e($r['registration_date']) ?></td>
        <td><span class="pill <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
        <td>
          <?php if ($r['status'] === 'registered'): ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="reg_id" value="<?= (int)$r['reg_id'] ?>">
            <button class="danger">Cancel</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</main>
</body>
</html>
