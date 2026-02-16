<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cagnotte terminée | Koffre</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .container {
            max-width: 400px;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
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
    </style>
</head>

<body>
    <div class="container">
        <h1>Cagnotte clôturée</h1>
        <p>Cette cagnotte ({{ $cagnotte->title }}) n'accepte plus de contributions. Merci pour votre intérêt !</p>
        <a href="/" class="btn">Retour à l'accueil</a>
    </div>
</body>

</html>