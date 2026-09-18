<?php
require 'config.php';

require_login();

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| Ticket Setup
|--------------------------------------------------------------------------
*/

$id = (int) (
    $_GET['id']
    ?? $_POST['ticket_id']
    ?? 0
);

$isEditing = $id > 0;

$ticket = [
    'student_name' => '',
    'student_email' => '',
    'category' => 'Academic',
    'priority' => 'Medium',
    'status' => 'Open',
    'subject' => '',
    'description' => '',
    'assigned_to' => '',
    'resolution_notes' => ''
];

$originalTicket = null;


/*
|--------------------------------------------------------------------------
| Load Existing Ticket
|--------------------------------------------------------------------------
*/

if ($isEditing) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM tickets
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $ticket = $stmt->fetch();

    if (!$ticket) {

        flash(
            'Support ticket could not be found.',
            'error'
        );

        redirect('index.php');
    }

    $originalTicket = $ticket;
}


/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/

$categories = [
    'Academic',
    'Technical',
    'Billing',
    'Registration',
    'Other'
];

$priorities = [
    'Low',
    'Medium',
    'High'
];

$statuses = [
    'Open',
    'In Progress',
    'Resolved'
];


/*
|--------------------------------------------------------------------------
| Form Processing
|--------------------------------------------------------------------------
*/

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Verify CSRF
    |--------------------------------------------------------------------------
    */

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {
        http_response_code(403);
        exit('Invalid request token.');
    }


    /*
    |--------------------------------------------------------------------------
    | Read Form Values
    |--------------------------------------------------------------------------
    */

    $ticket['student_name'] =
        trim($_POST['student_name'] ?? '');

    $ticket['student_email'] =
        trim($_POST['student_email'] ?? '');

    $ticket['category'] =
        trim($_POST['category'] ?? 'Academic');

    $ticket['priority'] =
        trim($_POST['priority'] ?? 'Medium');

    $ticket['subject'] =
        trim($_POST['subject'] ?? '');

    $ticket['description'] =
        trim($_POST['description'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Staff-Only Workflow Fields
    |--------------------------------------------------------------------------
    */

    if ($isEditing) {

        $ticket['status'] =
            trim($_POST['status'] ?? 'Open');

        $ticket['assigned_to'] =
            trim($_POST['assigned_to'] ?? '');

        $ticket['resolution_notes'] =
            trim($_POST['resolution_notes'] ?? '');

    } else {

        /*
        | New tickets always begin Open.
        */

        $ticket['status'] = 'Open';
        $ticket['assigned_to'] = '';
        $ticket['resolution_notes'] = '';
    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($ticket['student_name'] === '') {
        $errors[] = 'Student name is required.';
    }

    if (
        !filter_var(
            $ticket['student_email'],
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $errors[] = 'A valid student email address is required.';
    }

    if ($ticket['subject'] === '') {
        $errors[] = 'Subject is required.';
    }

    if ($ticket['description'] === '') {
        $errors[] = 'Description is required.';
    }

    if (
        !in_array(
            $ticket['category'],
            $categories,
            true
        )
    ) {
        $errors[] = 'Please select a valid category.';
    }

    if (
        !in_array(
            $ticket['priority'],
            $priorities,
            true
        )
    ) {
        $errors[] = 'Please select a valid priority.';
    }

    if (
        $isEditing &&
        !in_array(
            $ticket['status'],
            $statuses,
            true
        )
    ) {
        $errors[] = 'Please select a valid status.';
    }

    if (
        $isEditing &&
        $ticket['status'] === 'Resolved' &&
        $ticket['resolution_notes'] === ''
    ) {
        $errors[] =
            'Resolution notes are required before resolving a ticket.';
    }

    if (strlen($ticket['subject']) > 150) {
        $errors[] = 'Subject must be 150 characters or fewer.';
    }

    if (strlen($ticket['description']) > 2000) {
        $errors[] = 'Description must be 2000 characters or fewer.';
    }

    if (strlen($ticket['assigned_to']) > 100) {
        $errors[] = 'Assigned To must be 100 characters or fewer.';
    }

    if (strlen($ticket['resolution_notes']) > 2000) {
        $errors[] = 'Resolution notes must be 2000 characters or fewer.';
    }


    /*
    |--------------------------------------------------------------------------
    | Save Ticket
    |--------------------------------------------------------------------------
    */

    if (!$errors) {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Update Existing Ticket
            |--------------------------------------------------------------------------
            */

            if ($isEditing) {

                $stmt = $pdo->prepare("
                    UPDATE tickets
                    SET
                        student_name = ?,
                        student_email = ?,
                        category = ?,
                        priority = ?,
                        status = ?,
                        subject = ?,
                        description = ?,
                        assigned_to = ?,
                        resolution_notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");

                $stmt->execute([
                    $ticket['student_name'],
                    $ticket['student_email'],
                    $ticket['category'],
                    $ticket['priority'],
                    $ticket['status'],
                    $ticket['subject'],
                    $ticket['description'],
                    $ticket['assigned_to'],
                    $ticket['resolution_notes'],
                    $id
                ]);


                /*
                |--------------------------------------------------------------------------
                | Record Important Changes
                |--------------------------------------------------------------------------
                */

                $changes = [];

                if (
                    $originalTicket['status']
                    !== $ticket['status']
                ) {

                    $changes[] =
                        "Status changed from {$originalTicket['status']} to {$ticket['status']}";

                    logTicketActivity(
                        $pdo,
                        $id,
                        'Status Changed',
                        "Status changed from {$originalTicket['status']} to {$ticket['status']}."
                    );
                }


                if (
                    $originalTicket['priority']
                    !== $ticket['priority']
                ) {

                    $changes[] =
                        "Priority changed from {$originalTicket['priority']} to {$ticket['priority']}";

                    logTicketActivity(
                        $pdo,
                        $id,
                        'Priority Changed',
                        "Priority changed from {$originalTicket['priority']} to {$ticket['priority']}."
                    );
                }


                $oldAssignee =
                    trim(
                        (string) (
                            $originalTicket['assigned_to']
                            ?? ''
                        )
                    );

                $newAssignee =
                    trim(
                        (string) $ticket['assigned_to']
                    );

                if ($oldAssignee !== $newAssignee) {

                    $oldDisplay =
                        $oldAssignee !== ''
                            ? $oldAssignee
                            : 'Unassigned';

                    $newDisplay =
                        $newAssignee !== ''
                            ? $newAssignee
                            : 'Unassigned';

                    $changes[] =
                        "Assignment changed from {$oldDisplay} to {$newDisplay}";

                    logTicketActivity(
                        $pdo,
                        $id,
                        'Assignment Changed',
                        "Ticket assignment changed from {$oldDisplay} to {$newDisplay}."
                    );
                }


                $oldResolution =
                    trim(
                        (string) (
                            $originalTicket['resolution_notes']
                            ?? ''
                        )
                    );

                $newResolution =
                    trim(
                        (string) $ticket['resolution_notes']
                    );

                if ($oldResolution !== $newResolution) {

                    logTicketActivity(
                        $pdo,
                        $id,
                        'Resolution Updated',
                        'Resolution notes were updated.'
                    );

                    $changes[] =
                        'Resolution notes updated';
                }


                /*
                |--------------------------------------------------------------------------
                | General Ticket Information Changes
                |--------------------------------------------------------------------------
                */

                $generalFieldsChanged =
                    $originalTicket['student_name']
                        !== $ticket['student_name']
                    ||
                    $originalTicket['student_email']
                        !== $ticket['student_email']
                    ||
                    $originalTicket['category']
                        !== $ticket['category']
                    ||
                    $originalTicket['subject']
                        !== $ticket['subject']
                    ||
                    $originalTicket['description']
                        !== $ticket['description'];

                if ($generalFieldsChanged) {

                    logTicketActivity(
                        $pdo,
                        $id,
                        'Ticket Updated',
                        'Requester or issue information was updated.'
                    );

                    $changes[] =
                        'Ticket information updated';
                }


                /*
                |--------------------------------------------------------------------------
                | Fallback Activity
                |--------------------------------------------------------------------------
                */

                if (!$changes) {

                    logTicketActivity(
                        $pdo,
                        $id,
                        'Ticket Saved',
                        'Ticket was saved without workflow changes.'
                    );
                }


                $pdo->commit();

                flash(
                    ticketNumber($id)
                    . ' updated successfully.'
                );


            /*
            |--------------------------------------------------------------------------
            | Create New Ticket
            |--------------------------------------------------------------------------
            */

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO tickets (
                        student_name,
                        student_email,
                        category,
                        priority,
                        status,
                        subject,
                        description,
                        assigned_to,
                        resolution_notes
                    )
                    VALUES (?, ?, ?, ?, 'Open', ?, ?, NULL, NULL)
                ");

                $stmt->execute([
                    $ticket['student_name'],
                    $ticket['student_email'],
                    $ticket['category'],
                    $ticket['priority'],
                    $ticket['subject'],
                    $ticket['description']
                ]);

                $newTicketId =
                    (int) $pdo->lastInsertId();


                /*
                |--------------------------------------------------------------------------
                | Initial Activity
                |--------------------------------------------------------------------------
                */

                logTicketActivity(
                    $pdo,
                    $newTicketId,
                    'Ticket Created',
                    "Support request created by {$ticket['student_name']} with {$ticket['priority']} priority."
                );


                $pdo->commit();

                flash(
                    ticketNumber($newTicketId)
                    . ' created successfully.'
                );
            }


            redirect('index.php');

        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'The ticket could not be saved. Please try again.';
        }
    }
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
        <?= $isEditing
            ? 'Edit Ticket'
            : 'New Support Request'
        ?>
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
                class="sidebar-link <?= !$isEditing ? 'active' : '' ?>"
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


        <!-- PAGE HEADER -->

        <div class="page-header">

            <div>

                <h2>

                    <?= $isEditing
                        ? 'Manage Support Ticket'
                        : 'Create Support Request'
                    ?>

                </h2>

                <p>

                    <?= $isEditing
                        ? 'Review the request, assign ownership, update progress, and document the resolution.'
                        : 'Tell the support team what happened and provide the information needed to investigate.'
                    ?>

                </p>

            </div>


            <div class="header-actions">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Back to Dashboard
                </a>

            </div>

        </div>


        <!-- ERRORS -->

        <?php if ($errors): ?>

            <div class="flash error">

                <?= e(
                    implode(
                        ' ',
                        $errors
                    )
                ) ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >


            <?php if ($isEditing): ?>

                <input
                    type="hidden"
                    name="ticket_id"
                    value="<?= $id ?>"
                >

            <?php endif; ?>


            <!-- TICKET BANNER -->

            <?php if ($isEditing): ?>

                <section class="ticket-banner">

                    <div>

                        <span class="ticket-banner-label">
                            Support Ticket
                        </span>

                        <strong>
                            <?= e(
                                ticketNumber($id)
                            ) ?>
                        </strong>

                    </div>


                    <span
                        class="chip <?= match (
                            strtolower($ticket['status'])
                        ) {
                            'resolved' =>
                                'status-resolved',

                            'in progress' =>
                                'status-progress',

                            default =>
                                'status-open'
                        } ?>"
                    >

                        <?= e(
                            $ticket['status']
                        ) ?>

                    </span>

                </section>

            <?php endif; ?>


            <!-- REQUESTER -->

            <section class="panel form-section">


                <div class="section-heading">

                    <div class="section-number">
                        01
                    </div>

                    <div>

                        <h3>
                            Requester Information
                        </h3>

                        <p>
                            Identify the student who needs assistance.
                        </p>

                    </div>

                </div>


                <div class="form-grid form-grid-2">


                    <div class="form-group">

                        <label for="student_name">
                            Student Name
                        </label>

                        <input
                            type="text"
                            id="student_name"
                            name="student_name"
                            maxlength="100"
                            placeholder="Example: Maya Patel"
                            value="<?= e(
                                $ticket['student_name']
                            ) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="student_email">
                            Student Email
                        </label>

                        <input
                            type="email"
                            id="student_email"
                            name="student_email"
                            maxlength="150"
                            placeholder="student@university.edu"
                            value="<?= e(
                                $ticket['student_email']
                            ) ?>"
                            required
                        >

                    </div>


                </div>


            </section>


            <!-- CLASSIFICATION -->

            <section class="panel form-section">


                <div class="section-heading">

                    <div class="section-number">
                        02
                    </div>

                    <div>

                        <h3>
                            Issue Classification
                        </h3>

                        <p>
                            Categorize and prioritize the support request.
                        </p>

                    </div>

                </div>


                <div
                    class="form-grid <?= $isEditing
                        ? 'form-grid-3'
                        : 'form-grid-2'
                    ?>"
                >


                    <!-- CATEGORY -->

                    <div class="form-group">

                        <label for="category">
                            Category
                        </label>

                        <select
                            id="category"
                            name="category"
                            required
                        >

                            <?php foreach (
                                $categories
                                as $value
                            ): ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= $ticket['category'] === $value
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e($value) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- PRIORITY -->

                    <div class="form-group">

                        <label for="priority">
                            Priority
                        </label>

                        <select
                            id="priority"
                            name="priority"
                            required
                        >

                            <?php foreach (
                                $priorities
                                as $value
                            ): ?>

                                <option
                                    value="<?= e($value) ?>"
                                    <?= $ticket['priority'] === $value
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e($value) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="field-help">
                            High priority is intended for issues blocking critical access or required work.
                        </small>

                    </div>


                    <!-- STATUS - EDIT ONLY -->

                    <?php if ($isEditing): ?>

                        <div class="form-group">

                            <label for="status">
                                Status
                            </label>

                            <select
                                id="status"
                                name="status"
                                required
                            >

                                <?php foreach (
                                    $statuses
                                    as $value
                                ): ?>

                                    <option
                                        value="<?= e($value) ?>"
                                        <?= $ticket['status'] === $value
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= e($value) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    <?php endif; ?>


                </div>


                <?php if (!$isEditing): ?>

                    <div class="new-ticket-note">

                        <strong>
                            New tickets automatically start as Open.
                        </strong>

                        <span>
                            Support staff will update the status after reviewing the request.
                        </span>

                    </div>

                <?php endif; ?>


            </section>


            <!-- REQUEST DETAILS -->

            <section class="panel form-section">


                <div class="section-heading">

                    <div class="section-number">
                        03
                    </div>

                    <div>

                        <h3>
                            Request Details
                        </h3>

                        <p>
                            Explain the issue and provide useful context.
                        </p>

                    </div>

                </div>


                <div class="form-group">

                    <label for="subject">
                        Subject
                    </label>

                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        maxlength="150"
                        placeholder="Example: Unable to access student portal"
                        value="<?= e(
                            $ticket['subject']
                        ) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        maxlength="2000"
                        placeholder="Describe the issue, what happened, and any troubleshooting already attempted."
                        required
                    ><?= e(
                        $ticket['description']
                    ) ?></textarea>

                    <small class="field-help">
                        Include relevant error messages, affected services, or troubleshooting already attempted.
                    </small>

                </div>


            </section>


            <!-- SUPPORT WORKFLOW - EDIT ONLY -->

            <?php if ($isEditing): ?>

                <section class="panel form-section">


                    <div class="section-heading">

                        <div class="section-number">
                            04
                        </div>

                        <div>

                            <h3>
                                Support Workflow
                            </h3>

                            <p>
                                Assign ownership and document how the issue was handled.
                            </p>

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="assigned_to">
                            Assigned To
                        </label>

                        <input
                            type="text"
                            id="assigned_to"
                            name="assigned_to"
                            maxlength="100"
                            placeholder="Example: IT Support Team"
                            value="<?= e(
                                $ticket['assigned_to']
                                ?? ''
                            ) ?>"
                        >

                        <small class="field-help">
                            Leave blank if the ticket has not been assigned yet.
                        </small>

                    </div>


                    <div class="form-group">

                        <label for="resolution_notes">
                            Resolution Notes
                        </label>

                        <textarea
                            id="resolution_notes"
                            name="resolution_notes"
                            maxlength="2000"
                            placeholder="Document the fix, workaround, or final outcome."
                        ><?= e(
                            $ticket['resolution_notes']
                            ?? ''
                        ) ?></textarea>

                        <small class="field-help">
                            Resolution notes are required when the ticket is marked Resolved.
                        </small>

                    </div>


                </section>

            <?php endif; ?>


            <!-- ACTIONS -->

            <div class="ticket-form-actions">

                <a
                    href="index.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <?= $isEditing
                        ? 'Save Ticket Changes'
                        : 'Submit Support Ticket'
                    ?>

                </button>

            </div>


        </form>


    </main>


</div>


</body>

</html>