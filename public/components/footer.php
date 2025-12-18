<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="logo">ES</div>
                <h3>EventSite</h3>
                <p>Platform manajemen event kampus terbaik untuk mahasiswa.</p>
            </div>

            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="index.php?page=home">Beranda</a>
                <a href="index.php?page=events">Jelajahi Event</a>
                <a href="index.php?page=home#about">Tentang Kami</a>
                <a href="index.php?page=home#contact">Kontak</a>
                <a href="index.php?page=login">Masuk</a>
                <a href="index.php?page=register">Daftar</a>
            </div>

            <div class="footer-contact">
                <h4>Hubungi Kami</h4>
                <p>📧 support@eventsite.com</p>
                <p>📱 +62 00000000000</p>
                <p>📍 Jl. ABCD No. 123, Pluto</p>
                <p>💻 <a href="https://github.com/iori-28/EventSite" target="_blank" style="color: #666; text-decoration: none;">GitHub Repository</a></p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> EventSite. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
    /* Footer Styles - Shared */
    .footer {
        background: white;
        padding: 60px 0 20px;
        border-top: 1px solid #eee;
        margin-top: auto;
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }

    .footer-brand .logo {
        width: 40px;
        height: 40px;
        background: var(--primary-gradient);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 17px;
        letter-spacing: 0.8px;
        text-align: center;
        line-height: 1;
        padding-left: 1.5px;
        margin-bottom: 15px;
    }

    .footer-brand p {
        color: #666;
        line-height: 1.6;
        max-width: 300px;
    }

    .footer-links,
    .footer-contact {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .footer-links h4,
    .footer-contact h4 {
        color: var(--text-dark);
        font-size: 16px;
        margin-bottom: 5px;
    }

    .footer-links a {
        color: #666;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-links a:hover {
        color: var(--primary-color);
    }

    .footer-contact p {
        color: #666;
        margin: 0;
    }

    .footer-contact a {
        transition: color 0.3s;
    }

    .footer-contact a:hover {
        color: var(--primary-color);
    }

    .footer-bottom {
        border-top: 1px solid #eee;
        padding-top: 20px;
        text-align: center;
        color: #999;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 30px;
            text-align: center;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .footer-links,
        .footer-contact {
            align-items: center;
        }
    }
</style>