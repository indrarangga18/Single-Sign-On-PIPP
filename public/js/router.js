// Simple Router for SSO Application
class SSORouter {
    constructor() {
        this.routes = {
            '/': this.handleHome.bind(this),
            '/login': this.handleLogin.bind(this),
            '/dashboardsso': this.handleDashboard.bind(this)
        };
        
        this.init();
    }

    init() {
        // Handle initial page load
        window.addEventListener('load', () => {
            this.handleRoute();
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', () => {
            this.handleRoute();
        });

        // Handle navigation clicks
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-route]')) {
                e.preventDefault();
                const route = e.target.getAttribute('data-route');
                this.navigate(route);
            }
        });
    }

    handleRoute() {
        const path = window.location.pathname;
        const handler = this.routes[path] || this.handle404;
        handler();
    }

    navigate(path) {
        window.history.pushState({}, '', path);
        this.handleRoute();
    }

    handleHome() {
        // Show loading screen and redirect based on login status
        this.showPage('loading');
        
        setTimeout(() => {
            if (this.isLoggedIn()) {
                this.navigate('/dashboardsso');
            } else {
                this.navigate('/login');
            }
        }, 2000);
    }

    handleLogin() {
        if (this.isLoggedIn()) {
            // If already logged in, redirect to dashboard
            this.navigate('/dashboardsso');
            return;
        }
        
        this.showPage('login');
        this.initLoginForm();
    }

    handleDashboard() {
        if (!this.isLoggedIn()) {
            // If not logged in, redirect to login
            this.navigate('/login');
            return;
        }
        
        this.showPage('dashboard');
        this.initDashboard();
    }

    handle404() {
        // Redirect to home for unknown routes
        this.navigate('/');
    }

    showPage(pageType) {
        // Hide all pages
        const pages = ['loadingPage', 'loginPage', 'dashboardPage'];
        pages.forEach(pageId => {
            const element = document.getElementById(pageId);
            if (element) {
                element.classList.add('hidden');
                element.classList.remove('fade-in');
            }
        });

        // Show requested page
        let targetPageId;
        switch(pageType) {
            case 'loading':
                targetPageId = 'loadingPage';
                break;
            case 'login':
                targetPageId = 'loginPage';
                break;
            case 'dashboard':
                targetPageId = 'dashboardPage';
                break;
        }
        
        const targetPage = document.getElementById(targetPageId);
        if (targetPage) {
            targetPage.classList.remove('hidden');
            targetPage.classList.add('fade-in');
        }
    }

    isLoggedIn() {
        return sessionStorage.getItem('isLoggedIn') === 'true';
    }

    login(username, password) {
        // Simple demo authentication
        if (username === 'admin' && password === 'admin') {
            sessionStorage.setItem('isLoggedIn', 'true');
            sessionStorage.setItem('username', username);
            sessionStorage.setItem('loginTime', new Date().toISOString());
            return true;
        }
        return false;
    }

    logout() {
        sessionStorage.removeItem('isLoggedIn');
        sessionStorage.removeItem('username');
        sessionStorage.removeItem('loginTime');
        this.navigate('/login');
    }

    initLoginForm() {
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const errorMessage = document.getElementById('errorMessage');

        if (!loginForm) return;

        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            if (!username || !password) {
                this.showError('Silakan masukkan username dan password');
                return;
            }

            // Show loading state
            loginButton.disabled = true;
            loginButton.textContent = 'Memproses...';

            setTimeout(() => {
                if (this.login(username, password)) {
                    this.showSuccess('Login berhasil! Mengalihkan...');
                    setTimeout(() => {
                        this.navigate('/dashboardsso');
                    }, 1000);
                } else {
                    this.showError('Username atau password salah');
                    loginButton.disabled = false;
                    loginButton.textContent = 'Masuk';
                }
            }, 1000);
        });

        // Handle Enter key
        loginForm.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                loginForm.dispatchEvent(new Event('submit'));
            }
        });
    }

    initDashboard() {
        // Update user info
        const username = sessionStorage.getItem('username');
        const currentUserElement = document.getElementById('currentUser');
        if (currentUserElement && username) {
            currentUserElement.textContent = username;
        }

        // Initialize logout button
        const logoutBtn = document.querySelector('.logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => {
                this.logout();
            });
        }

        // Render SSO applications
        this.renderApps();
    }

    renderApps() {
        const appsContainer = document.getElementById('appsContainer');
        if (!appsContainer) return;

        const ssoApps = [
            {
                id: 'dashboard',
                name: 'Dashboard',
                description: 'Dashboard utama untuk monitoring dan analisis data PIPP',
                icon: '📊',
                url: 'https://pipp.kkp.go.id/dashboard-new/pemantauan/dashboard',
                color: '#3B82F6',
                status: 'online'
            },
            {
                id: 'data-entry',
                name: 'Data Entry',
                description: 'Sistem input dan pengelolaan data perikanan',
                icon: '📝',
                url: 'https://pipp.kkp.go.id/entry/webapp/login/?sessi=9d7a21d8-a5ef-11f0-a530-00505692c3ac',
                color: '#10B981',
                status: 'online'
            },
            {
                id: 'pemantauan',
                name: 'Pemantauan',
                description: 'Sistem monitoring dan pengawasan pelabuhan perikanan',
                icon: '👁️',
                url: 'https://pipp.kkp.go.id/dashboard-new/pemantauan/dashboard',
                color: '#F59E0B',
                status: 'online'
            }
        ];

        appsContainer.innerHTML = '';

        ssoApps.forEach((app, index) => {
            const appCard = this.createAppCard(app, index);
            appsContainer.appendChild(appCard);
        });

        // Update statistics
        this.updateStats(ssoApps);
    }

    createAppCard(app, index) {
        const card = document.createElement('div');
        card.className = 'app-card';
        card.style.animationDelay = `${index * 0.1}s`;
        
        card.innerHTML = `
            <div class="app-icon" style="background: ${app.color}">${app.icon}</div>
            <h3 class="app-name">${app.name}</h3>
            <p class="app-description">${app.description}</p>
            <div class="app-status ${app.status === 'online' ? '' : 'offline'}">
                ${app.status === 'online' ? 'Online' : 'Offline'}
            </div>
        `;

        card.addEventListener('click', () => {
            this.redirectToApp(app);
        });

        return card;
    }

    updateStats(apps) {
        const totalAppsElement = document.getElementById('totalApps');
        const activeAppsElement = document.getElementById('activeApps');
        const lastLoginElement = document.getElementById('lastLogin');

        if (totalAppsElement) {
            totalAppsElement.textContent = apps.length;
        }

        if (activeAppsElement) {
            const activeCount = apps.filter(app => app.status === 'online').length;
            activeAppsElement.textContent = activeCount;
        }

        if (lastLoginElement) {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            lastLoginElement.textContent = timeString;
        }
    }

    // Settings Management Functions
    showSettings() {
        const modal = document.getElementById('settingsModal');
        modal.classList.remove('hidden');
    }

    closeSettings() {
        const modal = document.getElementById('settingsModal');
        modal.classList.add('hidden');
    }

    showAppManager() {
        this.closeSettings();
        const modal = document.getElementById('appManagerModal');
        modal.classList.remove('hidden');
        this.populateAppManager();
    }

    closeAppManager() {
        const modal = document.getElementById('appManagerModal');
        modal.classList.add('hidden');
    }

    populateAppManager() {
        const appList = document.getElementById('appList');
        const ssoApps = [
            {
                id: 1,
                name: 'Dashboard PIPP',
                description: 'Dashboard pemantauan dan analisis data PIPP',
                url: 'https://pipp.kkp.go.id/dashboard-new/pemantauan/dashboard',
                icon: '📊',
                color: '#667eea',
                status: 'active'
            },
            {
                id: 2,
                name: 'Data Entry PIPP',
                description: 'Sistem input dan manajemen data PIPP',
                url: 'https://pipp.kkp.go.id/entry/webapp/login/?sessi=9d7a21d8-a5ef-11f0-a530-00505692c3ac',
                icon: '📝',
                color: '#10b981',
                status: 'active'
            },
            {
                id: 3,
                name: 'Pemantauan PIPP',
                description: 'Sistem pemantauan real-time PIPP',
                url: 'https://pipp.kkp.go.id/dashboard-new/pemantauan/dashboard',
                icon: '👁️',
                color: '#f59e0b',
                status: 'active'
            }
        ];

        appList.innerHTML = ssoApps.map(app => `
            <div class="app-manager-item">
                <div class="app-info">
                    <h4>${app.name}</h4>
                    <p>${app.description}</p>
                    <div class="app-url">${app.url}</div>
                </div>
                <div class="app-actions">
                    <button class="edit-btn" onclick="router.editApp(${app.id})" title="Edit">
                        ✏️
                    </button>
                    <button class="delete-btn" onclick="router.deleteApp(${app.id})" title="Hapus">
                        🗑️
                    </button>
                </div>
            </div>
        `).join('');
    }

    showAddAppForm() {
        this.closeAppManager();
        const modal = document.getElementById('addAppModal');
        modal.classList.remove('hidden');
        
        // Setup form handler
        const form = document.getElementById('addAppForm');
        form.onsubmit = (e) => this.handleAddApp(e);
    }

    closeAddAppForm() {
        const modal = document.getElementById('addAppModal');
        modal.classList.add('hidden');
        
        // Reset form
        const form = document.getElementById('addAppForm');
        form.reset();
    }

    handleAddApp(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const newApp = {
            id: Date.now(), // Simple ID generation
            name: formData.get('name'),
            description: formData.get('description'),
            url: formData.get('url'),
            icon: formData.get('icon'),
            color: formData.get('color'),
            status: 'active'
        };

        // Here you would typically save to a backend
        // For now, we'll just show a success message and close the form
        this.showSuccess('Aplikasi berhasil ditambahkan!');
        this.closeAddAppForm();
        
        // Refresh the app manager if it's open
        const appManagerModal = document.getElementById('appManagerModal');
        if (!appManagerModal.classList.contains('hidden')) {
            this.populateAppManager();
        }
        
        // Refresh the dashboard
        this.renderApps();
    }

    editApp(appId) {
        // Placeholder for edit functionality
        alert(`Edit aplikasi dengan ID: ${appId}`);
    }

    deleteApp(appId) {
        if (confirm('Apakah Anda yakin ingin menghapus aplikasi ini?')) {
            // Here you would typically delete from backend
            this.showSuccess('Aplikasi berhasil dihapus!');
            this.populateAppManager();
            this.renderApps();
        }
    }

    showProfile() {
        this.closeSettings();
        alert('Fitur profil akan segera tersedia!');
    }

    showSecurity() {
        this.closeSettings();
        alert('Fitur pengaturan keamanan akan segera tersedia!');
    }

    redirectToApp(app) {
        // Show loading message
        this.showSuccess(`Mengalihkan ke ${app.name}...`);
        
        setTimeout(() => {
            window.open(app.url, '_blank');
        }, 1000);
    }

    showError(message) {
        const errorElement = document.getElementById('errorMessage') || document.getElementById('dashboardErrorMessage');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            errorElement.style.background = '#fee2e2';
            errorElement.style.borderColor = '#fecaca';
            errorElement.style.color = '#dc2626';
            errorElement.style.padding = '12px 16px';
            errorElement.style.borderRadius = '8px';
            errorElement.style.border = '1px solid';
            errorElement.style.marginBottom = '1rem';
        }
    }

    showSuccess(message) {
        const errorElement = document.getElementById('errorMessage') || document.getElementById('dashboardErrorMessage');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            errorElement.style.background = '#dcfce7';
            errorElement.style.borderColor = '#bbf7d0';
            errorElement.style.color = '#16a34a';
            errorElement.style.padding = '12px 16px';
            errorElement.style.borderRadius = '8px';
            errorElement.style.border = '1px solid';
            errorElement.style.marginBottom = '1rem';
        }
    }

    showForgotPassword() {
        alert('Fitur lupa password akan tersedia dalam versi lengkap aplikasi.\\n\\nUntuk demo, gunakan:\\nUsername: admin\\nPassword: password');
    }
}

// Initialize router when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.ssoRouter = new SSORouter();
    
    // Create particles effect
    createParticles();
});

// Particles animation
function createParticles() {
    const particlesContainer = document.querySelector('.particles');
    if (!particlesContainer) return;

    for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        const size = Math.random() * 4 + 2;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        
        particlesContainer.appendChild(particle);
    }
}