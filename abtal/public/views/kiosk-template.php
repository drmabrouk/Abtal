<?php
if (!defined('ABSPATH')) exit;

$system_logo = get_option('abtal_system_logo', '');
?>
<style>
    .kiosk-card-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 20px 0;
    }
    .kiosk-card {
        width: 100%;
        max-width: 500px;
        background: #1e293b;
        color: #ffffff;
        border-radius: 20px;
        padding: 32px;
        box-sizing: border-box;
        border: 1px solid #334155;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .kiosk-logo {
        max-height: 50px;
        margin-bottom: 12px;
    }
    .kiosk-title {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 4px 0;
        color: #ffffff;
    }
    .kiosk-subtitle {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 24px;
    }
    .kiosk-input {
        width: 100%;
        padding: 16px;
        font-size: 18px;
        border-radius: 12px;
        border: 2px solid #334155;
        background: #0f172a;
        color: #ffffff;
        text-align: center;
        font-family: monospace;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.2s;
        margin-bottom: 16px;
    }
    .kiosk-input:focus {
        border-color: #38bdf8;
    }
    .kiosk-btn {
        width: 100%;
        padding: 16px;
        background: #38bdf8;
        color: #0f172a;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
    }
    .kiosk-btn:hover {
        background: #7dd3fc;
    }
    .kiosk-alert {
        display: none;
        padding: 16px;
        border-radius: 12px;
        margin-top: 20px;
        font-size: 14px;
        text-align: left;
    }
    .alert-success {
        background: #064e3b;
        border: 1px solid #059669;
        color: #a7f3d0;
    }
    .alert-danger {
        background: #7f1d1d;
        border: 1px solid #dc2626;
        color: #fecaca;
    }
</style>

<div class="kiosk-card-wrapper">
    <div class="kiosk-card">
        <?php if (!empty($system_logo)) : ?>
            <img src="<?php echo esc_url($system_logo); ?>" alt="System Logo" class="kiosk-logo">
        <?php endif; ?>

        <h2 class="kiosk-title">Abtal Kiosk Scanner</h2>
        <div class="kiosk-subtitle">Scan Employee Barcode or Subscriber ID for Instant Processing</div>

        <form id="abtalKioskForm">
            <input type="text" id="kiosk_barcode" class="kiosk-input" placeholder="Scan Barcode / Enter ID..." autofocus required autocomplete="off">
            <button type="submit" class="kiosk-btn">Process Barcode Scan</button>
        </form>

        <div id="kioskSuccessAlert" class="kiosk-alert alert-success"></div>
        <div id="kioskDangerAlert" class="kiosk-alert alert-danger"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxUrl = "<?php echo esc_url(admin_url('admin-ajax.php')); ?>";
    var nonce   = "<?php echo esc_js(wp_create_nonce('abtal_nonce')); ?>";

    $('#abtalKioskForm').on('submit', function(e) {
        e.preventDefault();
        $('#kioskSuccessAlert, #kioskDangerAlert').hide();

        var barcode = $('#kiosk_barcode').val().trim();
        if (!barcode) return;

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'abtal_kiosk_scan',
                nonce: nonce,
                barcode: barcode
            },
            success: function(res) {
                if (res.success) {
                    var d = res.data;
                    var html = '<strong>' + d.message + '</strong><br>';
                    html += '<small>Name: ' + d.name + '</small><br>';
                    if (d.type === 'subscriber_verify') {
                        html += '<small>Plan: ' + d.plan_name + ' | Remaining: ' + d.sessions_remaining + '</small>';
                    } else {
                        html += '<small>Time: ' + d.time + '</small>';
                    }
                    $('#kioskSuccessAlert').html(html).slideDown(200);
                } else {
                    $('#kioskDangerAlert').text(res.data || 'Scan processing failed.').slideDown(200);
                }
                $('#kiosk_barcode').val('').focus();
            },
            error: function() {
                $('#kioskDangerAlert').text('Network connection error during kiosk scan.').slideDown(200);
            }
        });
    });
});
</script>
