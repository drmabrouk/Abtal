<?php
if (!defined('ABSPATH')) exit;

$initial_token = Sportedia_Attendance_Manager::generate_attendance_qr_token();
$ajax_url      = admin_url('admin-ajax.php');
$nonce         = wp_create_nonce('sportedia_nonce');
$current_user  = wp_get_current_user();
$system_logo   = Sportedia_Settings_Manager::get_setting('system_logo', '');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportedia – Staff Attendance Kiosk</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Google+Sans+Flex:wght@400;600;700&display=swap">
    <style>
        :root {
            --sp-bg: #f8f9fa;
            --sp-card: #ffffff;
            --sp-text: #111827;
            --sp-muted: #6b7280;
            --sp-border: #e5e7eb;
            --sp-radius: 12px;
            --sp-primary: #000000;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--sp-bg);
            font-family: 'Google Sans Flex', -apple-system, sans-serif;
            color: var(--sp-text);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            direction: ltr;
        }

        .sp-kiosk-container {
            width: 100%;
            max-width: 460px;
            background: var(--sp-card);
            border: 1px solid var(--sp-border);
            border-radius: var(--sp-radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            padding: 36px 28px;
            box-sizing: border-box;
            text-align: center;
            margin: 20px;
        }

        .sp-kiosk-brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .sp-kiosk-logo {
            width: 40px;
            height: 40px;
            background: var(--sp-primary);
            color: #ffffff;
            border-radius: var(--sp-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 20px;
        }

        .sp-kiosk-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 4px 0;
            letter-spacing: -0.5px;
        }

        .sp-kiosk-subtitle {
            font-size: 13px;
            color: var(--sp-muted);
            margin: 0 0 24px 0;
        }

        .sp-qr-box {
            background: #ffffff;
            border: 2px solid var(--sp-border);
            border-radius: var(--sp-radius);
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 220px;
        }

        .sp-barcode-lines {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            height: 100px;
            width: 100%;
            max-width: 280px;
            margin-bottom: 12px;
        }

        .sp-barcode-line {
            background: var(--sp-primary);
            height: 100%;
            border-radius: 1px;
        }

        .sp-qr-code-text {
            font-family: monospace;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            color: var(--sp-text);
            background: #f3f4f6;
            padding: 6px 14px;
            border-radius: 6px;
        }

        .sp-timer-bar {
            width: 100%;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .sp-timer-progress {
            height: 100%;
            background: var(--sp-primary);
            width: 100%;
            transition: width 0.1s linear;
        }

        .sp-timer-label {
            font-size: 12px;
            color: var(--sp-muted);
            font-weight: 600;
        }

        .sp-scan-section {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--sp-border);
            text-align: left;
        }

        .sp-input {
            width: 100%;
            padding: 12px 14px;
            font-size: 13px;
            border: 1px solid var(--sp-border);
            border-radius: var(--sp-radius);
            box-sizing: border-box;
            outline: none;
            margin-bottom: 10px;
        }

        .sp-btn {
            width: 100%;
            padding: 12px;
            background: var(--sp-primary);
            color: #ffffff;
            border: none;
            border-radius: var(--sp-radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .sp-btn:hover {
            background: #222222;
        }

        /* Toast notification */
        .sp-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--sp-primary);
            color: #ffffff;
            padding: 14px 24px;
            border-radius: var(--sp-radius);
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            display: none;
            z-index: 3000;
        }
    </style>
</head>
<body>

<div class="sp-kiosk-container">
    <div class="sp-kiosk-brand">
        <?php if (!empty($system_logo)) : ?>
            <img src="<?php echo esc_url($system_logo); ?>" style="max-height: 48px; max-width: 140px; object-fit: contain;">
        <?php else : ?>
            <div class="sp-kiosk-logo">S</div>
        <?php endif; ?>
        <div style="text-align: left;">
            <div class="sp-kiosk-title">Sportedia</div>
            <div class="sp-kiosk-subtitle" style="margin: 0;">Automated Kiosk & Attendance System</div>
        </div>
    </div>

    <div class="sp-qr-box">
        <div id="spBarcodeContainer" class="sp-barcode-lines"></div>
        <div id="spCodeText" class="sp-qr-code-text"><?php echo esc_html($initial_token); ?></div>
    </div>

    <div class="sp-timer-bar">
        <div id="spTimerProgress" class="sp-timer-progress"></div>
    </div>
    <div id="spTimerText" class="sp-timer-label">Refreshing code in 5.0s</div>

    <!-- Barcode Scanner Field & Camera Engine -->
    <div class="sp-scan-section">
        <label style="font-size: 12px; font-weight: 700; display: block; margin-bottom: 8px; text-transform: uppercase; color: var(--sp-primary);">
            Unified Barcode & Camera Scanner Engine
        </label>

        <!-- Video Stream Container -->
        <div id="spKioskCameraContainer" style="display: none; margin-bottom: 12px; position: relative; border-radius: 12px; overflow: hidden; border: 2px solid #2B3245;">
            <video id="spKioskVideo" playsinline style="width: 100%; height: 200px; object-fit: cover; background: #000;"></video>
            <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: #ffffff; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600;">Camera Stream Active</div>
        </div>

        <button type="button" class="sp-btn" id="spToggleCameraBtn" onclick="toggleKioskCamera()" style="margin-bottom: 10px; background: #2B3245; color: #ffffff;">
            Toggle Camera Scanner
        </button>

        <div style="position: relative; margin-bottom: 10px;">
            <input type="text" id="spKioskBarcodeInput" class="sp-input" placeholder="Scan Barcode ID (Employee / Subscriber)..." autocomplete="off" autofocus style="padding-right: 40px; font-family: monospace; font-size: 14px; font-weight: 600;">
            <div style="position: absolute; right: 12px; top: 12px; color: #3E8E41; font-size: 18px;" title="Scanner Active">&#x25CF;</div>
        </div>
        <button type="button" class="sp-btn" onclick="submitKioskBarcodeScan()" style="background: #C02030;">
            Process Barcode Scan
        </button>
        <?php if (is_user_logged_in()) : ?>
            <button type="button" class="sp-btn" onclick="scanCurrentToken()" style="margin-top: 8px; background: #2B3245;">
                Quick Self Scan (<?php echo esc_html($current_user->display_name); ?>)
            </button>
        <?php endif; ?>
    </div>

    <!-- Scan Results Card -->
    <div id="spScanResultCard" style="display: none; margin-top: 16px; padding: 14px; border-radius: 8px; font-size: 13px; text-align: left;">
        <strong id="scan_result_title" style="display: block; font-size: 14px; margin-bottom: 4px;"></strong>
        <span id="scan_result_msg"></span>
    </div>
</div>

<div id="spToast" class="sp-toast"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
var currentToken = "<?php echo esc_js($initial_token); ?>";
var ajaxUrl = "<?php echo esc_js($ajax_url); ?>";
var nonce = "<?php echo esc_js($nonce); ?>";
var refreshInterval = 5000; // 5 seconds
var timeLeft = refreshInterval;
var lastFetchTime = Date.now();

function generateBarcodeSVG(text) {
    var container = $('#spBarcodeContainer');
    container.empty();
    var hash = 0;
    for (var i = 0; i < text.length; i++) {
        hash = text.charCodeAt(i) + ((hash << 5) - hash);
    }
    for (var j = 0; j < 36; j++) {
        var width = (Math.abs(hash + j * 13) % 4) + 1;
        var bar = $('<div>').addClass('sp-barcode-line').css('width', width + 'px');
        container.append(bar);
    }
}

function updateCode() {
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: { action: 'sportedia_get_kiosk_qr' },
        success: function(res) {
            if (res.success && res.data.token) {
                currentToken = res.data.token;
                $('#spCodeText').text(currentToken);
                generateBarcodeSVG(currentToken);
            }
        },
        error: function() {
            // Graceful recovery on network error
        }
    });
}

generateBarcodeSVG(currentToken);

setInterval(function() {
    var now = Date.now();
    var elapsed = now - lastFetchTime;
    var remaining = Math.max(0, refreshInterval - elapsed);

    var pct = (remaining / refreshInterval) * 100;
    $('#spTimerProgress').css('width', pct + '%');
    $('#spTimerText').text('Refreshing code in ' + (remaining / 1000).toFixed(1) + 's');

    if (remaining <= 0) {
        lastFetchTime = Date.now();
        updateCode();
    }
}, 100);

function showToast(msg) {
    $('#spToast').text(msg).fadeIn(200);
    setTimeout(function() {
        $('#spToast').fadeOut(300);
    }, 4000);
}

var cameraStream = null;
var cameraScanning = false;

function toggleKioskCamera() {
    if (cameraScanning) {
        stopKioskCamera();
    } else {
        startKioskCamera();
    }
}

function startKioskCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showToast('Camera access not supported on this browser.');
        return;
    }

    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            cameraStream = stream;
            cameraScanning = true;
            var video = document.getElementById('spKioskVideo');
            video.srcObject = stream;
            video.play();
            $('#spKioskCameraContainer').slideDown(200);
            $('#spToggleCameraBtn').text('Stop Camera Scanner').css('background', '#C02030');

            if ('BarcodeDetector' in window) {
                var detector = new BarcodeDetector();
                var scanFrame = function() {
                    if (!cameraScanning) return;
                    detector.detect(video).then(function(barcodes) {
                        if (barcodes.length > 0) {
                            var code = barcodes[0].rawValue;
                            if (code) {
                                $('#spKioskBarcodeInput').val(code);
                                submitKioskBarcodeScan();
                            }
                        }
                        if (cameraScanning) requestAnimationFrame(scanFrame);
                    }).catch(function() {
                        if (cameraScanning) requestAnimationFrame(scanFrame);
                    });
                };
                requestAnimationFrame(scanFrame);
            }
        })
        .catch(function(err) {
            showToast('Unable to access camera: ' + err.message);
        });
}

function stopKioskCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(function(track) { track.stop(); });
        cameraStream = null;
    }
    cameraScanning = false;
    $('#spKioskCameraContainer').slideUp(200);
    $('#spToggleCameraBtn').text('Toggle Camera Scanner').css('background', '#2B3245');
}

function submitKioskBarcodeScan(customBarcode) {
    var barcode = customBarcode || $('#spKioskBarcodeInput').val().trim();
    if (!barcode) {
        showToast('Please scan or enter a barcode.');
        return;
    }

    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'sportedia_process_kiosk_barcode_scan',
            nonce: nonce,
            barcode: barcode
        },
        success: function(res) {
            $('#spKioskBarcodeInput').val('').focus();
            if (res.success) {
                var d = res.data;
                showToast(d.message || 'Scan processed successfully!');
                $('#scan_result_title').text(d.name + ' (' + d.employee_id + ')');
                $('#scan_result_msg').text(d.message);
                $('#spScanResultCard').css({ background: '#f0fdf4', color: '#166534', border: '1px solid #bbf7d0' }).slideDown(200);
            } else {
                showToast(res.data || 'Scan failed.');
                $('#scan_result_title').text('Scan Failed');
                $('#scan_result_msg').text(res.data || 'Barcode not recognized.');
                $('#spScanResultCard').css({ background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca' }).slideDown(200);
            }
            setTimeout(function() {
                $('#spScanResultCard').slideUp(200);
            }, 6000);
        },
        error: function() {
            showToast('Network error during barcode processing.');
            $('#spKioskBarcodeInput').val('').focus();
        }
    });
}

function scanCurrentToken() {
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'sportedia_process_employee_scan',
            nonce: nonce,
            qr_token: currentToken
        },
        success: function(res) {
            if (res.success) {
                showToast(res.data.message || 'Attendance recorded successfully!');
            } else {
                showToast(res.data || 'Scan failed.');
            }
        },
        error: function() {
            showToast('Network error during scan processing.');
        }
    });
}

// Automatic Barcode Scanner Keystroke Listener
$(document).ready(function() {
    $('#spKioskBarcodeInput').focus();

    // Auto focus lock
    $(document).on('click', function(e) {
        if (!$(e.target).is('input, select, textarea, button')) {
            $('#spKioskBarcodeInput').focus();
        }
    });

    $('#spKioskBarcodeInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            submitKioskBarcodeScan();
        }
    });
});
</script>
</body>
</html>
