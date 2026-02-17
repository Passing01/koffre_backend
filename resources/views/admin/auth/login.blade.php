<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Kofre</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            font-family: 'Inter', sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .input-field {
            transition: all 0.3s ease;
        }

        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            right: -100px;
            animation-delay: 5s;
        }

        .shape:nth-child(3) {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 10%;
            animation-delay: 10s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-50px) rotate(180deg);
            }
        }
    </style>
</head>

<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-card w-full max-w-md rounded-3xl shadow-2xl p-8 relative z-10">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div
                class="w-20 h-20 mx-auto bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                <i class="fas fa-shield-halved text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Espace Admin</h1>
            <p class="text-gray-600">Connectez-vous pour accéder au tableau de bord</p>
        </div>

        <!-- Messages d'erreur -->
        @if($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <div>
                        @foreach($errors->all() as $error)
                            <p class="text-red-700 text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Messages de succès -->
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700 text-sm">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Formulaire de connexion -->
        <form method="POST" action="{{ route('admin.send-otp') }}" class="space-y-6">
            @csrf

            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-phone mr-2 text-purple-600"></i>Numéro de téléphone
                </label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+226 XX XX XX XX"
                    required
                    class="input-field w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500">
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle mr-1"></i>Utilisez le numéro de téléphone admin
                </p>
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-white font-semibold rounded-xl shadow-lg">
                <i class="fas fa-paper-plane mr-2"></i>Envoyer le code OTP
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-lock mr-1"></i>Connexion sécurisée
            </p>
            <p class="text-xs text-gray-400 mt-2">
                Kofre Admin Panel © {{ date('Y') }}
            </p>
        </div>
    </div>
</body>

</html>