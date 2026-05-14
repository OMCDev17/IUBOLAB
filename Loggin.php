<?php
require __DIR__ . '/api/auth.php';

// If already logged in, redirect to the correct dashboard.
$user = getSessionUser();
if ($user) {
    $role = strtolower($user['rol'] ?? '');
    $mapping = [
        'admin' => 'admin',
        'supervisor' => 'coordinador',
        'coordinador' => 'coordinador',
        'seguridad' => 'seguridad',
        'empleado' => 'usuario',
    ];
    $target = $mapping[$role] ?? 'usuario';
    header("Location: $target");
    exit;
}
?>

<!DOCTYPE html>

<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>GestIUBO - Acceso</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="icon" href="/iubolab/imagenes/icono_circulo.png" type="image/png">
    <link rel="icon" type="image/png" sizes="32x32" href="/iubolab/imagenes/icono_circulo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/iubolab/imagenes/icono_circulo.png">
    <link rel="apple-touch-icon" href="/iubolab/imagenes/icono_circulo.png">
    <link href="https://fonts.googleapis.com/css2?family=Argentum+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        lab: {
                            primary: '#0f172a',
                            secondary: '#334155',
                            accent: '#5c068c',
                            accentLight: '#f3e8ff',
                            accentHover: '#4a0570',
                            success: '#10b981',
                            warning: '#f59e0b',
                            bg: '#f8fafc'
                        }
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        body {
            background-color: #f8fafc;
            font-family: 'Argentum Sans', system-ui, -apple-system, sans-serif;
            overscroll-behavior: none;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-center p-3 md:p-6 bg-background-light dark:bg-background-dark">
    <main class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col relative" data-purpose="main-terminal-card">
        <section class="p-5 md:p-6 bg-white dark:bg-slate-900 flex flex-col relative" data-purpose="login-panel">
            <div class="absolute top-5 right-5 flex items-center gap-2 text-sm font-medium">
                <span class="text-lab-accent cursor-pointer border-b-2 border-lab-accent pb-0.5" id="lang-es" onclick="switchLanguage('es')">ES</span>
                <span class="text-slate-300">|</span>
                <span class="text-slate-400 hover:text-lab-primary cursor-pointer transition-colors border-b-2 border-transparent hover:border-slate-400 pb-0.5" id="lang-en" onclick="switchLanguage('en')">EN</span>
            </div>
            <script>
                function switchLanguage(lang) {
                    const texts = {
                        es: {
                            title: 'Recursos Humanos',
                            desc: 'Ingresa tus credenciales para acceder.',
                            userLabel: 'Usuario o Correo Electrónico',
                            userPlaceholder: 'ej. usuario_123',
                            passLabel: 'Contraseña',
                            forgot: '¿Olvidaste tu contraseña?',
                            remember: 'Recordar mi sesión',
                            signIn: 'Iniciar sesión',
                            noAccount: '¿No tienes una cuenta?',
                            createAccount: 'Crear Cuenta de Miembro',
                            alertForgot: 'Se ha enviado un correo electrónico de recuperación a su dirección registrada.',
                            alertCreate: 'Solicitud de registro enviada al administrador.',
                            signingIn: 'Iniciando sesión...'
                        },
                        en: {
                            title: 'Human Resources',
                            desc: 'Enter your credentials to access.',
                            userLabel: 'Username or Email',
                            userPlaceholder: 'e.g. employee_123',
                            passLabel: 'Password',
                            forgot: 'Forgot password?',
                            remember: 'Remember session',
                            signIn: 'Sign In',
                            noAccount: "Don't have an account?",
                            createAccount: 'Create Member Account',
                            alertForgot: 'A recovery email has been sent to your registered address.',
                            alertCreate: 'Registration request sent to the administrator.',
                            signingIn: 'Signing in...'
                        }
                    };

                    const t = texts[lang];

                    // Update Elements
                    document.querySelector('h2').textContent = t.title;
                    document.querySelector('h2 + p').textContent = t.desc;
                    document.querySelector('label[for="username"]').textContent = t.userLabel;
                    document.getElementById('username').placeholder = t.userPlaceholder;
                    document.querySelector('label[for="password"]').textContent = t.passLabel;
                    const forgotLink = document.getElementById('forgotLink');
                    if (forgotLink) {
                        forgotLink.textContent = t.forgot;
                        // Always send the user to the password change flow
                        forgotLink.href = 'recuperacion.html';
                        forgotLink.onclick = null;
                    }
                    const rememberLabel = document.querySelector('label[for="remember"]');
                    if (rememberLabel) rememberLabel.textContent = t.remember;
                    const signInBtn = document.querySelector('button[type="submit"] span:first-child');
                    if (signInBtn) signInBtn.textContent = t.signIn;
                    const loginButtonText = document.getElementById('loginButtonText');
                    if (loginButtonText) loginButtonText.textContent = t.signIn;

                    const footerP = document.querySelector('.mt-12.pt-8 p');
                    if (footerP) footerP.innerHTML = `${t.noAccount} <a class="text-lab-accent font-bold hover:underline" href="registro">${t.createAccount}</a>`;

                    // Update Language UI Toggle
                    const esBtn = document.getElementById('lang-es');
                    const enBtn = document.getElementById('lang-en');

                    const setActive = (btn) => {
                        if (!btn) return;
                        btn.classList.add('text-lab-accent', 'border-lab-accent');
                        btn.classList.remove('text-slate-400', 'border-transparent');
                    };

                    const setInactive = (btn) => {
                        if (!btn) return;
                        btn.classList.add('text-slate-400', 'border-transparent');
                        btn.classList.remove('text-lab-accent', 'border-lab-accent');
                    };

                    if (lang === 'es') {
                        setActive(esBtn);
                        setInactive(enBtn);
                    } else {
                        setActive(enBtn);
                        setInactive(esBtn);
                    }
                }

                // Ensure the UI starts with the correct language highlighted
                window.addEventListener('DOMContentLoaded', () => {
                    switchLanguage('es');
                });
            </script>
            <div class="mt-5 mb-6 text-center">
                <img alt="Universidad de La Laguna Logo" class="h-auto w-56 md:w-64 mx-auto mb-5" src="/iubolab/imagenes/instituto-biorganica-agonzalez-original.png" />
                <h2 class="text-2xl md:text-3xl font-bold text-lab-primary mb-1">Recursos Humanos</h2>
                <p class="text-slate-500">Ingresa tus credenciales para acceder.</p>
            </div>
            <form id="loginForm" class="space-y-4 max-w-md mx-auto w-full" onsubmit="handleLogin(event)">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-700 block" for="username">Usuario o Correo Electronico</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">person</span>
                        <input class="w-full pl-12 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-lab-accent focus:border-transparent outline-none transition-all placeholder:text-slate-400" id="username" name="username" placeholder="ej. User_123" type="text" />
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-semibold text-slate-700" for="password">Contraseña</label>
                        <a id="forgotLink" class="text-xs font-bold text-lab-accent hover:text-lab-accentHover transition-colors" href="recuperacion.html">¿Olvidaste tu contraseña?</a>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">lock</span>
                        <input class="w-full pl-12 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-lab-accent focus:border-transparent outline-none transition-all placeholder:text-slate-400" id="password" name="password" placeholder="********" type="password" />
                    </div>
                </div>
                <div class="flex items-center">
                    <input class="w-4 h-4 text-lab-accent border-slate-300 rounded focus:ring-lab-accent" id="remember" type="checkbox" />
                    <label class="ml-2 text-sm text-slate-600 cursor-pointer" for="remember">Recordar mi sesión</label>
                </div>
                <button id="loginButton" class="w-full py-3 bg-lab-accent text-white font-bold rounded-2xl hover:bg-lab-accentHover shadow-lg shadow-purple-100 transition-all flex items-center justify-center gap-2 group" type="submit">
                    <span id="loginButtonText">Iniciar sesión</span>
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">login</span>
                </button>
                <p id="loginError" class="text-sm text-red-600 hidden mt-2"></p>
            </form>
            <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                <p class="text-slate-500 text-sm">
                    ¿No tienes una cuenta?
                    <a class="text-lab-accent font-bold hover:underline" href="registro">Crear Cuenta de Miembro</a>
                </p>
            </div>
        </section>
    </main>
    <script>
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const loginButtonText = document.getElementById('loginButtonText');
        const loginError = document.getElementById('loginError');

        function setLoading(isLoading) {
            if (!loginButton) return;
            loginButton.disabled = isLoading;
            loginButton.classList.toggle('opacity-80', isLoading);
            loginButton.classList.toggle('cursor-not-allowed', isLoading);
            loginButtonText.textContent = isLoading ? 'Iniciando sesión...' : 'Iniciar sesión';
        }

        async function handleLogin(event) {
            event.preventDefault();
            loginError.classList.add('hidden');

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;

            if (!username || !password) {
                loginError.textContent = 'Por favor, introduce usuario y contraseña.';
                loginError.classList.remove('hidden');
                return;
            }

            setLoading(true);

            try {
                const resp = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        username,
                        password
                    }),
                });
                const result = await resp.json();

                if (!resp.ok) {
                    loginError.textContent = result.error || 'Usuario o contraseña incorrectos.';
                    loginError.classList.remove('hidden');
                    return;
                }

                // Redirect based on role (from backend)
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.href = 'usuario';
                }
            } catch (err) {
                console.error(err);
                loginError.textContent = 'Error de red. Intenta de nuevo.';
                loginError.classList.remove('hidden');
            } finally {
                setLoading(false);
            }
        }
    </script>
    <!-- Footer -->
    <footer class="text-center py-6 text-slate-500 text-sm">
        © 2026 GestIUBO. Todos los derechos reservados / All rights reserved.
    </footer>
</body>

</html>

