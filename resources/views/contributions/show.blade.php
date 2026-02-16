<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soutenir : {{ $cagnotte->title }} | Koffre</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --second: #ec4899;
            --dark: #0f172a;
            --light: #f8fafc;
            --glass: rgba(255, 255, 255, 0.8);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            margin-bottom: 30px;
        }

        .logo {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--second));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .description {
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #475569;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            background: white;
            font-family: inherit;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
            margin-bottom: 15px;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.5);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: #f5f3ff;
            transform: translateY(-2px);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #94a3b8;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e2e8f0;
        }

        .divider span {
            padding: 0 15px;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fee2e2;
        }

        .cagnotte-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 25px;
            background: rgba(99, 102, 241, 0.05);
            padding: 15px;
            border-radius: 15px;
        }

        .stat-item span {
            display: block;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .stat-item strong {
            font-size: 18px;
            color: var(--primary);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo-container">
            <span class="logo">Koffre.</span>
        </div>

        <h1>{{ $cagnotte->title }}</h1>
        <p class="description">{{ $cagnotte->description ?: 'Aucune description fournie.' }}</p>

        <div class="cagnotte-stats">
            <div class="stat-item">
                <span>Collecté</span>
                <strong>{{ number_format($cagnotte->current_amount, 0, ',', ' ') }} XOF</strong>
            </div>
            @if($cagnotte->target_amount)
                <div class="stat-item">
                    <span>Objectif</span>
                    <strong>{{ number_format($cagnotte->target_amount, 0, ',', ' ') }} XOF</strong>
                </div>
            @endif
        </div>

        @if(session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('cagnotte.web_contribute', $cagnotte->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="contributor_name">Votre Nom complet</label>
                <input type="text" id="contributor_name" name="contributor_name" placeholder="Ex: Jean Dupont" required>
            </div>

            <div class="form-group">
                <label for="amount">Montant (XOF)</label>
                <input type="number" id="amount" name="amount" placeholder="Min. 100" min="100" required>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-credit-card mr-2"></i> Contribuer en ligne
            </button>
        </form>

        <div class="divider">
            <span>OU</span>
        </div>

        <a href="koffre://c/{{ $cagnotte->id }}" class="btn btn-outline" id="appLink">
            <i class="fa-solid fa-mobile-screen-button mr-2"></i> Ouvrir dans l'App Koffre
        </a>
    </div>

    <script>
        // Fallback logic for app link if needed, but custom scheme is usually fine
        document.getElementById('appLink').addEventListener('click', function (e) {
            // If the app doesn't open within 2s, we could redirect to store
            // setTimeout(function() {
            //    window.location.href = "https://play.google.com/store/apps/details?id=com.koffre";
            // }, 2500);
        });
    </script>
</body>

</html>