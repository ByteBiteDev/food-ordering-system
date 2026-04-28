document.addEventListener('DOMContentLoaded', () => {
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const applyIcon = () => {
            const theme = document.documentElement.getAttribute('data-theme') || 'light';
            themeToggle.innerHTML = theme === 'dark'
                ? '<i class="fas fa-sun"></i>'
                : '<i class="fas fa-moon"></i>';
        };

        applyIcon();
        themeToggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
            applyIcon();
        });
    }

    const navbar = document.getElementById('navbar');
    
    // 1. Navbar Scroll Effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // 2. Add to Cart AJAX
    window.addToCart = async (foodId, quantity = 1) => {
        try {
            const formData = new FormData();
            formData.append('food_id', foodId);
            formData.append('quantity', quantity);
            
            // Get CSRF token from a hidden input if exists, or global var
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                showToast('Added to cart!', 'success');
                // Optional: Update cart count badge in UI
                const badges = document.querySelectorAll('.badge');
                badges.forEach(badge => {
                    const currentCount = parseInt(badge.textContent) || 0;
                    badge.textContent = currentCount + quantity;
                });
            } else {
                throw new Error('Failed to add');
            }
        } catch (error) {
            showToast('Could not add to cart.', 'error');
        }
    };


    // 2. Favorite Toggling (AJAX)
    window.toggleFavorite = async (foodId, element) => {
        const btn = (element instanceof HTMLElement) ? element : null;
        if (!btn) return;

        try {
            const formData = new FormData();
            formData.append('food_id', String(foodId));

            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            const response = await fetch('ajax_favorite.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data?.success) {
                if (data?.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                showToast(data?.message || 'Failed to update favorites.', 'error');
                return;
            }

            const isFavorited = Boolean(data.favorited);
            btn.classList.toggle('active', isFavorited);
            const icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fas', isFavorited);
                icon.classList.toggle('far', !isFavorited);
            }

            showToast(isFavorited ? 'Added to favorites.' : 'Removed from favorites.', 'success');
        } catch (error) {
            showToast('Failed to update favorites.', 'error');
        }
    };

    // 3. Simple Toast Notification System
    window.showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; bottom: 30px; right: 30px; 
            padding: 1rem 2rem; border-radius: 12px; background: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            z-index: 10000; border-left: 5px solid ${type === 'success' ? '#10b981' : '#ef4444'};
            font-weight: 600; display: flex; align-items: center; gap: 0.5rem;
            animation: slideIn 0.3s ease-out;
        `;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}" style="color: ${type === 'success' ? '#10b981' : '#ef4444'}"></i> ${message}`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // Add Keyframes for Toasts
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }
    `;
    document.head.appendChild(style);

    // 4. Menu (food.php) sidebar off-canvas toggle (mobile/tablet)
    const menuSidebar = document.getElementById('menuSidebar');
    const menuSidebarToggle = document.getElementById('menuSidebarToggle');
    const menuSidebarOverlay = document.getElementById('menuSidebarOverlay');

    const setMenuSidebarOpen = (isOpen) => {
        if (!menuSidebar || !menuSidebarToggle || !menuSidebarOverlay) return;
        menuSidebar.classList.toggle('open', isOpen);
        menuSidebarOverlay.classList.toggle('open', isOpen);
        menuSidebarToggle.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('no-scroll', isOpen);
    };

    if (menuSidebar && menuSidebarToggle && menuSidebarOverlay) {
        menuSidebarToggle.addEventListener('click', () => {
            setMenuSidebarOpen(!menuSidebar.classList.contains('open'));
        });

        menuSidebarOverlay.addEventListener('click', () => setMenuSidebarOpen(false));

        menuSidebar.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (!link) return;
            if (window.innerWidth <= 1024) setMenuSidebarOpen(false);
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') setMenuSidebarOpen(false);
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) setMenuSidebarOpen(false);
        });
    }

    // 5. Form validation enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fill in all required fields.', 'error');
            }
        });
    });

    // 6. Image lazy loading optimization
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
});
