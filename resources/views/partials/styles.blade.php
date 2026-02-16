    <style>
        body {
            background-color: var(--tg-theme-bg-color, #fff);
            color: var(--tg-theme-text-color, #000);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .app-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: var(--tg-theme-button-color, #40a7e3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: white;
            font-size: 3rem;
        }
        .app-name {
            color: var(--tg-theme-text-color, #000);
        }
        .app-description {
            color: var(--tg-theme-hint-color, #999);
        }
        .menu-item {
            background-color: var(--tg-theme-secondary-bg-color, #f0f0f0);
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
            color: var(--tg-theme-button-color, #40a7e3);
            margin-bottom: 10px;
            display: block;
        }
        .menu-item span {
            font-size: 1rem;
            font-weight: 500;
        }
        .container {
            background-color: var(--tg-theme-section-bg-color);
    </style>