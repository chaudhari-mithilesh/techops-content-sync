# TechOps Content Sync

A WordPress plugin for syncing content with Git repositories, managing GitHub file access, and providing secure REST API endpoints for plugin and theme management.

## Features

### Content Management
- Sync WordPress plugins and themes with Git repositories
- List, activate, and deactivate plugins and themes via REST API
- Download plugins and themes securely
- Access and display GitHub repository files
- Download and save GitHub content locally

### Security & Authentication
- Secure token management for GitHub API access
- Basic Authentication for REST API endpoints
- Application Password support
- Input validation and sanitization
- Nonce verification for form submissions

## Installation

1. Upload the `techops-content-sync` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure GitHub settings in the TechOps Sync admin page
4. Generate an Application Password for API authentication

## Usage

### GitHub File Access
You can access GitHub repository files using the `[github_file_content]` shortcode with the following attributes:

- `repo`: GitHub repository URL (required)
- `path`: Path to the file in the repository (required)
- `token`: GitHub personal access token (optional)
- `display`: Display format ('text' or 'json')
- `action`: Action to perform ('download' for saving locally)
- `filename`: Filename for downloaded content (default: 'result.json')

Example:
```
[github_file_content repo="https://github.com/username/repo" path="path/to/file.json" display="json"]
```

### REST API Endpoints

#### Plugin Management
- `GET /wp-json/techops/v1/plugins/list` - List all plugins
- `POST /wp-json/techops/v1/plugins/activate/{slug}` - Activate a plugin
- `POST /wp-json/techops/v1/plugins/deactivate/{slug}` - Deactivate a plugin
- `GET /wp-json/techops/v1/plugins/download/{slug}` - Download a plugin

#### Theme Management
- `GET /wp-json/techops/v1/themes/list` - List all themes
- `POST /wp-json/techops/v1/themes/activate/{slug}` - Activate a theme
- `POST /wp-json/techops/v1/themes/deactivate/{slug}` - Deactivate a theme
- `GET /wp-json/techops/v1/themes/download/{slug}` - Download a theme

### API Authentication
All API endpoints require Basic Authentication using WordPress Application Passwords:

1. Go to Users → Your Profile in WordPress admin
2. Scroll down to "Application Passwords"
3. Enter a name for the password (e.g., "GitHub Actions")
4. Click "Add New Application Password"
5. Copy the generated password (you won't be able to see it again)

Example API request:
```bash
curl -H "Authorization: Basic {base64_encoded_credentials}" \
     https://your-site.com/wp-json/techops/v1/plugins/list
```

## Configuration

1. Navigate to the TechOps Sync admin page
2. Enter your GitHub personal access token in the settings
3. Save the settings
4. Generate an Application Password for API authentication

## Security

- All API requests require authentication
- GitHub tokens are securely stored
- Application Passwords for API access
- Rate limiting enabled (30 requests per minute)
- Input validation and sanitization
- Nonce verification for form submissions
- File path validation to prevent directory traversal

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- cURL extension enabled
- ZipArchive PHP extension

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please open an issue in the GitHub repository. 


# Project Structure & Folder Purposes

### 1. `techops-content-sync/`
This is the main WordPress plugin folder. It contains:
- **`techops-content-sync.php`**: Main plugin file, bootstraps the plugin.
- **`admin/`**: Admin UI assets and partials for settings, automation, and management pages.
  - `partials/`: PHP templates for admin pages (dashboard, settings, automation).
  - `css/` and `js/`: Styles and scripts for the admin interface.
- **`includes/`**: Core PHP classes for handling admin, API, authentication, file operations, GitHub integration, security, etc.
- **`modules/`**: Contains additional plugin modules (see below).
- **`uninstall.php`**: Handles cleanup on plugin uninstall.

### 2. `modules/visual-regression-tester/`
This is a sub-plugin/module for visual regression testing, structured as a standalone WordPress plugin:
- **`visual-regression-tester.php`**: Main file for this module/plugin.
- **`admin/`**: Admin UI for visual regression testing (single test, site-wide audit).
- **`includes/`**: PHP classes for running tests, integrating with BackstopJS, etc.
- **`public/`**: Frontend shortcodes and public-facing logic.
- **`assets/`**: CSS/JS for the module’s admin UI.
- **`backstop_data/`**: Stores test results, screenshots, and reports.
- **`templates/`**: Admin page templates.

---

## How the Two Plugins Are Connected

- **Parent-Child Relationship:**  
  The main `techops-content-sync` plugin acts as a parent or host. The `visual-regression-tester` is a module inside the `modules/` directory, but it is structured as a full WordPress plugin and can be loaded/activated independently or as part of the main plugin’s ecosystem.

- **Integration Points:**  
  - Both plugins add their own admin pages to the WordPress dashboard.
  - The main plugin focuses on syncing, managing, and automating plugin/theme operations (with GitHub integration).
  - The visual regression tester module adds advanced testing capabilities, allowing users to visually compare site versions or environments.
  - They may share authentication, settings, or utility functions, but each has its own admin UI and logic.

- **Purpose of Connection:**  
  By including the visual regression tester as a module, the main plugin offers a more complete DevOps/content workflow: not only can you sync and manage code/content, but you can also visually verify changes and catch regressions—all from the same admin interface.

---

## Summary Table

| Folder/Plugin                  | Purpose                                                                 |
|------------------------------- |------------------------------------------------------------------------|
| `techops-content-sync/`        | Main plugin: sync/manage plugins/themes, GitHub integration, automation |
| `modules/visual-regression-tester/` | Sub-plugin/module: visual regression testing (BackstopJS integration)   |

---

**In short:**  
The main plugin manages content and automation, while the module adds visual testing. They are connected by being in the same codebase and can work together to streamline WordPress site management and quality assurance.