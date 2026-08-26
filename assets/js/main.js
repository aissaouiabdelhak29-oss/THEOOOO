/**
 * Yuki Movie Streaming Platform - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Navigation Toggle
    const mobileToggle = document.getElementById('mobileToggle');
    const navLinks = document.getElementById('navLinks');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            const icon = this.querySelector('i');
            if (navLinks.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }

    // Flash Message Auto Dismiss
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.opacity = '0';
            flashMessage.style.transform = 'translateX(-50%) translateY(-20px)';
            setTimeout(() => flashMessage.remove(), 400);
        }, 4500);
    }

    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(15, 15, 26, 0.98)';
                navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.3)';
            } else {
                navbar.style.background = 'rgba(15, 15, 26, 0.95)';
                navbar.style.boxShadow = 'none';
            }
        });
    }

    // Server Selection in Watch Page
    initServerSelector();

    // Episode Tabs
    initEpisodeTabs();

    // Admin Mobile Sidebar
    initAdminSidebar();

    // Add Server Button in Admin
    initAddServer();

    // Favorite Button
    initFavoriteButton();

    // Lazy Load Images
    initLazyLoad();
});

/**
 * Server Selector - Switch between video servers
 */
function initServerSelector() {
    const serverButtons = document.querySelectorAll('.server-btn');
    const playerFrame = document.getElementById('playerFrame');
    const playerLoading = document.getElementById('playerLoading');

    if (!serverButtons.length || !playerFrame) return;

    serverButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active from all
            serverButtons.forEach(b => b.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');

            const embedUrl = this.dataset.embedUrl;

            // Show loading
            if (playerLoading) {
                playerLoading.style.display = 'flex';
            }

            // Update iframe src
            playerFrame.src = embedUrl;

            // Hide loading when iframe loads
            playerFrame.onload = function() {
                if (playerLoading) {
                    playerLoading.style.display = 'none';
                }
            };

            // Fallback: hide loading after 3 seconds
            setTimeout(() => {
                if (playerLoading) {
                    playerLoading.style.display = 'none';
                }
            }, 3000);

            // Save preference
            const serverName = this.dataset.server;
            localStorage.setItem('preferred_server', serverName);
        });
    });

    // Auto-select preferred server
    const preferredServer = localStorage.getItem('preferred_server');
    if (preferredServer) {
        const preferredBtn = document.querySelector(`.server-btn[data-server="${preferredServer}"]`);
        if (preferredBtn && !preferredBtn.classList.contains('active')) {
            preferredBtn.click();
        }
    }
}

/**
 * Episode Tabs - Switch between seasons
 */
function initEpisodeTabs() {
    const tabs = document.querySelectorAll('.episode-tab');
    const contents = document.querySelectorAll('.episode-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const season = this.dataset.season;

            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            contents.forEach(content => {
                if (content.dataset.season === season) {
                    content.style.display = 'grid';
                } else {
                    content.style.display = 'none';
                }
            });
        });
    });
}

/**
 * Admin Mobile Sidebar
 */
function initAdminSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const adminSidebar = document.querySelector('.admin-sidebar');

    if (sidebarToggle && adminSidebar) {
        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('open');
        });
    }
}

/**
 * Add Server Button in Admin Form
 */
function initAddServer() {
    const addServerBtn = document.getElementById('addServerBtn');
    const serversContainer = document.getElementById('serversContainer');

    if (!addServerBtn || !serversContainer) return;

    addServerBtn.addEventListener('click', function() {
        const index = serversContainer.children.length;
        const serverGroup = document.createElement('div');
        serverGroup.className = 'server-input-group';
        serverGroup.innerHTML = `
            <div>
                <label>السيرفر</label>
                <select name="servers[${index}][name]" required>
                    <option value="streamhg">StreamHG</option>
                    <option value="earnvids">EarnVids</option>
                    <option value="mixdrop">Mixdrop</option>
                    <option value="doodstream">DoodStream</option>
                </select>
            </div>
            <div>
                <label>رابط التضمين (Embed URL)</label>
                <input type="url" name="servers[${index}][url]" placeholder="https://..." required>
            </div>
            <div>
                <label>الجودة</label>
                <select name="servers[${index}][quality]">
                    <option value="HD">HD</option>
                    <option value="FHD">FHD</option>
                    <option value="4K">4K</option>
                </select>
            </div>
            <button type="button" class="remove-server" onclick="this.closest('.server-input-group').remove()">
                <i class="fas fa-trash"></i>
            </button>
        `;
        serversContainer.appendChild(serverGroup);
    });
}

/**
 * Favorite Button AJAX
 */
function initFavoriteButton() {
    const favBtn = document.getElementById('favoriteBtn');
    if (!favBtn) return;

    favBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const contentId = this.dataset.contentId;
        const icon = this.querySelector('i');
        const text = this.querySelector('span');

        fetch('ajax/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `content_id=${contentId}&csrf_token=${document.querySelector('input[name="csrf_token"]').value}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.action === 'added') {
                    this.classList.add('active');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    if (text) text.textContent = 'في المفضلة';
                } else {
                    this.classList.remove('active');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    if (text) text.textContent = 'أضف للمفضلة';
                }
            }
        })
        .catch(err => console.error('Favorite error:', err));
    });
}

/**
 * Lazy Load Images
 */
function initLazyLoad() {
    const lazyImages = document.querySelectorAll('img[data-src]');

    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }
}

/**
 * Confirm Delete
 */
function confirmDelete(message) {
    return confirm(message || 'هل أنت متأكد من الحذف؟');
}

/**
 * Copy to Clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('تم النسخ!');
    });
}
