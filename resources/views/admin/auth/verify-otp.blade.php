<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification OTP - Kofre Admin</title>
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

        .otp-input {
            width: 3rem;
            height: 3.5rem;
            font-size: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
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
        }

        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            bottom: -100px;
            right: -100px;
            animation-delay: 5s;
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
    </div>

    <div class="login-card w-full max-w-md rounded-3xl shadow-2xl p-8 relative z-10">
        <!-- Header -->
        <div class="text-center mb-8">
            <div
                class="w-20 h-20 mx-auto bg-gradient-to-r from-purple-600 to-pink-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg">
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Vérification OTP</h1>
            <p class="text-gray-600">Entrez le code à 6 chiffres envoyé</p>
        </div>

        <!-- Code OTP en mode test -->
        @if(session('otp_code'))
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-yellow-500 mr-3"></i>
                    <div>
                        <p class="text-yellow-700 text-sm font-semibold">Mode Test</p>
                        <p class="text-yellow-600 text-lg font-bold">Code OTP: {{ session('otp_code') }}</p>
                    </div>
                </div>
            </div>
        @endif

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

        <!-- Formulaire OTP -->
        <form method="POST" action="{{ route('admin.verify-otp.submit') }}" id="otpForm" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-4 text-center">
                    Code de vérification
                </label>
                <div class="flex justify-center gap-2 mb-4">
                    <input type="text" maxlength="1"
                        class="otp-input border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                        data-index="0">
                    <input type="text" maxlength="1"
                        class="otp-input border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                        data-index="1">
                    <input type="text" maxlength="1"
                        class="otp-input border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                        data-index="2">
                    <input type="text" maxlength="1"
                        class="otp-input border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                        data-index="3">
                    <input type="text" maxlength="1"
                        class="otp-input border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                        data-index="4">
                    <input type="text" maxlength="1"
                        class="otp-input border-2 border-gray-200 rounded-xl focus:outline-none focus:border-purple-500"
                        data-index="5">
                </div>
                <input type="hidden" name="otp_code" id="otp_code" value="{{ old('otp_code') }}">
            </div>

            <button type="submit" class="btn-primary w-full py-3 text-white font-semibold rounded-xl shadow-lg">
                <i class="fas fa-check mr-2"></i>Vérifier et se connecter
            </button>
        </form>

        <!-- Lien retour -->
        <div class="mt-6 text-center">
            <a href="{{ route('admin.login') }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la connexion
            </a>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center">
            <p class="text-sm text-gray-500">
                <i class="fas fa-lock mr-1"></i>Connexion sécurisée
            </p>
        </div>
    </div>

    <script>
        // Gestion des inputs OTP
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpCodeInput = document.getElementById('otp_code');
        const form = document.getElementById('otpForm');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;

                if (value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                updateOtpCode();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6);

                pastedData.split('').forEach((char, i) => {
                    if (otpInputs[i]) {
                        otpInputs[i].value = char;
                    }
                });

                updateOtpCode();

                if (pastedData.length === 6) {
                    otpInputs[5].focus();
                }
            });
        });

        function updateOtpCode() {
            const code = Array.from(otpInputs).map(input => input.value).join('');
            otpCodeInput.value = code;
        }

        // Auto-focus sur le premier input
        otpInputs[0].focus();
    </script>
</body>

</html>