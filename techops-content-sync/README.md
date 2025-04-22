# TechOps Content Sync

A WordPress plugin for syncing content with Git repositories and managing plugins/themes.

## Features

- Download specific folders from Git repositories as zip files
- Install plugins and themes from Git repositories
- Automatic type detection (plugin/theme)
- Secure authentication and permissions
- Dependency management
- Logging and debugging

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- PHP ZipArchive extension
- Composer (for dependency management)

## Installation

1. Upload the plugin to your WordPress site
2. Navigate to the plugin directory
3. Run `composer install` to install dependencies
4. Activate the plugin through WordPress admin

## Usage

### Git Repository Integration

1. Go to TechOps Sync in the WordPress admin menu
2. Enter the Git repository URL
3. Specify the folder path within the repository
4. Click "Download and Install"

The plugin will:
1. Download the specified folder as a zip file
2. Detect if it's a plugin or theme
3. Install it on your WordPress site
4. Clean up temporary files

### REST API Endpoints

The plugin provides the following REST API endpoints:

- `POST /wp-json/techops/v1/git/download`
  - Parameters:
    - `repo_url`: Git repository URL
    - `folder_path`: Path to the folder within the repository

## Security

- All operations require admin privileges
- Input validation and sanitization
- Secure file handling
- Temporary file cleanup
- Nonce verification for forms

## Development

### Directory Structure

```
techops-content-sync/
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-api-endpoints.php
│   ├── class-authentication.php
│   ├── class-file-handler.php
│   ├── class-git-handler.php
│   ├── class-installer.php
│   └── class-security.php
├── vendor/
├── composer.json
├── README.md
├── techops-content-sync.php
└── uninstall.php
```

### Dependencies

The plugin uses Composer for dependency management. Key dependencies:

- `czproject/git-php`: Git operations
- PHP ZipArchive extension: Zip file handling

## Troubleshooting

### Common Issues

1. **Dependencies Not Installed**
   - Run `composer install` in the plugin directory
   - Check PHP version and extensions

2. **Permission Issues**
   - Ensure proper file permissions
   - Check WordPress user capabilities

3. **Git Operations Failed**
   - Verify Git repository URL
   - Check folder path exists
   - Ensure Git is installed on the server

### Debugging

Enable debug mode in the plugin settings to see detailed logs.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please create an issue in the repository or contact the plugin author. 