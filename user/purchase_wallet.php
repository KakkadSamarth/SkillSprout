<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `transactions` (
    `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount_wp` INT NOT NULL,
    `price_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(150) NOT NULL DEFAULT 'Mock Card / Test Payment',
    `status` ENUM('COMPLETED', 'PENDING', 'FAILED') NOT NULL DEFAULT 'COMPLETED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$sql = "SELECT user_id, name, email, wp_balance FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: " . BASE_URL . "auth/logout.php");
    exit();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $package = $_POST["package"] ?? "100";
    $custom_wp = isset($_POST["custom_wp"]) ? (int) $_POST["custom_wp"] : 0;
    $payment_option = $_POST["payment_option"] ?? "upi";

    $amount_wp = 0;
    $price_paid = 0.00;

    if ($package === "100") {
        $amount_wp = 100;
        $price_paid = 100.00;
    } elseif ($package === "250") {
        $amount_wp = 250;
        $price_paid = 250.00;
    } elseif ($package === "500") {
        $amount_wp = 500;
        $price_paid = 500.00;
    } elseif ($package === "1000") {
        $amount_wp = 1000;
        $price_paid = 1000.00;
    } elseif ($package === "2500") {
        $amount_wp = 2500;
        $price_paid = 2500.00;
    } elseif ($package === "custom") {
        if ($custom_wp < 10 || $custom_wp > 50000) {
            $error_message = "Custom amount must be between 10 WP and 50,000 WP.";
        } else {
            $amount_wp = $custom_wp;
            $price_paid = (float) $custom_wp;
        }
    } else {
        $error_message = "Please select a valid Work Points package.";
    }

    $payment_method = "";

    if ($payment_option === "upi") {
        $upi_id = trim($_POST["upi_id"] ?? "");
        $upi_app = trim($_POST["upi_app"] ?? "UPI");

        if ($upi_id === "") {
            $error_message = "Please enter your UPI ID / VPA (e.g. yourname@okhdfcbank).";
        } elseif (!preg_match('/^[\w.\-_]{2,256}@[a-zA-Z]{2,64}$/', $upi_id)) {
            $error_message = "Invalid UPI ID format. A valid UPI ID resembles user@bank or mobile@upi.";
        } else {
            $payment_method = "UPI (" . $upi_id . " via " . $upi_app . ")";
        }

    } elseif ($payment_option === "card") {
        $card_name = trim($_POST["card_name"] ?? "");
        $card_number = trim($_POST["card_number"] ?? "");
        $card_expiry = trim($_POST["card_expiry"] ?? "");
        $card_cvv = trim($_POST["card_cvv"] ?? "");

        $clean_card = preg_replace('/\D/', '', $card_number);

        if ($card_name === "") {
            $error_message = "Please enter the Cardholder Name.";
        } elseif (strlen($clean_card) < 15 || strlen($clean_card) > 19) {
            $error_message = "Please enter a valid 16-digit Debit or Credit Card number.";
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $card_expiry)) {
            $error_message = "Please enter a valid expiry date in MM/YY format.";
        } elseif (!preg_match('/^[0-9]{3,4}$/', $card_cvv)) {
            $error_message = "Please enter a valid 3 or 4 digit CVV/CVC code.";
        } else {
            $last4 = substr($clean_card, -4);
            $payment_method = "Card (" . $card_name . " - ending in " . $last4 . ")";
        }

    } elseif ($payment_option === "netbanking") {
        $bank_name = trim($_POST["bank_name"] ?? "");
        $netbanking_userid = trim($_POST["netbanking_userid"] ?? "");

        if ($bank_name === "") {
            $error_message = "Please select your bank for Net Banking.";
        } elseif ($netbanking_userid === "") {
            $error_message = "Please enter your Net Banking Customer ID or Username.";
        } else {
            $payment_method = "Net Banking (" . $bank_name . " - ID: " . $netbanking_userid . ")";
        }

    } elseif ($payment_option === "wallet") {
        $wallet_provider = trim($_POST["wallet_provider"] ?? "");
        $wallet_mobile = trim($_POST["wallet_mobile"] ?? "");

        if ($wallet_provider === "") {
            $error_message = "Please select your mobile wallet provider.";
        } elseif (!preg_match('/^[6-9]\d{9}$/', $wallet_mobile)) {
            $error_message = "Please enter a valid 10-digit Indian mobile number registered with your wallet.";
        } else {
            $payment_method = "Wallet (" . $wallet_provider . " - " . $wallet_mobile . ")";
        }

    } else {
        $error_message = "Please select a valid payment option.";
    }

    if ($error_message === "" && $amount_wp > 0) {
        mysqli_begin_transaction($conn);

        try {
            $update_sql = "UPDATE users SET wp_balance = wp_balance + ? WHERE user_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "ii", $amount_wp, $user_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            $txn_sql = "INSERT INTO transactions (user_id, amount_wp, price_paid, payment_method, status) VALUES (?, ?, ?, ?, 'COMPLETED')";
            $txn_stmt = mysqli_prepare($conn, $txn_sql);
            mysqli_stmt_bind_param($txn_stmt, "iids", $user_id, $amount_wp, $price_paid, $payment_method);
            mysqli_stmt_execute($txn_stmt);
            mysqli_stmt_close($txn_stmt);

            mysqli_commit($conn);

            header("Location: " . BASE_URL . "user/wallet.php?success=purchased&wp=" . $amount_wp . "&rupees=" . $price_paid);
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error_message = "Transaction failed: " . $e->getMessage();
        }
    }
}

include __DIR__ . "/../includes/header2.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Purchase Work Points - SkillSprout Wallet</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<main>
    <div class="purchase-container">
        <a href="<?= BASE_URL ?>user/wallet.php" class="btn-cancel">&larr; Back to My Wallet</a>
        <h1 class="mb-0">Purchase Work Points</h1>
        <p class="text-muted mt-0">
            Add funds in Indian Rupees (&IndianRupee;) to your SkillSprout balance to post tasks, hire talent, and reward solutions.
        </p>

        <div class="rate-banner">
            <div>
                <strong class="rate-banner-title">Standard Platform Exchange Rate:</strong>
                <div class="rate-banner-sub">1 Indian Rupee = 1 WorkPoint (1:1 Ratio)</div>
            </div>
            <div class="rate-badge">
                &#8377;1 = 1 WP
            </div>
        </div>

        <?php if ($error_message !== ""): ?>
            <div class="alert-error">
                &cross; <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" id="purchaseForm">
            <h3 class="mb-1">1. Select Work Points Package</h3>
            
            <div class="package-grid">
                <label class="package-card selected" onclick="selectPackage('100', '100.00', this)">
                    <input type="radio" name="package" value="100" checked>
                    <div class="package-wp">100 WP</div>
                    <div class="package-price">&#8377;100</div>
                    <div class="pkg-tag pkg-tag-starter">Starter</div>
                </label>

                <label class="package-card" onclick="selectPackage('250', '250.00', this)">
                    <input type="radio" name="package" value="250">
                    <div class="package-wp">250 WP</div>
                    <div class="package-price">&#8377;250</div>
                    <div class="pkg-tag pkg-tag-popular">Popular</div>
                </label>

                <label class="package-card" onclick="selectPackage('500', '500.00', this)">
                    <input type="radio" name="package" value="500">
                    <div class="package-wp">500 WP</div>
                    <div class="package-price">&#8377;500</div>
                    <div class="pkg-tag pkg-tag-standard">Standard</div>
                </label>

                <label class="package-card" onclick="selectPackage('1000', '1000.00', this)">
                    <input type="radio" name="package" value="1000">
                    <div class="package-wp">1,000 WP</div>
                    <div class="package-price">&#8377;1,000</div>
                    <div class="pkg-tag pkg-tag-pro">Pro</div>
                </label>

                <label class="package-card" onclick="selectPackage('2500', '2500.00', this)">
                    <input type="radio" name="package" value="2500">
                    <div class="package-wp">2,500 WP</div>
                    <div class="package-price">&#8377;2,500</div>
                    <div class="pkg-tag pkg-tag-business">Business</div>
                </label>
            </div>

            <div class="custom-wp-box">
                <label class="custom-wp-label">
                    <input type="radio" name="package" value="custom" id="customRadio" onclick="selectCustom(this)">
                    <span>Or enter custom Work Points amount (&#8377;1 per WP):</span>
                </label>
                <div class="custom-wp-inputs">
                    <input type="number" name="custom_wp" id="customInput" min="10" max="50000" step="1" placeholder="e.g. 350" class="custom-wp-input" oninput="updateCustomPrice()" disabled>
                    <span id="customPriceLabel" class="custom-price-label">Rate: &#8377;1 = 1 WP (Min. 10 WP)</span>
                </div>
            </div>

            <h3 class="mb-1">2. Choose Payment Method</h3>
            
            <div class="payment-method-selector">
                <div class="method-tab active" id="tab_upi" onclick="switchPaymentMethod('upi')">
                    <input type="radio" name="payment_option" value="upi" id="opt_upi" checked class="hidden-radio">
                    &#9889; UPI / QR Code
                </div>
                <div class="method-tab" id="tab_card" onclick="switchPaymentMethod('card')">
                    <input type="radio" name="payment_option" value="card" id="opt_card" class="hidden-radio">
                    &#128179; Debit / Credit Card
                </div>
                <div class="method-tab" id="tab_netbanking" onclick="switchPaymentMethod('netbanking')">
                    <input type="radio" name="payment_option" value="netbanking" id="opt_netbanking" class="hidden-radio">
                    &#127966; Net Banking
                </div>
                <div class="method-tab" id="tab_wallet" onclick="switchPaymentMethod('wallet')">
                    <input type="radio" name="payment_option" value="wallet" id="opt_wallet" class="hidden-radio">
                    &#128091; Mobile Wallets
                </div>
            </div>

            <div class="payment-section">

                <div class="option-panel active" id="panel_upi">
                    <h4>Enter UPI Details (Google Pay, PhonePe, Paytm, BHIM)</h4>
                    <div class="form-row">
                        <label for="upi_id">Your UPI ID / VPA <span class="required-star">*</span></label>
                        <input type="text" name="upi_id" id="upi_id" placeholder="e.g. yourname@okhdfcbank or 9876543210@paytm" required>
                    </div>
                    <div class="form-row">
                        <label for="upi_app">Select UPI App / Provider</label>
                        <select name="upi_app" id="upi_app">
                            <option value="Google Pay">Google Pay (Tez)</option>
                            <option value="PhonePe">PhonePe</option>
                            <option value="Paytm UPI">Paytm UPI</option>
                            <option value="BHIM UPI">BHIM UPI</option>
                            <option value="CRED UPI">CRED UPI</option>
                            <option value="Other Bank UPI">Other Bank UPI</option>
                        </select>
                    </div>
                    <div class="form-help-text">
                        &bull; A collect request will be simulated to your UPI handle. Payment is completed instantly.
                    </div>
                </div>

                <div class="option-panel" id="panel_card">
                    <h4>Debit, Credit or ATM Card (RuPay, Visa, Mastercard)</h4>
                    <div class="form-row">
                        <label for="card_name">Cardholder Name <span class="required-star">*</span></label>
                        <input type="text" name="card_name" id="card_name" value="<?php echo htmlspecialchars($user["name"]); ?>">
                    </div>
                    <div class="form-row">
                        <label for="card_number">16-Digit Card Number <span class="required-star">*</span></label>
                        <input type="text" name="card_number" id="card_number" maxlength="19" placeholder="4242 4242 4242 4242" value="4242 4242 4242 4242">
                    </div>
                    <div class="form-cols">
                        <div class="form-row">
                            <label for="card_expiry">Expiry Date (MM/YY) <span class="required-star">*</span></label>
                            <input type="text" name="card_expiry" id="card_expiry" placeholder="MM/YY" maxlength="5" value="12/28">
                        </div>
                        <div class="form-row">
                            <label for="card_cvv">CVV / Security Code (3 Digits) <span class="required-star">*</span></label>
                            <input type="password" name="card_cvv" id="card_cvv" placeholder="123" maxlength="4" value="123">
                        </div>
                    </div>
                </div>

                <div class="option-panel" id="panel_netbanking">
                    <h4>Internet Banking</h4>
                    <div class="form-row">
                        <label for="bank_name">Select Your Bank <span class="required-star">*</span></label>
                        <select name="bank_name" id="bank_name">
                            <option value="">-- Choose Indian Bank --</option>
                            <option value="State Bank of India (SBI)" selected>State Bank of India (SBI)</option>
                            <option value="HDFC Bank">HDFC Bank</option>
                            <option value="ICICI Bank">ICICI Bank</option>
                            <option value="Axis Bank">Axis Bank</option>
                            <option value="Kotak Mahindra Bank">Kotak Mahindra Bank</option>
                            <option value="Punjab National Bank (PNB)">Punjab National Bank (PNB)</option>
                            <option value="Bank of Baroda">Bank of Baroda</option>
                            <option value="Canara Bank">Canara Bank</option>
                            <option value="Union Bank of India">Union Bank of India</option>
                            <option value="Other Major Indian Bank">Other Major Indian Bank</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="netbanking_userid">Net Banking Customer ID / Username <span class="required-star">*</span></label>
                        <input type="text" name="netbanking_userid" id="netbanking_userid" placeholder="Enter Customer ID or Username" value="USER_<?php echo $user_id; ?>">
                    </div>
                </div>

                <div class="option-panel" id="panel_wallet">
                    <h4>Digital Mobile Wallet</h4>
                    <div class="form-row">
                        <label for="wallet_provider">Select Wallet Provider <span class="required-star">*</span></label>
                        <select name="wallet_provider" id="wallet_provider">
                            <option value="Paytm Wallet" selected>Paytm Wallet</option>
                            <option value="PhonePe Wallet">PhonePe Wallet</option>
                            <option value="Amazon Pay">Amazon Pay</option>
                            <option value="Mobikwik">Mobikwik</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="wallet_mobile">Registered 10-Digit Mobile Number <span class="required-star">*</span></label>
                        <input type="tel" name="wallet_mobile" id="wallet_mobile" placeholder="e.g. 9876543210" maxlength="10" pattern="[6-9][0-9]{9}" value="9876543210">
                    </div>
                </div>

                <div class="summary-box">
                    <div class="summary-row">
                        <span>Work Points to Credit:</span>
                        <strong id="summaryWp" class="summary-wp">+100 WP</strong>
                    </div>
                    <div class="summary-total">
                        <span>Total Payable Amount (Rupees):</span>
                        <strong id="summaryPrice" class="summary-price">&#8377;100.00</strong>
                    </div>
                </div>

                <button type="submit" class="btn-purchase" id="submitBtn">
                    Pay &#8377;100.00 &amp; Add 100 WP
                </button>
                <div class="security-notice">
                    1 Rupee = 1 WorkPoint &bull; Safe simulated transaction &bull; Funds immediately credited to your wallet balance.
                </div>
            </div>
        </form>
    </div>
</main>

<script>
let currentPrice = 100;
let currentWp = 100;

function selectPackage(wp, price, cardElement) {
    document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
    cardElement.classList.add('selected');
    cardElement.querySelector('input[type="radio"]').checked = true;

    const customInput = document.getElementById('customInput');
    customInput.disabled = true;

    currentWp = parseInt(wp, 10);
    currentPrice = parseFloat(price);

    updateDisplay();
}

function selectCustom(radioElement) {
    document.querySelectorAll('.package-card').forEach(c => c.classList.remove('selected'));
    radioElement.checked = true;

    const customInput = document.getElementById('customInput');
    customInput.disabled = false;
    customInput.focus();
    updateCustomPrice();
}

function updateCustomPrice() {
    const customInput = document.getElementById('customInput');
    let val = parseInt(customInput.value, 10);
    if (isNaN(val) || val < 10) {
        val = 10;
    }
    currentWp = val;
    currentPrice = val;

    document.getElementById('customPriceLabel').innerHTML = '= &#8377;' + val.toFixed(2) + ' (&#8377;1/WP)';
    updateDisplay();
}

function updateDisplay() {
    document.getElementById('summaryWp').innerText = '+' + currentWp.toLocaleString('en-IN') + ' WP';
    document.getElementById('summaryPrice').innerHTML = '&#8377;' + currentPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('submitBtn').innerHTML = 'Pay &#8377;' + currentPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' &amp; Add ' + currentWp.toLocaleString('en-IN') + ' WP';
}

function switchPaymentMethod(method) {
    document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.option-panel').forEach(p => p.classList.remove('active'));

    document.getElementById('tab_' + method).classList.add('active');
    document.getElementById('opt_' + method).checked = true;
    document.getElementById('panel_' + method).classList.add('active');

    const fields = {
        upi: ['upi_id'],
        card: ['card_name', 'card_number', 'card_expiry', 'card_cvv'],
        netbanking: ['bank_name', 'netbanking_userid'],
        wallet: ['wallet_provider', 'wallet_mobile']
    };

    for (let key in fields) {
        fields[key].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (key === method) {
                    el.required = true;
                    el.disabled = false;
                } else {
                    el.required = false;
                    el.disabled = true;
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    switchPaymentMethod('upi');
});
</script>

<?php include __DIR__ . "/../includes/footer2.php"; ?>

</body>
</html>
