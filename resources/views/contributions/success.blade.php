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

        .info-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 25px;
            text-align: left;
        }

        .info-box .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-box .value {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-top: 2px;
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

        .btn-outline {
            display: inline-block;
            margin-top: 12px;
            padding: 10px 20px;
            border: 2px solid #6366f1;
            color: #6366f1;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h1>Merci beaucoup !</h1>
        <p>Votre contribution a été enregistrée avec succès.</p>

        @if(isset($contribution))
            <div class="info-box">
                <div class="label">Montant versé</div>
                <div class="value">{{ number_format($contribution->amount, 0, ',', ' ') }} XOF</div>
            </div>
        @endif

        @if(isset($cagnotte))
            <div class="info-box">
                <div class="label">Cagnotte</div>
                <div class="value">{{ $cagnotte->title }}</div>
            </div>
        @endif

        @if(isset($tontine))
            <div class="info-box">
                <div class="label">Tontine</div>
                <div class="value">{{ $tontine->title }}</div>
            </div>
        @endif

        @if(isset($reference))
            <div class="info-box">
                <div class="label">Référence</div>
                <div class="value" style="font-size:13px; word-break:break-all;">{{ $reference }}</div>
            </div>
        @endif

        @if(isset($cagnotte))
            <a href="{{ route('cagnotte.web_show', $cagnotte->id) }}" class="btn">Voir la cagnotte</a>
        @else
            <a href="/" class="btn">Retour à Koffre</a>
        @endif

        <br>
        <a href="{{ $deeplink ?? 'koffre://payment/success' }}" class="btn-outline">
            <i class="fa-solid fa-mobile-screen"></i> Ouvrir dans l'application
        </a>
    </div>

    <script>
        // Redirection automatique vers l'app après 4s
        setTimeout(() => {
            window.location.href = '{{ $deeplink ?? "koffre://payment/success" }}';
        }, 4000);
    </script>
</body>

</html>