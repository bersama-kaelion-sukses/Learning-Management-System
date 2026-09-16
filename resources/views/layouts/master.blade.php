<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS Web')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <!-- Custom CSS -->
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
</head>
<body class="app-layout">
    {{-- Navbar --}}
    <nav class="navbar navbar-expand-lg navbar-light bg-warning fixed-top app-navbar" id="appNavbar">
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between w-100">
                {{-- Logo --}}
                <a class="navbar-brand fw-bold text-dark" href="/dashboard">
                    <img src="{{ asset('assets/img/skbf_logo.png') }}"
                        alt="Company Logo"
                        class="navbar-logo rounded shadow me-2">
                </a>

                {{-- Toggle button (Mobile) --}}
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            @auth
            @php
                $user = Auth::user();
                $mainRole = [$user->role_id];
                $subRoles = is_array($user->sub_role) 
                            ? $user->sub_role 
                            : (json_decode($user->sub_role, true) ?: []);
                $allRoles = array_merge($mainRole, $subRoles);

                $isIT = in_array(1, $allRoles); // role 1 = IT
            @endphp

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="mobile-menu-header d-lg-none">
                    <a class="navbar-brand fw-bold text-dark" href="/dashboard">
                        <img src="{{ asset('assets/img/skbf_logo.png') }}"
                            alt="Company Logo"
                            class="navbar-logo rounded shadow">
                    </a>
                    <button class="btn-close" type="button"
                            data-bs-toggle="collapse" data-bs-target="#navbarNav"
                            aria-controls="navbarNav" aria-label="Close navigation"></button>
                </div>

                <ul class="navbar-nav ms-auto  align-items-lg-center align-items-left">

                    {{-- Learner --}}
                    @if($isIT || array_intersect([4], $allRoles))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" id="learnerDropdown" 
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            🎓 {{ json_lang('Learner') }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">📝 {{ json_lang('Dashboard') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('learner.course') }}">📚 {{ json_lang('My Courses') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('learner.explore') }}">🔍 {{ json_lang('Explore Courses') }}</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Instructor --}}
                    @if($isIT || array_intersect([3], $allRoles))
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" id="instructorDropdown" 
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            👨‍🏫 {{ json_lang('Instructor') }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('instructor.course') }}">📚 {{ json_lang('My Courses') }}</a></li>

                            {{-- 🔹 Takeover Kursus (sekarang di sini) --}}
                            <li><a class="dropdown-item" href="{{ route('instructor.takeover') }}">✏️ {{ json_lang('Takeover Course') }}</a></li>
                        </ul>
                    </li>
                    @endif

                    {{-- Administrator --}}
                    @if ($isIT || in_array(2, $allRoles))
                    <li class="nav-item dropdown mb-4 mb-lg-0">
                        <a class="nav-link dropdown-toggle fw-bold" href="#" id="adminDropdown" 
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            🛠 {{ json_lang('Administrator') }}
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                            {{-- Manajemen Pengguna --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('administrator.user-mgt') }}">
                                    👥 {{ json_lang('User Management') }}
                                </a>
                            </li>

                            {{-- Penugasan Kursus --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('administrator.course-list') }}">
                                    📚 {{ json_lang('Course Assignment') }}
                                </a>
                            </li>

                            {{-- Learner Submission Release --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('administrator.learner-submission-realeased') }}">
                                    📂 {{ json_lang('Learner Submission Release') }}
                                </a>
                            </li>
                                {{-- 🔹 Konsultasi Learner (sekarang di sini) --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('learner.consultation') }}"> 🗣 ️{{ json_lang('Learner Consultation') }} </a>
                            </li>
                            {{-- Laporan --}}
                            <li>
                                <a class="dropdown-item" href="{{ route('administrator.report') }}">
                                    📊 {{ json_lang('HR Report') }}
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    {{-- User Dropdown --}}
                    <li class="nav-item dropdown bg-white p-1 rounded user-nav-item">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="fw-semibold text-dark me-2 text-truncate user-name">
                                {{ Auth::user()->full_name }}
                            </span>
                            <img src="{{ $user->photo_profile ? asset($user->photo_profile) : asset('assets/img/default-profile.png') }}" 
                                alt="Profile" class="rounded-circle border border-2 shadow-sm profile-photo"
                                onerror="this.src='{{ asset('assets/img/default-profile.png') }}'">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{{ route('general.profile') }}">👤 <span>{{ json_lang('Profile') }}</span></a></li>

                            {{-- Approval --}}
                            @if($isIT || array_intersect([2, 3], $allRoles))
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2"
                                href="{{ route('approval.index') }}">
                                    ✅ <span>{{ json_lang('Approval List') }}</span>
                                </a>
                            </li>
                            @endif

                            <li>
                                <a class="dropdown-item" href="#" id="notifDropdown" role="button" data-bs-toggle="modal" data-bs-target="#notifModal">🔔 <span>{{ json_lang('Notifications') }}</span></a>
                            </li>

                            <li><hr class="dropdown-divider"></li>
                            <li class="px-3 py-2">
                                    <small class="text-muted d-block mb-1">
                                        🌐 {{ json_lang('Language') }}
                                    </small>
                                    <select name="languages" id="languages"
                                            class="form-select form-select-sm">
                                        <option value="id" {{ session('locale') === 'id' ? 'selected' : '' }}>
                                            Bahasa Indonesia
                                        </option>
                                        <option value="en" {{ session('locale', 'en') === 'en' ? 'selected' : '' }}>
                                            English (US)
                                        </option>
                                    </select>
                                </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">🚪 {{ json_lang('Logout') }}</button>
                                </form>
                            </li>
                        </ul>
                    </li>

                </ul>
            </div>
            @endauth
        </div>
    </nav>

    <script>
        (() => {
            const navbar = document.getElementById('appNavbar');
            const mobileMenu = document.getElementById('navbarNav');

            if (!navbar) return;

            const updateNavbarHeight = () => {
                document.documentElement.style.setProperty(
                    '--app-navbar-height',
                    `${navbar.offsetHeight}px`
                );
            };

            updateNavbarHeight();
            window.addEventListener('resize', updateNavbarHeight);

            if ('ResizeObserver' in window) {
                new ResizeObserver(updateNavbarHeight).observe(navbar);
            }

            if (mobileMenu) {
                mobileMenu.addEventListener('show.bs.collapse', () => {
                    if (window.innerWidth < 992) {
                        document.body.classList.add('mobile-menu-open');
                        mobileMenu.classList.remove('drawer-visible');

                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                mobileMenu.classList.add('drawer-visible');
                            });
                        });
                    }
                });

                mobileMenu.addEventListener('shown.bs.collapse', () => {
                    if (window.innerWidth < 992) {
                        mobileMenu.classList.add('drawer-visible');
                    }
                });

                mobileMenu.addEventListener('hide.bs.collapse', () => {
                    if (window.innerWidth < 992) {
                        mobileMenu.classList.remove('drawer-visible');
                    }
                });

                mobileMenu.addEventListener('hidden.bs.collapse', () => {
                    document.body.classList.remove('mobile-menu-open');
                    mobileMenu.classList.remove('drawer-visible');
                });

                window.addEventListener('resize', () => {
                    if (window.innerWidth >= 992) {
                        document.body.classList.remove('mobile-menu-open');
                        mobileMenu.classList.remove('drawer-visible');
                    } else if (mobileMenu.classList.contains('show')) {
                        document.body.classList.add('mobile-menu-open');
                        mobileMenu.classList.add('drawer-visible');
                    }
                });
            }
        })();
    </script>

    {{-- Main Content --}}
    <div class="container mb-5 pb-4 app-main-content">
        <!-- Notifikasi -->
        @if(session('success'))
        <div id="alert-success" class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('error'))
        <div id="alert-error" class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @if(session('info'))
        <div id="alert-info" class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        @yield('content')
    </div>

    <!-- Modal Password Expiry -->
    <div class="modal fade" id="passwordExpiryModal" tabindex="-1" aria-hidden="true"  data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content border-danger shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">⚠️ {{ json_lang('Password Will Expire Soon') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="fw-semibold">
                {{ json_lang('Your password will expire in ') }} 
                <span class="text-danger">{{ $user->password_expiry_days }}</span> {{ json_lang('Days') }}
                </p>
                <small class="text-muted">{{ json_lang('If not updated, the system will automatically reset your password.') }}</small>
            </div>
            <div class="modal-footer justify-content-center">
                <form action="{{ route('password.resetbyUser') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning">🔑 {{ json_lang('Reset Password') }}</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ json_lang('Later') }}</button>
            </div>
            </div>
        </div>
    </div>

   <!-- Modal Notifikasi -->
    <div class="modal fade" id="notifModal" tabindex="-1" aria-labelledby="notifModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">🔔 {{ json_lang('Notifications') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-0">
                  <ul class="list-group list-group-flush" style="max-height:450px; overflow-y:auto">
                        @forelse($notifications as $notif)
                        <a href="{{ $notif->redirect_url ?? '#' }}"
                        class="text-decoration-none text-dark w-100"
                        style="display:block; transition:0.2s;border-radius:5px; margin-right:5px"
                        onmouseover="this.style.backgroundColor='var(--theme-secondary)'; this.style.cursor='pointer';"
                        onmouseout="this.style.backgroundColor='';">     
                          <li class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold text-dark">
                                        {{ $notif['message'] }}
                                    </div>
                                    <small class="text-muted">{{ $notif['time'] }}</small>
                                </div>
                                <a href="{{ route('notif.read', ['id' => $notif['notification_id'] ?? $notif['id'] ?? $notif->id ?? $notif['notification_id']]) }}"
                                class="btn btn-outline-secondary btn-sm text-nowrap">
                                {{ json_lang('Mark as Read') }}
                                </a>
                            </li>
                        </a>
                        @empty
                            <li class="list-group-item text-center text-muted">
                                {{ json_lang('No new notifications') }}
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="modal-footer bg-light">
                    <a href="{{ route('notif.readAll') }}" class="btn btn-warning btn-sm"> {{ json_lang('View All') }} </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ json_lang('Close') }} </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="text-center py-3 border-top bg-light shadow-sm fixed-bottom">
        <small class="text-muted">
            © {{ date('Y') }} Muhammad Iqbal Fattah (Portofolio) (v1.4.4)
        </small>
    </div>

    <!-- 🔔 Toast Notification -->
    <div class="position-fixed top-0 mt-2 end-0 p-3" style="z-index: 9999">
    <div id="newNotifToast" class="toast align-items-center text-bg-secondary border-0 shadow-sm" role="alert">
        <div class="d-flex">
        <div class="toast-body fw-semibold">
            🔔 {{ json_lang('New notification available!') }}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // ==========================================================
            // 🧩 1️⃣ Auto-close alert biasa
            // ==========================================================
            document.querySelectorAll('.alert').forEach(alertEl => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alertEl);
                    bsAlert.close();
                }, 3000);
            });

            // ==========================================================
            // 🔐 2️⃣ Password expiry reminder
            // ==========================================================
            const expiryDays = {{ $user->password_expiry_days ?? 'null' }};
            if (expiryDays !== null && expiryDays <= 7) {
                const key = "shownExpiryModal";
                const today = new Date().toISOString().split("T")[0];
                if (localStorage.getItem(key) !== today) {
                    const modal = new bootstrap.Modal(document.getElementById('passwordExpiryModal'));
                    modal.show();
                    localStorage.setItem(key, today);
                }
            }

            // ==========================================================
            // 🔔 3️⃣ Toast Notification — Real-time polling
            // ==========================================================
            const toastEl = document.getElementById('newNotifToast');
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            const lastNotifKey = "lastNotifShownId";
            let lastNotifId = localStorage.getItem(lastNotifKey);
            let latestFromServer = "{{ $latestNotifId ?? '' }}";

            // 🟢 Tampilkan toast di load awal jika ada notif baru
            if (latestFromServer && (!lastNotifId || Number(latestFromServer) > Number(lastNotifId))) {
                toast.show();
                localStorage.setItem(lastNotifKey, latestFromServer);
            }

            // 🖱️ Klik Toast → Buka Modal Notifikasi
            toastEl.addEventListener("click", (e) => {
                if (e.target.classList.contains("btn-close")) return;
                const notifModal = document.getElementById("notifModal");
                if (notifModal) {
                    const modal = new bootstrap.Modal(notifModal);
                    modal.show();
                }
            });

            // ==========================================================
            // 🔁 Polling ke server setiap 15 detik
            // ==========================================================
            async function checkNotifications() {
                try {
                    const res = await fetch("/notif/check-latest");
                    const data = await res.json();

                    // console.log("📡 Polled result:", data);

                    // ✅ Format fleksibel agar bisa baca dari latestId / latest
                    const newId = data.latest?.id || data.latestId || null;
                    const message = data.latest?.message || data.message || "Ada notifikasi baru!";
                    const time = data.latest?.time || data.time || new Date().toLocaleTimeString();

                    if (data.hasNew && newId && (!lastNotifId || Number(newId) > Number(lastNotifId))) {
                        lastNotifId = newId;
                        localStorage.setItem(lastNotifKey, newId);

                        toastEl.querySelector('.toast-body').innerHTML = `
                            🔔 ${message}<br>
                            <small class="text-light opacity-75">${time}</small>
                        `;
                        toast.show();
                    }
                } catch (err) {
                    console.warn("⚠️ Failed to check notifications:", err);
                }
            }

            setInterval(checkNotifications, 15000);

            // ======================================================
            // 🧩 Auto-remove notification item after marking as read
            // ======================================================
            document.querySelectorAll('#notifModal a.btn-outline-secondary').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const href = btn.getAttribute('href');
                    const notifItem = btn.closest('li');

                    try {
                        const res = await fetch(href, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });

                        if (res.ok) {
                            notifItem.style.transition = 'opacity 0.3s ease';
                            notifItem.style.opacity = '0';
                            setTimeout(() => notifItem.remove(), 300);
                        } else {
                            console.warn("Gagal update notifikasi:", await res.text());
                        }
                    } catch (err) {
                        console.error("❌ Error saat tandai notifikasi:", err);
                    }
                });
            });
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script> window.defaultProfileImgUrl = "{{ asset('assets/img/defaultPic.jpeg') }}";</script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dayjs@1/plugin/relativeTime.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <script>
        dayjs.extend(window.dayjs_plugin_relativeTime);
    </script>
    <script>
        document.getElementById('languages')?.addEventListener('change', function () {
            if (!this.value) return;
            window.location.href = `/lang/${this.value}`;
        });
    </script>
    <script>
        window.currentUserId = {{ auth()->user()->user_id ?? 'null' }};
    </script>
    <script type="module" src="{{ asset('assets/js/main.js') }}"></script>
    @stack('scripts')
</body>
</html>
