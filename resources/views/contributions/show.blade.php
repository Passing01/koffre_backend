<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soutenir : {{ $cagnotte->title }} | Koffre</title>
    <meta name="description"
        content="{{ Str::limit($cagnotte->description, 160) ?: 'Participez à la cagnotte ' . $cagnotte->title . ' sur Koffre.' }}">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --second: #ec4899;
            --dark: #0f172a;
            --mid: #334155;
            --muted: #64748b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --glass: rgba(255, 255, 255, 0.88);
            --glass-dim: rgba(255, 255, 255, 0.5);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            color: var(--dark);
            background: #0f172a;
        }

        /* ── Hero Background ── */
        .hero {
            position: relative;
            min-height: 320px;
            overflow: hidden;
            display: flex;
            align-items: flex-end;
        }

        .hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            filter: brightness(0.55);
            transition: transform 8s ease;
        }

        .hero-bg.no-image {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
            filter: brightness(0.85);
        }

        .hero:hover .hero-bg {
            transform: scale(1.03);
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 20%, rgba(0, 0, 0, 0.7) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            padding: 40px 30px 30px;
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            color: white;
            border-radius: 50px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-title {
            font-size: clamp(22px, 5vw, 34px);
            font-weight: 800;
            color: white;
            line-height: 1.2;
            margin-bottom: 10px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .hero-creator {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.75);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hero-creator i {
            font-size: 12px;
        }

        /* ── Main Layout ── */
        .main-wrap {
            max-width: 760px;
            margin: 0 auto;
            padding: 0 16px 60px;
        }

        /* ── Stats Bar ── */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: -24px;
            margin-bottom: 24px;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 500px) {
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 14px 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            animation: slideUp 0.5s ease-out both;
        }

        .stat-card:nth-child(1) {
            animation-delay: 0.05s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 0.15s;
        }

        .stat-card:nth-child(4) {
            animation-delay: 0.2s;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-icon {
            font-size: 18px;
            margin-bottom: 4px;
            background: linear-gradient(135deg, var(--primary), var(--second));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-value {
            font-size: 17px;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 3px;
        }

        .stat-label {
            font-size: 10px;
            color: var(--muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Card ── */
        .card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--border);
            animation: slideUp 0.5s ease-out 0.3s both;
        }

        .card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--mid);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title i {
            color: var(--primary);
            font-size: 16px;
        }

        /* ── Progress ── */
        .description-text {
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 20px;
        }

        .progress-wrap {
            margin-bottom: 8px;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 99px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: linear-gradient(to right, var(--primary), var(--second));
            transition: width 1s ease;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .progress-info strong {
            color: var(--primary);
            font-size: 15px;
        }

        /* ── Deadline badge ── */
        .deadline-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
            border-radius: 50px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 14px;
            border: 1px solid #fbbf24;
        }

        .deadline-badge.expired {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-color: #f87171;
        }

        /* ── Form ── */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
            color: var(--mid);
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 13px 16px;
            border-radius: 12px;
            border: 2px solid var(--border);
            background: var(--light);
            font-family: inherit;
            font-size: 15px;
            transition: all 0.3s;
            color: var(--dark);
            outline: none;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 90px;
        }

        /* ── Buttons ── */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 15px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.25s;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(79, 70, 229, 0.45);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            margin-top: 12px;
        }

        .btn-outline:hover {
            background: #f5f3ff;
            transform: translateY(-2px);
        }

        .btn-ghost {
            background: var(--light);
            color: var(--mid);
            border: 2px solid var(--border);
            font-size: 13px;
            padding: 10px 16px;
            width: auto;
            border-radius: 10px;
        }

        .btn-ghost:hover {
            background: var(--border);
        }

        /* ── Alert ── */
        .alert {
            padding: 13px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* ── Comments ── */
        .comments-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .comment-item {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .comment-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--second));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .comment-avatar.reply-avatar {
            width: 30px;
            height: 30px;
            font-size: 11px;
        }

        .comment-body-wrap {
            flex: 1;
        }

        .comment-bubble {
            background: var(--light);
            border-radius: 14px 14px 14px 4px;
            padding: 12px 16px;
            border: 1px solid var(--border);
        }

        .comment-author {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .comment-text {
            font-size: 14px;
            color: var(--mid);
            line-height: 1.6;
        }

        .comment-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted);
        }

        .reply-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 0;
            font-family: inherit;
        }

        .reply-btn:hover {
            text-decoration: underline;
        }

        .replies-wrap {
            padding-left: 20px;
            border-left: 2px solid var(--border);
            margin-top: 10px;
        }

        .reply-item {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        /* ── Reply Form (hidden by default) ── */
        .reply-form {
            display: none;
            margin-top: 10px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .reply-form.active {
            display: block;
        }

        .reply-form input,
        .reply-form textarea {
            font-size: 13px;
            padding: 10px 14px;
            margin-bottom: 8px;
        }

        .empty-comments {
            text-align: center;
            padding: 30px;
            color: var(--muted);
            font-size: 14px;
        }

        .empty-comments i {
            font-size: 36px;
            margin-bottom: 10px;
            display: block;
            background: linear-gradient(135deg, var(--primary), var(--second));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            margin: 10px 0 20px;
            color: var(--muted);
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            padding: 0 14px;
        }

        /* ── Logo ── */
        .logo-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: white;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 5px 12px;
            font-weight: 700;
            text-decoration: none;
            margin-bottom: 20px;
        }

        .logo-text {
            background: linear-gradient(to right, #a5b4fc, #f9a8d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>
    <!-- ── Hero Section ── -->
    <div class="hero">
        <div class="hero-bg {{ $cagnotte->background_image_path ? '' : 'no-image' }}"
            @if($cagnotte->background_image_path) style="background-image: url('{{ $cagnotte->background_image_url }}')"
            @endif></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <a href="/" class="logo-pill">
                <i class="fa-solid fa-vault"></i>
                <span class="logo-text">Koffre</span>
            </a>
            <div class="hero-tag">
                <i class="fa-solid fa-hand-holding-heart"></i>
                Cagnotte {{ $cagnotte->visibility === 'public' ? 'Publique' : 'Privée' }}
            </div>
            <h1 class="hero-title">{{ $cagnotte->title }}</h1>
            @if($cagnotte->user)
                <p class="hero-creator">
                    <i class="fa-solid fa-user-circle"></i>
                    Créé par <strong
                        style="color:white; margin-left:4px">{{ $cagnotte->user->fullname ?? $cagnotte->user->phone }}</strong>
                </p>
            @endif
        </div>
    </div>

    <!-- ── Stats Bar ── -->
    <div class="main-wrap">
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                @if($stats['is_expired'])
                    <div class="stat-value">Terminée</div>
                    <div class="stat-label">Durée</div>
                @elseif($stats['remaining_days'] > 0)
                    <div class="stat-value">{{ $stats['remaining_days'] }}j {{ $stats['remaining_hours'] }}h</div>
                    <div class="stat-label">Restants</div>
                @else
                    <div class="stat-value">{{ $stats['remaining_hours'] }}h</div>
                    <div class="stat-label">Restantes</div>
                @endif
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                <div class="stat-value">{{ $stats['contributors_count'] }}</div>
                <div class="stat-label">Contributeurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-heart"></i></div>
                <div class="stat-value">{{ $stats['likes_count'] }}</div>
                <div class="stat-label">J'aimes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-comment"></i></div>
                <div class="stat-value">{{ $stats['comments_count'] }}</div>
                <div class="stat-label">Commentaires</div>
            </div>
        </div>

        <!-- ── Progression ── -->
        <div class="card">
            <p class="description-text">{{ $cagnotte->description ?: 'Aucune description fournie.' }}</p>

            <div class="progress-wrap">
                @php
                    $pct = $cagnotte->target_amount > 0
                        ? min(100, ($cagnotte->current_amount / $cagnotte->target_amount) * 100)
                        : 100;
                @endphp
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $pct }}%"></div>
                </div>
                <div class="progress-info">
                    <span><strong>{{ number_format($cagnotte->current_amount, 0, ',', ' ') }} XOF</strong>
                        collectés</span>
                    @if($cagnotte->target_amount)
                        <span>Objectif : {{ number_format($cagnotte->target_amount, 0, ',', ' ') }} XOF</span>
                    @endif
                </div>
            </div>

            @if($cagnotte->ends_at)
                <div class="deadline-badge {{ $stats['is_expired'] ? 'expired' : '' }}">
                    <i class="fa-solid fa-calendar-days"></i>
                    @if($stats['is_expired'])
                        Cagnotte terminée le {{ $cagnotte->ends_at->format('d/m/Y') }}
                    @else
                        Clôture le {{ $cagnotte->ends_at->format('d M Y à H\hi') }}
                    @endif
                </div>
            @endif
        </div>

        <!-- ── Formulaire de contribution ── -->
        <div class="card" id="contribute-section">
            <div class="card-title">
                <i class="fa-solid fa-credit-card"></i>
                Faire un don
            </div>

            @if(session('error'))
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    {{ session('error') }}
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('cagnotte.web_contribute', $cagnotte->id) }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="contributor_name">Votre Nom complet</label>
                    <input type="text" id="contributor_name" name="contributor_name" placeholder="Ex: Jean Dupont"
                        value="{{ old('contributor_name') }}" required>
                </div>
                <div class="form-group">
                    <label for="amount">Montant (XOF)</label>
                    <input type="number" id="amount" name="amount" placeholder="Minimum 100 XOF" min="100"
                        value="{{ old('amount') }}" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-lock"></i>
                    Contribuer en ligne
                </button>
            </form>

            <div class="divider"><span>OU</span></div>

            <a href="koffre://c/{{ $cagnotte->id }}" class="btn btn-outline" id="appLink">
                <i class="fa-solid fa-mobile-screen-button"></i>
                Ouvrir dans l'App Koffre
            </a>
        </div>

        <!-- ── Section Commentaires ── -->
        <div class="card" id="comments-section">
            <div class="comments-header">
                <div class="card-title" style="margin-bottom:0">
                    <i class="fa-solid fa-comments"></i>
                    Commentaires <span
                        style="color:var(--muted);font-weight:500;font-size:13px">({{ $stats['comments_count'] }})</span>
                </div>
            </div>

            <!-- Formulaire d'ajout de commentaire -->
            <form action="{{ route('cagnotte.web_comment', $cagnotte->id) }}" method="POST" style="margin-bottom:28px">
                @csrf
                <div class="form-group">
                    <label for="comment_name">Votre nom</label>
                    <input type="text" id="comment_name" name="name" placeholder="Ex: Marie Diallo"
                        value="{{ old('name') }}" required>
                </div>
                <div class="form-group" style="margin-bottom:12px">
                    <label for="comment_body">Votre message</label>
                    <textarea id="comment_body" name="body" placeholder="Écrivez votre commentaire ici..."
                        required>{{ old('body') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="padding:12px">
                    <i class="fa-solid fa-paper-plane"></i>
                    Publier le commentaire
                </button>
            </form>

            <!-- Liste des commentaires -->
            @if($comments->isEmpty())
                <div class="empty-comments">
                    <i class="fa-regular fa-comments"></i>
                    Soyez le premier à laisser un commentaire !
                </div>
            @else
                @foreach($comments as $comment)
                    <div class="comment-item">
                        <div class="comment-avatar">
                            {{ strtoupper(substr($comment->author_name, 0, 1)) }}
                        </div>
                        <div class="comment-body-wrap">
                            <div class="comment-bubble">
                                <div class="comment-author">{{ $comment->author_name }}</div>
                                <div class="comment-text">{{ $comment->body }}</div>
                            </div>
                            <div class="comment-meta">
                                <span><i class="fa-regular fa-clock"></i> {{ $comment->time_ago }}</span>
                                <button class="reply-btn" onclick="toggleReplyForm({{ $comment->id }})">
                                    <i class="fa-solid fa-reply"></i> Répondre
                                </button>
                            </div>

                            <!-- Formulaire de réponse -->
                            <div class="reply-form" id="reply-form-{{ $comment->id }}">
                                <form action="{{ route('cagnotte.web_comment', $cagnotte->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                    <input type="text" name="name" placeholder="Votre nom" required>
                                    <textarea name="body" placeholder="Votre réponse..." required
                                        style="min-height:70px"></textarea>
                                    <div style="display:flex;gap:8px">
                                        <button type="submit" class="btn btn-primary"
                                            style="padding:10px 18px;font-size:13px;width:auto">
                                            <i class="fa-solid fa-paper-plane"></i> Répondre
                                        </button>
                                        <button type="button" class="btn-ghost btn"
                                            onclick="toggleReplyForm({{ $comment->id }})">
                                            Annuler
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Réponses -->
                            @if($comment->replies->isNotEmpty())
                                <div class="replies-wrap">
                                    @foreach($comment->replies as $reply)
                                        <div class="reply-item">
                                            <div class="comment-avatar reply-avatar">
                                                {{ strtoupper(substr($reply->author_name, 0, 1)) }}
                                            </div>
                                            <div class="comment-body-wrap">
                                                <div class="comment-bubble" style="border-radius:12px 12px 12px 4px">
                                                    <div class="comment-author">{{ $reply->author_name }}</div>
                                                    <div class="comment-text">{{ $reply->body }}</div>
                                                </div>
                                                <div class="comment-meta">
                                                    <span><i class="fa-regular fa-clock"></i> {{ $reply->time_ago }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <script>
        function toggleReplyForm(commentId) {
            const form = document.getElementById('reply-form-' + commentId);
            form.classList.toggle('active');
            if (form.classList.contains('active')) {
                form.querySelector('input[name="name"]').focus();
            }
        }

        // Smooth progress bar animation on load
        document.addEventListener('DOMContentLoaded', function () {
            const fills = document.querySelectorAll('.progress-fill');
            fills.forEach(function (fill) {
                const target = fill.style.width;
                fill.style.width = '0';
                setTimeout(function () { fill.style.width = target; }, 300);
            });
        });
    </script>
</body>

</html>