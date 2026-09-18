<?php
require 'config.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    flash('Ticket not found.', 'error');
    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Load Ticket
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM tickets
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$ticket = $stmt->fetch();

if (!$ticket) {
    flash('Ticket not found.', 'error');
    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Prevent Duplicate Archive
|--------------------------------------------------------------------------
*/

if (!empty($ticket['archived_at'])) {
    flash(
        ticketNumber($id) . ' is already archived.',
        'error'
    );

    redirect('index.php');
}

/*
|--------------------------------------------------------------------------
| Archive Ticket
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE tickets
        SET
            archived_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    logTicketActivity(
        $pdo,
        $id,
        'Ticket Archived',
        ticketNumber($id) . ' was archived and removed from the active support queue.'
    );

    $pdo->commit();

    flash(
        ticketNumber($id) . ' archived successfully.'
    );

} catch (Throwable $exception) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash(
        'The ticket could not be archived.',
        'error'
    );
}

redirect('index.php');