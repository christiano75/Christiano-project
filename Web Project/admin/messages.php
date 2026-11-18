<?php
/**
 * Admin — customer messages with read/unread toggle and delete.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

if (is_post()) {
    verify_csrf();
    $id = (int) ($_POST['message_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($id > 0 && $action === 'toggle') {
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 - is_read WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('success', 'Message marked as ' . 'updated.');
    } elseif ($id > 0 && $action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('info', 'Message deleted.');
    }
    redirect('messages.php');
}

$messages = $conn->query("SELECT * FROM messages ORDER BY is_read ASC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$unread = count(array_filter($messages, static fn (array $message): bool => !$message['is_read']));

$pageTitle = 'Admin — Messages';
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'messages';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">Inbox</p>
            <h1>Messages</h1>
            <p class="text-muted-warm mb-0"><?= $unread ?> unread of <?= count($messages) ?> total</p>
        </div>
        <a class="btn btn-outline-warm" href="../contact.php" target="_blank" rel="noopener"><?= icon('eye') ?> View contact page</a>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <?php if (!$messages): ?>
        <div class="empty-state">
            <?= icon('message') ?>
            <h2 class="h4">Inbox zero</h2>
            <p class="text-muted-warm mb-0">New messages from the contact form will appear here.</p>
        </div>
    <?php else: ?>
        <div class="accordion" id="messageList">
            <?php foreach ($messages as $message): ?>
                <div class="accordion-item mb-2 border rounded" style="border-color:var(--line)!important">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-white" type="button" data-bs-toggle="collapse"
                                data-bs-target="#message-<?= (int) $message['id'] ?>">
                            <span class="d-flex flex-wrap align-items-center gap-2 w-100 me-3 text-start">
                                <?php if (!$message['is_read']): ?>
                                    <span class="badge text-bg-dark">New</span>
                                <?php endif; ?>
                                <strong class="small"><?= e($message['subject']) ?></strong>
                                <span class="small text-muted-warm ms-auto"><?= e($message['name']) ?> · <?= e(date('j M Y', strtotime($message['created_at']))) ?></span>
                            </span>
                        </button>
                    </h2>
                    <div id="message-<?= (int) $message['id'] ?>" class="accordion-collapse collapse" data-bs-parent="#messageList">
                        <div class="accordion-body">
                            <p class="small text-muted-warm mb-2">
                                From <strong><?= e($message['name']) ?></strong> ·
                                <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
                            </p>
                            <p class="mb-3" style="white-space:pre-line"><?= e($message['message']) ?></p>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-ink" href="mailto:<?= e($message['email']) ?>?subject=Re: <?= e(rawurlencode($message['subject'])) ?>"><?= icon('mail') ?> Reply by email</a>
                                <form method="post" action="messages.php" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <button class="btn btn-sm btn-outline-warm" type="submit">
                                        <?= $message['is_read'] ? 'Mark unread' : 'Mark read' ?>
                                    </button>
                                </form>
                                <form method="post" action="messages.php" class="d-inline"
                                      onsubmit="return confirm('Delete this message?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><?= icon('trash') ?> Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
