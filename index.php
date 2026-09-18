<?php
require 'config.php';

require_login();

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$status = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');


/*
|--------------------------------------------------------------------------
| Load Active Tickets
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM tickets
    WHERE archived_at IS NULL
";

$params = [];

if ($status !== '') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

if ($q !== '') {

    $sql .= "
        AND (
            student_name LIKE ?
            OR subject LIKE ?
            OR student_email LIKE ?
            OR category LIKE ?
            OR assigned_to LIKE ?
        )
    ";

    $like = '%' . $q . '%';

    array_push(
        $params,
        $like,
        $like,
        $like,
        $like,
        $like
    );
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$tickets = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Dashboard Metrics
|--------------------------------------------------------------------------
*/

$metrics = [

    'Total' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM tickets
            WHERE archived_at IS NULL
        ")
        ->fetchColumn(),

    'Open' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM tickets
            WHERE
                status = 'Open'
                AND archived_at IS NULL
        ")
        ->fetchColumn(),

    'In Progress' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM tickets
            WHERE
                status = 'In Progress'
                AND archived_at IS NULL
        ")
        ->fetchColumn(),

    'Resolved' => (int) $pdo
        ->query("
            SELECT COUNT(*)
            FROM tickets
            WHERE
                status = 'Resolved'
                AND archived_at IS NULL
        ")
        ->fetchColumn()

];

$f = flash();


/*
|--------------------------------------------------------------------------
| UI Helpers
|--------------------------------------------------------------------------
*/

function priorityClass(string $priority): string
{
    return match (strtolower($priority)) {
        'high' => 'priority-high',
        'medium' => 'priority-medium',
        default => 'priority-low'
    };
}


function statusClass(string $status): string
{
    return match (strtolower($status)) {
        'open' => 'status-open',
        'in progress' => 'status-progress',
        'resolved' => 'status-resolved',
        default => 'status-open'
    };
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
        CampusCare | Support Dashboard
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
                class="sidebar-link <?= $status === '' ? 'active' : '' ?>"
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
                class="sidebar-link <?= $status === 'Open' ? 'active' : '' ?>"
            >
                Open Tickets
            </a>

            <a
                href="index.php?status=In+Progress"
                class="sidebar-link <?= $status === 'In Progress' ? 'active' : '' ?>"
            >
                In Progress
            </a>

            <a
                href="index.php?status=Resolved"
                class="sidebar-link <?= $status === 'Resolved' ? 'active' : '' ?>"
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

                <h2>
                    Support Dashboard
                </h2>

                <p>
                    Track student support requests, monitor ticket progress,
                    and manage campus service issues from one workspace.
                </p>

            </div>


            <div class="header-actions">

                <a
                    href="ticket_form.php"
                    class="btn btn-primary"
                >
                    + New Request
                </a>

            </div>

        </div>


        <!-- FLASH MESSAGE -->

        <?php if ($f): ?>

            <div
                class="flash <?= $f[1] === 'error'
                    ? 'error'
                    : ''
                ?>"
            >
                <?= e($f[0]) ?>
            </div>

        <?php endif; ?>


        <!-- METRICS -->

        <section class="metrics-grid">


            <div class="metric-card">

                <div class="metric-top">

                    <span class="metric-label">
                        Total Tickets
                    </span>

                    <span class="metric-icon purple">
                        #
                    </span>

                </div>

                <div class="metric-value">
                    <?= $metrics['Total'] ?>
                </div>

            </div>


            <div class="metric-card">

                <div class="metric-top">

                    <span class="metric-label">
                        Open
                    </span>

                    <span class="metric-icon blue">
                        O
                    </span>

                </div>

                <div class="metric-value">
                    <?= $metrics['Open'] ?>
                </div>

            </div>


            <div class="metric-card">

                <div class="metric-top">

                    <span class="metric-label">
                        In Progress
                    </span>

                    <span class="metric-icon orange">
                        P
                    </span>

                </div>

                <div class="metric-value">
                    <?= $metrics['In Progress'] ?>
                </div>

            </div>


            <div class="metric-card">

                <div class="metric-top">

                    <span class="metric-label">
                        Resolved
                    </span>

                    <span class="metric-icon green">
                        R
                    </span>

                </div>

                <div class="metric-value">
                    <?= $metrics['Resolved'] ?>
                </div>

            </div>


        </section>


        <!-- FILTERS -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <h3>
                        Find Support Tickets
                    </h3>

                    <p>
                        Search by student, email, subject,
                        category, or assignee.
                    </p>

                </div>

            </div>


            <form
                method="GET"
                class="filter-grid"
            >

                <div class="form-group">

                    <label for="q">
                        Search
                    </label>

                    <input
                        type="search"
                        id="q"
                        name="q"
                        placeholder="Search tickets..."
                        value="<?= e($q) ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="status">
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                    >

                        <option value="">
                            All statuses
                        </option>

                        <?php foreach (
                            [
                                'Open',
                                'In Progress',
                                'Resolved'
                            ]
                            as $s
                        ): ?>

                            <option
                                value="<?= e($s) ?>"
                                <?= $status === $s
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($s) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="filter-actions">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Apply
                    </button>

                    <a
                        href="index.php"
                        class="btn btn-secondary"
                    >
                        Reset
                    </a>

                </div>

            </form>


        </section>


        <!-- TICKETS -->

        <section class="panel">


            <div class="panel-header">

                <div>

                    <h3>
                        Recent Support Tickets
                    </h3>

                    <p>
                        <?= count($tickets) ?>
                        ticket<?= count($tickets) === 1 ? '' : 's' ?>
                        shown.
                    </p>

                </div>


                <a
                    href="ticket_form.php"
                    class="btn btn-primary"
                >
                    + New Ticket
                </a>

            </div>


            <?php if (!$tickets): ?>

                <div class="empty-state">

                    <h4>
                        No active tickets found
                    </h4>

                    <p>
                        Try adjusting your search or create a new support request.
                    </p>

                </div>


            <?php else: ?>


                <div class="table-wrapper">

                    <table>


                        <thead>

                            <tr>

                                <th>
                                    Ticket
                                </th>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Request
                                </th>

                                <th>
                                    Priority
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Assigned To
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($tickets as $t): ?>

                                <tr>


                                    <td>

                                        <a
                                            href="ticket.php?id=<?= (int) $t['id'] ?>"
                                            class="ticket-id"
                                        >

                                            <?= e(
                                                ticketNumber(
                                                    (int) $t['id']
                                                )
                                            ) ?>

                                        </a>

                                    </td>


                                    <td>

                                        <span class="student-name">
                                            <?= e($t['student_name']) ?>
                                        </span>

                                        <span class="student-email">
                                            <?= e($t['student_email']) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <div class="ticket-subject">
                                            <?= e($t['subject']) ?>
                                        </div>

                                        <div class="ticket-category">
                                            <?= e($t['category']) ?>
                                        </div>

                                    </td>


                                    <td>

                                        <span
                                            class="chip <?= priorityClass(
                                                $t['priority']
                                            ) ?>"
                                        >
                                            <?= e($t['priority']) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="chip <?= statusClass(
                                                $t['status']
                                            ) ?>"
                                        >
                                            <?= e($t['status']) ?>
                                        </span>

                                    </td>


                                    <td>

                                        <?php if (!empty($t['assigned_to'])): ?>

                                            <span class="assignee-name">
                                                <?= e($t['assigned_to']) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="unassigned">
                                                Unassigned
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <div class="row-actions">


                                            <a
                                                href="ticket.php?id=<?= (int) $t['id'] ?>"
                                                class="btn btn-primary btn-small"
                                            >
                                                View Ticket
                                            </a>


                                            <a
                                                href="ticket_form.php?id=<?= (int) $t['id'] ?>"
                                                class="btn btn-secondary btn-small"
                                            >
                                                Edit
                                            </a>


                                            <a
                                                href="delete_ticket.php?id=<?= (int) $t['id'] ?>"
                                                class="btn btn-archive btn-small"
                                                onclick="return confirm(
                                                    'Archive this ticket? It will be removed from the active support queue but its history will be preserved.'
                                                );"
                                            >
                                                Archive
                                            </a>


                                        </div>

                                    </td>


                                </tr>

                            <?php endforeach; ?>


                        </tbody>


                    </table>

                </div>


            <?php endif; ?>


        </section>


    </main>


</div>


</body>

</html>