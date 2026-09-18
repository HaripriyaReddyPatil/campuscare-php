<?php
declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Database Setup
|--------------------------------------------------------------------------
*/

$dataDir = __DIR__ . '/data';

if (!is_dir($dataDir)) {
    if (!mkdir($dataDir, 0775, true) && !is_dir($dataDir)) {
        die('Could not create the data directory.');
    }
}

if (!is_writable($dataDir)) {
    die('The data directory is not writable.');
}

$dbFile = $dataDir . '/campuscare.sqlite';

$pdo = new PDO(
    'sqlite:' . $dbFile
);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);

$pdo->exec("PRAGMA foreign_keys = ON");


/*
|--------------------------------------------------------------------------
| Users Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'admin'
    )
");


/*
|--------------------------------------------------------------------------
| Tickets Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS tickets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        student_name TEXT NOT NULL,
        student_email TEXT NOT NULL,
        category TEXT NOT NULL,
        priority TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'Open',
        subject TEXT NOT NULL,
        description TEXT NOT NULL,
        assigned_to TEXT,
        resolution_notes TEXT,
        archived_at TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )
");


/*
|--------------------------------------------------------------------------
| Safe Migration For Existing Databases
|--------------------------------------------------------------------------
*/

$ticketColumns = $pdo
    ->query("PRAGMA table_info(tickets)")
    ->fetchAll();

$existingTicketColumns = array_column(
    $ticketColumns,
    'name'
);

if (!in_array('assigned_to', $existingTicketColumns, true)) {

    $pdo->exec("
        ALTER TABLE tickets
        ADD COLUMN assigned_to TEXT
    ");
}

if (!in_array('resolution_notes', $existingTicketColumns, true)) {

    $pdo->exec("
        ALTER TABLE tickets
        ADD COLUMN resolution_notes TEXT
    ");
}

if (!in_array('archived_at', $existingTicketColumns, true)) {

    $pdo->exec("
        ALTER TABLE tickets
        ADD COLUMN archived_at TEXT
    ");
}


/*
|--------------------------------------------------------------------------
| Ticket Activity Table
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS ticket_activity (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ticket_id INTEGER NOT NULL,
        actor TEXT NOT NULL,
        action TEXT NOT NULL,
        details TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (ticket_id)
            REFERENCES tickets(id)
            ON DELETE CASCADE
    )
");


/*
|--------------------------------------------------------------------------
| Seed Admin
|--------------------------------------------------------------------------
*/

$count = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM users
    ")
    ->fetchColumn();

if ($count === 0) {

    $adminEmail =
        getenv('CAMPUSCARE_ADMIN_EMAIL')
        ?: 'admin@campuscare.local';

    $adminPassword =
        getenv('CAMPUSCARE_ADMIN_PASSWORD')
        ?: 'Admin123!';

    $stmt = $pdo->prepare("
        INSERT INTO users (
            email,
            password_hash,
            role
        )
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $adminEmail,
        password_hash(
            $adminPassword,
            PASSWORD_DEFAULT
        ),
        'admin'
    ]);
}


/*
|--------------------------------------------------------------------------
| Output Escaping
|--------------------------------------------------------------------------
*/

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| Redirect Helper
|--------------------------------------------------------------------------
*/

function redirect(string $path): never
{
    header("Location: $path");
    exit;
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('login.php');
    }
}


/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

function flash(
    ?string $message = null,
    string $type = 'ok'
): ?array {

    if ($message !== null) {

        $_SESSION['flash'] = [
            $message,
            $type
        ];

        return null;
    }

    if (!empty($_SESSION['flash'])) {

        $f = $_SESSION['flash'];

        unset($_SESSION['flash']);

        return $f;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Ticket Number Helper
|--------------------------------------------------------------------------
*/

function ticketNumber(int $id): string
{
    return 'CC-' . str_pad(
        (string) $id,
        4,
        '0',
        STR_PAD_LEFT
    );
}


/*
|--------------------------------------------------------------------------
| Ticket Activity Logging
|--------------------------------------------------------------------------
*/

function logTicketActivity(
    PDO $pdo,
    int $ticketId,
    string $action,
    string $details,
    ?string $actor = null
): void {

    if ($actor === null) {

        $actor =
            $_SESSION['user']['email']
            ?? 'Support Staff';
    }

    $stmt = $pdo->prepare("
        INSERT INTO ticket_activity (
            ticket_id,
            actor,
            action,
            details
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $ticketId,
        $actor,
        $action,
        $details
    ]);
}
?>