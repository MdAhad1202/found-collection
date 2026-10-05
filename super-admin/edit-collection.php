<?php
require_once __DIR__ . '/../includes/header.php';
requireSuperAdmin();

$id = (int)($_GET['id'] ?? 0);
$msg = '';
$type = 'info';

// Load collection
$stmt = $conn->prepare("
    SELECT c.*, s.roll, s.name AS student_name, a.name AS collector
    FROM collections c
    JOIN students s ON c.student_id = s.id
    JOIN admins a ON c.admin_id = a.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$col = $stmt->get_result()->fetch_assoc();

if (!$col) {
    echo "<div class='alert alert-danger'>❌ Collection entry পাওয়া যায়নি</div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Update
if (isset($_POST['update'])) {
    $newAmount = (float)$_POST['amount'];
    $newNote = trim($_POST['note'] ?? '');
    
    if ($newAmount <= 0) {
        $msg = "❌ Amount ০-এর বেশি হতে হবে";
        $type = 'danger';
    } else {
        $oldAmount = $col['amount'];
        $upd = $conn->prepare("UPDATE collections SET amount = ?, note = ? WHERE id = ?");
        $upd->bind_param("dsi", $newAmount, $newNote, $id);
        
        if ($upd->execute()) {
            logActivity($conn, $_SESSION['admin_id'], 'collection_edit', 
                "ID $id: ৳$oldAmount → ৳$newAmount");
            $msg = "✅ Update successful! (৳" . number_format($oldAmount, 2) . " → ৳" . number_format($newAmount, 2) . ")";
            $type = 'success';
            
            // Reload
            $stmt->execute();
            $col = $stmt->get_result()->fetch_assoc();
        } else {
            $msg = "❌ Update failed: " . $conn->error;
            $type = 'danger';
        }
    }
}

// Delete
if (isset($_POST['delete'])) {
    $del = $conn->prepare("DELETE FROM collections WHERE id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        logActivity($conn, $_SESSION['admin_id'], 'collection_delete', 
            "ID $id: Roll {$col['roll']}, Amount ৳{$col['amount']}");
        header("Location: collections.php?msg=deleted");
        exit;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">✏️ Edit Collection Entry</h2>
    <a href="collections.php" class="btn btn-secondary">← Back to Collections</a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-<?= $type ?> alert-dismissible fade show">
        <?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card stat-card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">📋 Entry Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td><strong>Entry ID:</strong></td>
                        <td>#<?= $col['id'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Student:</strong></td>
                        <td><?= htmlspecialchars($col['roll']) ?> — <?= htmlspecialchars($col['student_name']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Collector:</strong></td>
                        <td><span class="badge bg-primary"><?= htmlspecialchars($col['collector']) ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Collected At:</strong></td>
                        <td><?= date('d-M-Y h:i A', strtotime($col['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Current Amount:</strong></td>
                        <td><span class="text-success fw-bold fs-5">৳ <?= number_format($col['amount'], 2) ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card stat-card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">✏️ Edit Amount & Note</h5>
            </div>
            <div class="card-body">
                <form method="POST" onsubmit="return confirm('Update করতে চান?')">
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Amount (৳)</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">৳</span>
                            <input type="number" name="amount" class="form-control" 
                                   value="<?= $col['amount'] ?>" required min="1" step="0.01">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <input type="text" name="note" class="form-control" 
                               value="<?= htmlspecialchars($col['note'] ?? '') ?>"
                               placeholder="Optional note">
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button name="update" class="btn btn-warning btn-lg flex-grow-1">
                            💾 Update
                        </button>
                        <button type="button" class="btn btn-danger btn-lg" 
                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                            🗑️ Delete
                        </button>
                    </div>
                </form>
                
                <div class="alert alert-info mt-3 mb-0">
                    <small>
                        <strong>⚠️ Note:</strong> এই edit হবে <strong>permanent</strong>। 
                        Activity log-এ record থাকবে।
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">⚠️ Delete Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>আপনি কি নিশ্চিত এই entry <strong>permanently delete</strong> করতে চান?</p>
                    <ul>
                        <li><strong>Entry ID:</strong> #<?= $col['id'] ?></li>
                        <li><strong>Student:</strong> <?= htmlspecialchars($col['roll']) ?> (<?= htmlspecialchars($col['student_name']) ?>)</li>
                        <li><strong>Amount:</strong> ৳ <?= number_format($col['amount'], 2) ?></li>
                        <li><strong>Collector:</strong> <?= htmlspecialchars($col['collector']) ?></li>
                    </ul>
                    <p class="text-danger mb-0"><strong>এই কাজটি undo করা যাবে না!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="delete" class="btn btn-danger">🗑️ Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>