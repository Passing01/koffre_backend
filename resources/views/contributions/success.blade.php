<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci pour votre soutien ! | Koffre</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 450px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .icon {
            font-size: 60px;
            color: #10b981;
            margin-bottom: 20px;
        }

        h1 {
            color: #0f172a;
            font-size: 28px;
            margin-bottom: 15px;
        }

        p {
            color: #64748b;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h1>Merci beaucoup !</h1>
        <p>Votre contribution a été enregistrée avec succès. Elle sera visible sur la cagnotte dès que le paiement sera
            confirmé par l'opérateur.</p>

        <div id="simulation-status"
            style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 12px; background: #fef3c7; color: #92400e; font-size: 14px; border: 1px solid #fde68a;">
            <i class="fas fa-robot mr-2"></i> Mode Simulation : <span id="status-text">Validation en cours...</span>
        </div>

        <a href="/" class="btn">Retour à Koffre</a>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const token = urlParams.get('token');

            if (token && token.startsWith('sim_token_')) {
                const statusDiv = document.getElementById('simulation-status');
                const statusText = document.getElementById('status-text');
                statusDiv.style.display = 'block';

                console.log('Simulation token detected, triggering webhook...', token);

                fetch('/api/payments/paydunya/webhook', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ token: token })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Webhook triggered successfully:', data);
                        statusText.innerText = 'Paiement simulé validé avec succès !';
                        statusDiv.style.background = '#d1fae5';
                        statusDiv.style.color = '#065f46';
                        statusDiv.style.borderColor = '#a7f3d0';
                    })
                    .catch(error => {
                        console.error('Error triggering simulated webhook:', error);
                        statusText.innerText = 'Échec de la simulation du webhook.';
                    });
            }
        });
    </script>
</body>

</html>