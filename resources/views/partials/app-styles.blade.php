    <style>
        body {
            background-color: var(--tg-theme-bg-color, #fff);
            color: var(--tg-theme-text-color, #000);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: var(--tg-theme-secondary-bg-color, #f8f9fa);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 0.5rem 1rem;
        }
        .back-button {
            font-size: 1.5rem;
            color: var(--tg-theme-button-color, #40a7e3);
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }
        .user-dropdown {
            cursor: pointer;
        }
        .theme-indicator {
            font-size: 1.2rem;
            color: var(--tg-theme-hint-color, #999);
            margin-right: 0.75rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .theme-indicator:hover {
            opacity: 0.8s;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--tg-theme-button-color, #40a7e3);
        }
        .user-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--tg-theme-button-color, #40a7e3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .dropdown-menu {
            background-color: var(--tg-theme-secondary-bg-color, #fff);
            border: 1px solid var(--tg-theme-hint-color, #ddd);
        }
        .dropdown-item {
            color: var(--tg-theme-text-color, #000);
        }
        .dropdown-item:hover {
            background-color: var(--tg-theme-button-color, #40a7e3);
            color: var(--tg-theme-button-text-color, white);
        }
        .content {
            padding: 20px;
        }
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--tg-theme-text-color, #000);
        }
        .app-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .app-item {
            background-color: var(--tg-theme-secondary-bg-color, #f8f9fa);
            border-radius: 16px;
            padding: 20px 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            color: var(--tg-theme-text-color, #000);
            text-decoration: none;
            display: block;
        }
        .app-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .app-item i {
            font-size: 2.5rem;
            color: var(--tg-theme-button-color, #40a7e3);
            margin-bottom: 10px;
            display: block;
        }
        .app-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        .admin-section {
            margin-top: 20px;
            border-top: 1px solid var(--tg-theme-hint-color, #ddd);
            padding-top: 20px;
        }
        .container-custom {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>