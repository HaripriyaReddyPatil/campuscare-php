<?php
require 'config.php';

require_login();

/*
|--------------------------------------------------------------------------
| Ticket ID
|--------------------------------------------------------------------------
*/

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
| Load Activity Timeline
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM ticket_activity
    WHERE ticket_id = ?
    ORDER BY id DESC
");

$stmt->execute([$id]);

$activity = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| UI Helpers
|--------------------------------------------------------------------------
*/

function ticketPriorityClass(string $priority): string
{
    return match (strtolower($priority)) {
        'high' => 'priority-high',
        'medium' => 'priority-medium',
        default => 'priority-low'
    };
}

function ticketStatusClass(string $status): string
{
    return match (strtolower($status)) {
        'resolved' => 'status-resolved',
        'in progress' => 'status-progress',
        default => 'status-open'
    };
}

function activityClass(string $action): string
{
    $action = strtolower($action);

    if (str_contains($action, 'resolved')) {
        return 'timeline-success';
    }

    if (str_contains($action, 'status')) {
        return 'timeline-warning';
    }

    if (str_contains($action, 'assignment')) {
        return 'timeline-purple';
    }

    if (str_contains($action, 'priority')) {
        return 'timeline-danger';
    }

    return 'timeline-blue';
}

function formatTicketDate(string $date): string
{
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    return date(
        'M j, Y g:i A',
        $timestamp
    );
}
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e(ticketNumber($id)) ?>
        | CampusCare
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


<div class="app-shell">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <a
            href="index.php"
            class="sidebar-brand"
        >

            <div class="sidebar-logo">
                CC
            </div>

            <div>

                <h1>
                    CampusCare
                </h1>

                <p>
                    Student Support Desk
                </p>

            </div>

        </a>


        <nav class="sidebar-nav">

            <a
                href="index.php"
                class="sidebar-link"
            >
                Dashboard
            </a>

            <a
                href="ticket_form.php"
                class="sidebar-link"
            >
                New Request
            </a>

            <a
                href="index.php?status=Open"
                class="sidebar-link"
            >
                Open Tickets
            </a>

            <a
                href="index.php?status=In+Progress"
                class="sidebar-link"
            >
                In Progress
            </a>

            <a
                href="index.php?status=Resolved"
                class="sidebar-link"
            >
                Resolved
            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="sidebar-user">

                <strong>
                    Support Staff
                </strong>

                <span>
                    CampusCare Portal
                </span>

                <a
                    href="logout.php"
                    class="logout-link"
                >
                    Logout
                </a>

            </div>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main-content">


        <!-- HEADER -->

        <div class="page-header">

            <div>

                <div class="ticket-page-number">
                    <?= e(ticketNumber($id)) ?>
                </div>

                <h2>
                    <?= e($ticket['subject']) ?>
                </h2>

                <p>
                    Created
                    <?= e(
                        formatTicketDate(
                            $ticket['created_at']
                        )
                    ) ?>
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="ticket_form.php?id=<?= $id ?>"
                    class="btn btn-primary"
                >
                    Edit Ticket
                </a>

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Back
                </a>

            </div>

        </div>


        <!-- STATUS STRIP -->

        <section class="ticket-status-strip">

            <div>

                <span class="ticket-meta-label">
                    Status
                </span>

                <span
                    class="chip <?= ticketStatusClass(
                        $ticket['status']
                    ) ?>"
                >
                    <?= e($ticket['status']) ?>
                </span>

            </div>


            <div>

                <span class="ticket-meta-label">
                    Priority
                </span>

                <span
                    class="chip <?= ticketPriorityClass(
                        $ticket['priority']
                    ) ?>"
                >
                    <?= e($ticket['priority']) ?>
                </span>

            </div>


            <div>

                <span class="ticket-meta-label">
                    Category
                </span>

                <strong>
                    <?= e($ticket['category']) ?>
                </strong>

            </div>


            <div>

                <span class="ticket-meta-label">
                    Assigned To
                </span>

                <strong>
                    <?= !empty($ticket['assigned_to'])
                        ? e($ticket['assigned_to'])
                        : 'Unassigned'
                    ?>
                </strong>

            </div>

        </section>


        <div class="ticket-detail-grid">


            <!-- LEFT COLUMN -->

            <div>


                <!-- REQUESTER -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Requester
                            </h3>

                            <p>
                                Student associated with this ticket.
                            </p>

                        </div>

                    </div>


                    <div class="requester-card">

                        <div class="requester-avatar">

                            <?= e(
                                strtoupper(
                                    substr(
                                        $ticket['student_name'],
                                        0,
                                        1
                                    )
                                )
                            ) ?>

                        </div>


                        <div>

                            <strong>
                                <?= e($ticket['student_name']) ?>
                            </strong>

                            <span>
                                <?= e($ticket['student_email']) ?>
                            </span>

                        </div>

                    </div>

                </section>


                <!-- ISSUE -->

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Issue Description
                            </h3>

                            <p>
                                Information supplied with the request.
                            </p>

                        </div>

                    </div>


                    <div class="ticket-description">

                        <?= nl2br(
                            e($ticket['description'])
                        ) ?>

                    </div>

                </section>


                <!-- RESOLUTION -->

                <?php if (
                    !empty($ticket['resolution_notes'])
                ): ?>

                    <section class="panel resolution-panel">

                        <div class="panel-header">

                            <div>

                                <h3>
                                    Resolution
                                </h3>

                                <p>
                                    Final support notes and outcome.
                                </p>

                            </div>

                        </div>


                        <div class="resolution-text">

                            <?= nl2br(
                                e(
                                    $ticket['resolution_notes']
                                )
                            ) ?>

                        </div>

                    </section>

                <?php endif; ?>


            </div>


            <!-- RIGHT COLUMN -->

            <div>

                <section class="panel">

                    <div class="panel-header">

                        <div>

                            <h3>
                                Activity Timeline
                            </h3>

                            <p>
                                History of this support request.
                            </p>

                        </div>

                    </div>


                    <?php if (!$activity): ?>

                        <div class="empty-state">

                            <h4>
                                No activity yet
                            </h4>

                            <p>
                                Ticket changes will appear here.
                            </p>

                        </div>

                    <?php else: ?>

                        <div class="ticket-timeline">

                            <?php foreach (
                                $activity
                                as $item
                            ): ?>

                                <div class="timeline-item">

                                    <div
                                        class="timeline-marker <?= activityClass(
                                            $item['action']
                                        ) ?>"
                                    ></div>


                                    <div class="timeline-content">

                                        <div class="timeline-heading">

                                            <strong>
                                                <?= e(
                                                    $item['action']
                                                ) ?>
                                            </strong>

                                            <span>
                                                <?= e(
                                                    formatTicketDate(
                                                        $item['created_at']
                                                    )
                                                ) ?>
                                            </span>

                                        </div>


                                        <p>
                                            <?= e(
                                                $item['details']
                                            ) ?>
                                        </p>


                                        <small>
                                            <?= e(
                                                $item['actor']
                                            ) ?>
                                        </small>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </section>

            </div>


        </div>


    </main>


</div>


</body>

</html>