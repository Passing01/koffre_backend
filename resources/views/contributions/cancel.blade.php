<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement annulé | Koffre</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            text-align: center;
        }

        .container {
            max-width: 400px;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .icon {
            font-size: 60px;
            color: #ef4444;
            margin-bottom: 20px;
        }

        h1 {
            color: #1e293b;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
        }

        .btn-outline {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            border: 2px solid #94a3b8;
            color: #64748b;
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
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
        <h1>Paiement interrompu</h1>
        <p>{{ $message ?? 'Le processus de paiement a été interrompu. Vous pouvez réessayer à tout moment.' }}</p>

        @if(isset($cagnotte))
            <a href="{{ route('cagnotte.web_show', $cagnotte->id) }}" class="btn">Retour à la cagnotte</a>
        @else
            <a href="/" class="btn">Retour à l'accueil</a>
        @endif

        <br>
        <a href="{{ $deeplink ?? 'koffre://payment/failed' }}" class="btn-outline">
            <i class="fa-solid fa-mobile-screen"></i> Ouvrir dans l'application
        </a>
    </div>
</body>

</html>