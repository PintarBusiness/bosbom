<?php
session_start();

// --- NASTAVI GESLO TUKAJ ---
define('ADMIN_PASSWORD', 'bosbom2025');
define('EVENTS_FILE', __DIR__ . '/events.json');

// Login / logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin'] = true;
    } else {
        $loginError = 'Napačno geslo.';
    }
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin_events.php');
    exit;
}

// Load events
function loadEvents() {
    if (!file_exists(EVENTS_FILE)) return [];
    return json_decode(file_get_contents(EVENTS_FILE), true) ?? [];
}
function saveEvents($events) {
    file_put_contents(EVENTS_FILE, json_encode(array_values($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Handle actions (only if logged in)
$message = '';
if (!empty($_SESSION['admin'])) {
    // Add event
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        $datum = trim($_POST['datum'] ?? '');
        $opis  = trim($_POST['opis'] ?? '');
        if ($datum && $opis && preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) {
            $events = loadEvents();
            $events[] = ['datum' => $datum, 'opis' => htmlspecialchars($opis)];
            // Sort by date ascending
            usort($events, fn($a, $b) => strcmp($a['datum'], $b['datum']));
            saveEvents($events);
            $message = 'Event dodan!';
        }
    }
    // Delete event
    if (isset($_GET['delete'])) {
        $idx = (int)$_GET['delete'];
        $events = loadEvents();
        array_splice($events, $idx, 1);
        saveEvents($events);
        header('Location: admin_events.php?ok=1');
        exit;
    }
    // Move up/down
    if (isset($_GET['move'])) {
        [$idx, $dir] = explode(',', $_GET['move']);
        $idx = (int)$idx; $dir = (int)$dir;
        $events = loadEvents();
        $swap = $idx + $dir;
        if ($swap >= 0 && $swap < count($events)) {
            [$events[$idx], $events[$swap]] = [$events[$swap], $events[$idx]];
            saveEvents($events);
        }
        header('Location: admin_events.php');
        exit;
    }
}

$events = loadEvents();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Boš? Bom. Eventi</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --red: #C8102E; --black: #0d0d0d; --off-white: #F4EEE4;
    --charcoal: #1a1a1a; --grey: #888;
    --font-mono: 'Space Mono', monospace;
    --font-body: 'DM Sans', sans-serif;
  }
  body { background: var(--black); color: var(--off-white); font-family: var(--font-body); min-height: 100vh; }

  .topbar {
    background: var(--charcoal); border-bottom: 1px solid rgba(255,255,255,0.07);
    padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center;
  }
  .topbar-title { font-family: var(--font-mono); font-size: 0.8rem; letter-spacing: 0.15em; text-transform: uppercase; color: var(--red); }
  .topbar a { font-family: var(--font-mono); font-size: 0.65rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--grey); text-decoration: none; }
  .topbar a:hover { color: var(--off-white); }

  .container { max-width: 720px; margin: 3rem auto; padding: 0 1.5rem; }

  /* LOGIN */
  .login-box {
    background: var(--charcoal); border: 1px solid rgba(255,255,255,0.08);
    padding: 2.5rem; max-width: 380px; margin: 5rem auto;
  }
  .login-box h1 { font-family: var(--font-mono); font-size: 1rem; letter-spacing: 0.1em; margin-bottom: 1.5rem; color: var(--off-white); }
  .login-error { font-family: var(--font-mono); font-size: 0.7rem; color: var(--red); margin-bottom: 1rem; }

  /* FORM ELEMENTS */
  label { font-family: var(--font-mono); font-size: 0.62rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--grey); display: block; margin-bottom: 0.4rem; }
  input[type=text], input[type=password], input[type=date] {
    width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
    color: var(--off-white); padding: 0.75rem 1rem; font-family: var(--font-body);
    font-size: 0.9rem; outline: none; transition: border-color 0.2s; border-radius: 0;
    appearance: none; -webkit-appearance: none;
  }
  input:focus { border-color: var(--red); }
  .form-group { margin-bottom: 1rem; }
  .btn {
    background: var(--red); color: #fff; border: none; cursor: pointer;
    font-family: var(--font-mono); font-size: 0.72rem; letter-spacing: 0.12em;
    text-transform: uppercase; padding: 0.8rem 1.8rem; transition: background 0.2s;
  }
  .btn:hover { background: #a80d22; }
  .btn-sm {
    background: transparent; border: 1px solid rgba(255,255,255,0.15); color: var(--grey);
    font-family: var(--font-mono); font-size: 0.6rem; letter-spacing: 0.1em;
    text-transform: uppercase; padding: 0.35rem 0.7rem; cursor: pointer;
    text-decoration: none; display: inline-block; transition: border-color 0.2s, color 0.2s;
  }
  .btn-sm:hover { border-color: var(--off-white); color: var(--off-white); }
  .btn-del { border-color: rgba(200,16,46,0.4); color: rgba(200,16,46,0.7); }
  .btn-del:hover { border-color: var(--red); color: var(--red); background: rgba(200,16,46,0.08); }

  /* ADD FORM */
  .add-section { background: var(--charcoal); border: 1px solid rgba(255,255,255,0.08); padding: 2rem; margin-bottom: 2.5rem; }
  .add-section h2 { font-family: var(--font-mono); font-size: 0.75rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--red); margin-bottom: 1.5rem; }
  .add-row { display: grid; grid-template-columns: 160px 1fr auto; gap: 0.75rem; align-items: end; }
  @media(max-width: 540px) { .add-row { grid-template-columns: 1fr; } }

  /* EVENTS LIST */
  .events-section h2 { font-family: var(--font-mono); font-size: 0.75rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--grey); margin-bottom: 1rem; }
  .event-row {
    display: flex; align-items: center; gap: 1rem;
    background: var(--charcoal); border: 1px solid rgba(255,255,255,0.06);
    padding: 1rem 1.2rem; margin-bottom: 0.5rem;
  }
  .event-datum {
    font-family: var(--font-mono); font-size: 0.72rem; letter-spacing: 0.1em;
    background: rgba(200,16,46,0.15); color: var(--red); padding: 0.3rem 0.6rem;
    white-space: nowrap; min-width: 70px; text-align: center;
  }
  .event-opis { flex: 1; font-size: 0.9rem; color: var(--off-white); }
  .event-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }
  .move-btns { display: flex; flex-direction: column; gap: 2px; }
  .move-btns a { font-size: 0.55rem; padding: 0.2rem 0.4rem; line-height: 1; }

  .msg-ok { font-family: var(--font-mono); font-size: 0.7rem; color: #4caf50; margin-bottom: 1.2rem; }
  .empty { font-family: var(--font-mono); font-size: 0.72rem; color: var(--grey); padding: 1.5rem; text-align: center; border: 1px dashed rgba(255,255,255,0.1); }

  .hint { font-family: var(--font-mono); font-size: 0.6rem; color: var(--grey); margin-top: 0.5rem; letter-spacing: 0.08em; }
</style>
</head>
<body>

<div class="topbar">
  <span class="topbar-title">Boš? Bom. // Admin — Eventi</span>
  <?php if (!empty($_SESSION['admin'])): ?>
    <a href="?logout=1">Odjava</a>
  <?php else: ?>
    <a href="../index.html">← Nazaj na stran</a>
  <?php endif; ?>
</div>

<?php if (empty($_SESSION['admin'])): ?>
<!-- LOGIN -->
<div class="container">
  <div class="login-box">
    <h1>// Prijava</h1>
    <?php if (!empty($loginError)): ?>
      <div class="login-error"><?= $loginError ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Geslo</label>
        <input type="password" name="password" autofocus required>
      </div>
      <button class="btn" type="submit">Prijava →</button>
    </form>
  </div>
</div>

<?php else: ?>
<!-- ADMIN PANEL -->
<div class="container">

  <?php if ($message): ?><div class="msg-ok">✓ <?= $message ?></div><?php endif; ?>
  <?php if (isset($_GET['ok'])): ?><div class="msg-ok">✓ Event izbrisan.</div><?php endif; ?>

  <!-- ADD EVENT -->
  <div class="add-section">
    <h2>// Dodaj event</h2>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="add-row">
        <div class="form-group" style="margin:0">
          <label>Datum</label>
          <input type="date" name="datum" required>
        </div>
        <div class="form-group" style="margin:0">
          <label>Opis / lokacija</label>
          <input type="text" name="opis" placeholder="Street Food Festival — Ljubljana" required>
        </div>
        <button class="btn" type="submit" style="white-space:nowrap">Dodaj +</button>
      </div>
      <p class="hint">Dogodki se samodejno sortirajo po datumu in pretekli se skrijejo na strani.</p>
    </form>
  </div>

  <!-- EVENTS LIST -->
  <div class="events-section">
    <h2>// Trenutni eventi (<?= count($events) ?>)</h2>
    <?php if (empty($events)): ?>
      <div class="empty">Ni eventov. Dodaj prvega zgoraj.</div>
    <?php else: ?>
      <?php foreach ($events as $i => $ev): ?>
        <div class="event-row">
          <div class="move-btns">
            <?php if ($i > 0): ?>
              <a class="btn-sm" href="?move=<?= $i ?>,<?= -1 ?>">▲</a>
            <?php endif; ?>
            <?php if ($i < count($events) - 1): ?>
              <a class="btn-sm" href="?move=<?= $i ?>,<?= 1 ?>">▼</a>
            <?php endif; ?>
          </div>
          <span class="event-datum"><?= date('d.m.Y', strtotime($ev['datum'])) ?></span>
          <span class="event-opis"><?= htmlspecialchars($ev['opis']) ?></span>
          <div class="event-actions">
            <a class="btn-sm btn-del" href="?delete=<?= $i ?>" onclick="return confirm('Izbriši ta event?')">Briši</a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
<?php endif; ?>

</body>
</html>
