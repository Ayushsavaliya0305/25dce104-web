# Practical 8 — StudentHub: MySQL Schema, ER Model, PDO + Prepared Statements

## 1. Aim
Design the `studenthub` database (students, events, registrations), draw its ER
model, connect PHP with PDO, and perform all inserts/updates/selects through
**prepared statements**.

## 2. Files
| File | Purpose |
|---|---|
| `schema.sql` | `CREATE DATABASE` + 3 tables + FKs + sample data. Import via phpMyAdmin. |
| `db.php` | Single PDO connection used by every page (`require_once 'db.php'`). |
| `index.php` | Demo app: add student, add event, register, cancel, and JOIN report. |

## 3. ER Model

```
+------------+          +----------------+          +------------+
|  students  |          | registrations  |          |   events   |
+------------+          +----------------+          +------------+
| PK student_id |<----->| PK reg_id      |<----->| PK event_id  |
| name         |  1  M  | FK student_id  |  M  1  | title        |
| email UNIQUE |        | FK event_id    |        | description  |
| phone        |        | reg. date      |        | event_date   |
| department   |        | status         |        | venue        |
+------------+          +----------------+        | max_seats    |
                                                 +------------+
```

* **students 1 — M registrations** (one student, many registrations; FK `student_id` with `ON DELETE CASCADE`).
* **events 1 — M registrations** (one event, many registrations; FK `event_id` with `ON DELETE CASCADE`).
* **students M — N events** resolved by the junction table `registrations`.
* `UNIQUE(student_id, event_id)` stops a double booking; `status` is
  `registered / cancelled`.

## 4. Schema summary
* `students(student_id PK AI, name, email UNIQUE, phone, department, created_at)`
* `events(event_id PK AI, title, description, event_date, venue, max_seats, created_at)`
* `registrations(reg_id PK AI, student_id FK, event_id FK, registration_date, status, UNIQUE(student_id, event_id))`
* Engine `InnoDB` (required for foreign keys), charset `utf8mb4`.

## 5. PDO connectivity (`db.php`)
```php
$pdo = new PDO(
  'mysql:host=127.0.0.1;dbname=studenthub;charset=utf8mb4',
  'root', '',
  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // exceptions
   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
   PDO::ATTR_EMULATE_PREPARES => false]           // real prepares
);
```

## 6. Prepared statements used in `index.php`
| Operation | SQL pattern |
|---|---|
| Add student (named + `bindValue`) | `INSERT INTO students (…) VALUES (:name, :email, :phone, :department)` |
| Add event (positional `?`) | `INSERT INTO events (…) VALUES (?, ?, ?, ?, ?)` |
| Register | `INSERT INTO registrations (student_id, event_id) VALUES (:sid, :eid)` |
| Cancel | `UPDATE registrations SET status='cancelled' WHERE reg_id = ?` |
| Report (JOIN) | `SELECT … FROM registrations r JOIN students s … JOIN events e …` |

Why prepared statements: SQL and data travel separately, so user input can
never inject SQL; the statement is compiled once and values are bound safely.

## 7. How to run (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\Practical_8`.
2. Start Apache + MySQL in XAMPP Control Panel.
3. phpMyAdmin → Import → choose `schema.sql` → Go (creates `studenthub` + data).
4. If MySQL has a password, edit it in `db.php`.
5. Open `http://localhost/Practical_8/index.php`.

## 8. Viva points
* PDO vs mysqli: PDO supports many DBs + named placeholders + exceptions.
* `prepare()` compiles SQL, `execute([...])` binds values safely.
* `ATTR_EMULATE_PREPARES=false` forces real server-side prepares.
* FK `ON DELETE CASCADE`: deleting a student/event auto-deletes its registrations.
* Error `23000` = integrity violation (duplicate email/registration).
