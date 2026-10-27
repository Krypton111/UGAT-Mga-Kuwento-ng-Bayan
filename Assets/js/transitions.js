// assets/js/transitions.js
(function() {
    'use strict';

    // ===== PAGE TRANSITIONS =====
    document.addEventListener('DOMContentLoaded', function() {
        // Handle all internal links for smooth transitions
        const links = document.querySelectorAll('a[href]');
        links.forEach(link => {
            // Skip external links, javascript: links, and anchor links
            if (link.href.startsWith('javascript:') || 
                link.href.startsWith('#') ||
                link.href.startsWith('mailto:') ||
                link.href.startsWith('tel:')) {
                return;
            }
            
            // Check if it's an internal link (same domain)
            if (link.hostname === window.location.hostname) {
                link.addEventListener('click', function(e) {
                    // Don't intercept if ctrl/cmd is pressed (open in new tab)
                    if (e.ctrlKey || e.metaKey || e.shiftKey) return;
                    
                    e.preventDefault();
                    const targetUrl = this.href;
                    
                    // Add fade-out class
                    document.body.classList.add('fade-out');
                    
                    // Navigate after animation completes
                    setTimeout(function() {
                        window.location.href = targetUrl;
                    }, 500);
                });
            }
        });
    });

    // ===== PARTICLE SYSTEM =====
    function createParticles() {
        const container = document.createElement('div');
        container.className = 'particles';
        document.body.appendChild(container);
        
        const particleCount = 30;
        const colors = ['rgba(212, 175, 55, 0.3)', 'rgba(180, 20, 40, 0.2)', 'rgba(255, 215, 0, 0.2)'];
        
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            
            const size = Math.random() * 4 + 2;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.left = Math.random() * 100 + '%';
            const duration = Math.random() * 15 + 8;
            particle.style.animationDuration = duration + 's';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.background = colors[Math.floor(Math.random() * colors.length)];
            
            container.appendChild(particle);
        }
    }

    // ===== PARALLAX EFFECT =====
    function initParallax() {
        const loginBox = document.querySelector('.login-box');
        if (!loginBox) return;
        
        document.addEventListener('mousemove', function(e) {
            const x = (e.clientX / window.innerWidth - 0.5) * 2;
            const y = (e.clientY / window.innerHeight - 0.5) * 2;
            
            loginBox.style.transform = `perspective(1000px) rotateY(${x * 2}deg) rotateX(${-y * 2}deg)`;
            loginBox.style.transition = 'transform 0.2s ease';
        });
        
        document.addEventListener('mouseleave', function() {
            loginBox.style.transform = 'perspective(1000px) rotateY(0deg) rotateX(0deg)';
        });
    }

    // ===== GLITCH EFFECT =====
    function initGlitchEffect() {
        const titles = document.querySelectorAll('h1');
        titles.forEach(title => {
            if (title.textContent.includes('REALM') || title.textContent.includes('THRONE')) {
                setInterval(() => {
                    if (Math.random() > 0.85) {
                        title.style.transform = `translate(${Math.random() * 4 - 2}px, ${Math.random() * 4 - 2}px)`;
                        setTimeout(() => {
                            title.style.transform = 'translate(0, 0)';
                        }, 50);
                    }
                }, 3000);
            }
        });
    }

    // ===== SCROLL ANIMATIONS =====
    function initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.stat, .message, .footer').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease';
            observer.observe(el);
        });
    }

    // ===== BUTTON SPARKLES =====
    function initButtonSparkles() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes sparkle {
                0% { transform: scale(0); opacity: 1; }
                100% { transform: scale(4); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseenter', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const sparkle = document.createElement('div');
                sparkle.style.cssText = `
                    position: absolute;
                    left: ${x}px;
                    top: ${y}px;
                    width: 8px;
                    height: 8px;
                    background: radial-gradient(circle, #ffd700, transparent);
                    border-radius: 50%;
                    pointer-events: none;
                    animation: sparkle 0.6s ease forwards;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(sparkle);
                
                setTimeout(() => sparkle.remove(), 600);
            });
        });
    }

    // ===== INITIALIZE =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            initParallax();
            initGlitchEffect();
            initScrollAnimations();
            initButtonSparkles();
        });
    } else {
        createParticles();
        initParallax();
        initGlitchEffect();
        initScrollAnimations();
        initButtonSparkles();
    }

})();