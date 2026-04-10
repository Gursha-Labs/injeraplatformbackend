<!DOCTYPE html>
<html>

<head>
    <title>Payment Success</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }

        #status {
            font-size: 18px;
            margin: 20px 0;
        }

        #wallet-balance {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
        }

        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
        }

        .loading {
            color: #666;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }
    </style>
</head>

<body>
    <h1>💰 Payment Successful!</h1>
    <div id="status" class="loading">Processing your payment...</div>
    <div id="wallet-balance">Loading wallet balance...</div>
    <button onclick="location.reload()" style="display:none" id="refreshBtn">Refresh Page</button>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const tx_ref = urlParams.get('tx_ref');

        if (!tx_ref) {
            document.getElementById('status').innerHTML = '❌ No transaction reference found';
            document.getElementById('wallet-balance').innerHTML = 'Please contact support';
        } else {
            let attempts = 0;
            const maxAttempts = 30; // 60 seconds max

            function checkPaymentStatus() {
                fetch(`/api/check-payment-status?tx_ref=${tx_ref}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            document.getElementById('status').innerHTML = '✅ Payment completed successfully!';
                            document.getElementById('status').className = 'success';
                            document.getElementById('wallet-balance').innerHTML =
                                `Your new balance: ${data.balance} ETB`;
                            document.getElementById('wallet-balance').className = 'success';
                        } else if (attempts < maxAttempts) {
                            attempts++;
                            document.getElementById('status').innerHTML =
                                `Processing payment... (${attempts}/${maxAttempts})`;
                            setTimeout(checkPaymentStatus, 2000);
                        } else {
                            document.getElementById('status').innerHTML =
                                '⚠️ Payment confirmed but balance update delayed';
                            document.getElementById('wallet-balance').innerHTML =
                                'Please click the button below to refresh and see your balance.';
                            document.getElementById('refreshBtn').style.display = 'inline-block';
                        }
                    })
                    .catch(error => {
                        if (attempts < maxAttempts) {
                            attempts++;
                            setTimeout(checkPaymentStatus, 2000);
                        } else {
                            document.getElementById('status').innerHTML = '⚠️ Unable to verify payment status';
                            document.getElementById('refreshBtn').style.display = 'inline-block';
                        }
                    });
            }

            checkPaymentStatus();
        }
    </script>
</body>

</html>
