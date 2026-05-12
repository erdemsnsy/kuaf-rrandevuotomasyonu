<?php
require_once 'config/database.php';

// Ensure user is logged in
check_role('user');

$service_id = $_GET['service_id'] ?? null;
if (!$service_id) redirect('/index.php');

// Fetch Service and Barber Information
$stmt = $pdo->prepare("
    SELECT s.*, b.shop_name, b.opening_time, b.closing_time, b.closed_day, b.id as barber_id 
    FROM services s 
    JOIN barbers b ON s.barber_id = b.id 
    WHERE s.id = ?
");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if (!$service) redirect('/index.php');

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $note = $_POST['note'] ?? '';

    // Simple conflict check
    $stmt_check = $pdo->prepare("SELECT id FROM appointments WHERE barber_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $stmt_check->execute([$service['barber_id'], $date, $time]);
    
    if ($stmt_check->fetch()) {
        $error = "Bu tarih ve saatte kuaförün başka bir randevusu bulunmaktadır. Lütfen farklı bir zaman seçin.";
    } else {
        $pdo->beginTransaction();
        try {
            // Insert Appointment
            $stmt_app = $pdo->prepare("INSERT INTO appointments (user_id, barber_id, service_id, appointment_date, appointment_time, user_note) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_app->execute([$_SESSION['user_id'], $service['barber_id'], $service['id'], $date, $time, $note]);
            $appointment_id = $pdo->lastInsertId();

            // Insert Payment
            $stmt_pay = $pdo->prepare("INSERT INTO payments (appointment_id, amount, payment_method, status) VALUES (?, ?, ?, 'success')");
            $stmt_pay->execute([$appointment_id, $service['price'], $payment_method]);

            // Notify Barber Owner
            // First get the user_id of the barber owner
            $stmt_owner = $pdo->prepare("SELECT user_id FROM barbers WHERE id = ?");
            $stmt_owner->execute([$service['barber_id']]);
            $owner = $stmt_owner->fetch();
            
            if ($owner) {
                $msg = "Yeni Randevu! " . $_SESSION['name'] . " - " . $date . " " . $time;
                $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $stmt_notif->execute([$owner['user_id'], $msg]);
            }

            $pdo->commit();
            $success = "Randevunuz başarıyla oluşturuldu!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Randevu oluşturulurken bir hata oluştu: " . $e->getMessage();
        }
    }
}

require_once 'views/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <?php if ($success): ?>
            <div class="alert alert-success mt-4 text-center">
                <h4><i class="fas fa-check-circle me-2"></i><?= $success ?></h4>
                <a href="user_dashboard.php" class="btn btn-success mt-3">Randevularıma Git</a>
            </div>
        <?php else: ?>
            <div class="card card-custom mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Randevu Oluştur</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info border-0 rounded">
                        <strong>Kuaför:</strong> <?= htmlspecialchars($service['shop_name']) ?><br>
                        <strong>Seçilen Hizmet:</strong> <?= htmlspecialchars($service['service_name']) ?><br>
                        <strong>Süre:</strong> <?= $service['duration'] ?> Dakika <br>
                        <strong>Tutar:</strong> ₺<?= number_format($service['price'], 2) ?>
                    </div>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tarih</label>
                                <input type="date" name="date" id="appointmentDate" class="form-control" required min="<?= date('Y-m-d') ?>">
                                <?php if(!empty($service['closed_day'])): ?>
                                    <?php 
                                        $days = ['Monday'=>'Pazartesi', 'Tuesday'=>'Salı', 'Wednesday'=>'Çarşamba', 'Thursday'=>'Perşembe', 'Friday'=>'Cuma', 'Saturday'=>'Cumartesi', 'Sunday'=>'Pazar'];
                                        $tr_day = $days[$service['closed_day']];
                                    ?>
                                    <small class="text-danger">Kapalı Gün: <?= $tr_day ?>. Lütfen farklı bir gün seçin.</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Saat (<?= substr($service['opening_time'],0,5) ?> - <?= substr($service['closing_time'],0,5) ?>)</label>
                                <input type="time" name="time" class="form-control" required min="<?= substr($service['opening_time'],0,5) ?>" max="<?= substr($service['closing_time'],0,5) ?>" step="1800">
                                <small class="text-muted">30 dakikalık aralıklarla seçiniz (Örn: 14:00, 14:30)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kuaföre Notunuz (Opsiyonel)</label>
                            <textarea name="note" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Ödeme Yöntemi</label>
                            <select name="payment_method" id="paymentMethod" class="form-select" onchange="toggleCardForm()">
                                <option value="cash">Nakit (Kuaförde Ödeme)</option>
                                <option value="card">Banka / Kredi Kartı</option>
                            </select>
                        </div>

                        <div id="cardDetails" style="display:none;" class="bg-light p-3 rounded mb-4">
                            <h6>Kart Bilgileri</h6>
                            <p class="small text-muted"><i class="fas fa-lock me-1"></i>Kart bilgileriniz güvenle şifrelenir ve veritabanımızda saklanmaz.</p>
                            <div class="mb-2">
                                <input type="text" class="form-control" placeholder="Kart Üzerindeki İsim">
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control" placeholder="Kart Numarası" maxlength="16">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <input type="text" class="form-control" placeholder="AA/YY">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control" placeholder="CVV" maxlength="3">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-custom w-100">Ödemeyi Tamamla ve Randevu Al</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleCardForm() {
    var method = document.getElementById('paymentMethod').value;
    document.getElementById('cardDetails').style.display = method === 'card' ? 'block' : 'none';
    
    // Make card fields required if card is selected
    var inputs = document.getElementById('cardDetails').querySelectorAll('input');
    inputs.forEach(input => {
        input.required = (method === 'card');
    });
}
document.getElementById('appointmentDate').addEventListener('change', function() {
    var selectedDate = new Date(this.value);
    var dayOfWeek = selectedDate.toLocaleDateString('en-US', { weekday: 'long' });
    var closedDay = '<?= $service['closed_day'] ?? '' ?>';
    
    if(closedDay && dayOfWeek === closedDay) {
        alert('Seçtiğiniz tarihte kuaför kapalıdır. Lütfen başka bir gün seçiniz.');
        this.value = '';
    }
});
</script>

<?php require_once 'views/footer.php'; ?>
