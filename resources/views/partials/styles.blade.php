    <style>
        body {
            background-color: var(--tg-theme-secondary-bg-color, #f0f0f0);
            color: var(--tg-theme-text-color, #000);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .main-container {
            background-color: var(--tg-theme-bg-color, #fff);
        }
        .container {
            background-color: var(--tg-theme-section-bg-color, #f0f0f0);
        }
        .app-name {
            color: var(--tg-theme-text-color, #000);
        }
        .app-description {
            color: var(--tg-theme-hint-color, #999);
        }
        .menu-item {
            background-color: var(--tg-theme-bg-color, #f0f0f0);
            cursor: pointer;
            transition: transform 0.2s, opacity 0.2s;
            color: var(--tg-theme-text-color, #000);
            text-decoration: none;
            display: block;
        }
        .menu-item:hover {
            transform: scale(1.02);
            opacity: 0.8;
        }
        .menu-item i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        .menu-item span {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--tg-theme-text-color, #000);
        }
    </style>