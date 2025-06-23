# Visual Regression Tester for WordPress

A WordPress plugin that enables visual regression testing using BackstopJS. This plugin allows you to compare visual differences between two versions of your website, helping you catch unintended visual changes during development and deployment.

## Features

- Easy-to-use interface in WordPress admin
- Visual comparison of reference and test URLs
- Detailed HTML reports of visual differences
- Configurable viewport settings
- Support for multiple test scenarios

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Node.js 12 or higher
- BackstopJS 6.0 or higher
- Web server (Apache/Nginx)
- MySQL/MariaDB

## Installation

### 1. Prerequisites

#### Node.js and npm
```bash
# For Ubuntu/Debian:
sudo apt update
sudo apt install nodejs npm

# For macOS (using Homebrew):
brew install node

# For Windows:
# 1. Download Node.js installer from https://nodejs.org/
# 2. Run the installer (node-vXX.XX.XX-x64.msi)
# 3. Follow the installation wizard
# 4. Open Command Prompt as Administrator and run:
npm install -g npm

# Verify installation (all platforms)
node --version
npm --version
```

#### BackstopJS
```bash
# For Linux/macOS:
sudo npm install -g backstopjs

# For Windows (run Command Prompt as Administrator):
npm install -g backstopjs

# Verify installation (all platforms)
backstop --version
```

### 2. Plugin Installation

1. Download the plugin files
2. Place the plugin in `wp-content/plugins/visual-regression-tester/`
3. Activate the plugin through WordPress admin panel

### 3. Directory Setup

```bash
# For Linux/macOS:
mkdir -p wp-content/vrt-tests
chmod 755 wp-content/vrt-tests
sudo chown -R www-data:www-data wp-content/vrt-tests

# For Windows (using Command Prompt as Administrator):
mkdir wp-content\vrt-tests
# Set permissions using Windows Explorer:
# 1. Right-click on vrt-tests folder
# 2. Select Properties
# 3. Go to Security tab
# 4. Click Edit
# 5. Add IIS_IUSRS and give it Modify permissions
```

## Configuration

### File Permissions

```bash
# For Linux/macOS:
chmod -R 755 wp-content/plugins/visual-regression-tester
chmod -R 644 wp-content/plugins/visual-regression-tester/*.php

# For Windows:
# Set permissions using Windows Explorer:
# 1. Right-click on visual-regression-tester folder
# 2. Select Properties
# 3. Go to Security tab
# 4. Click Edit
# 5. Add IIS_IUSRS and give it Modify permissions
```

### Path Configuration

If needed, adjust the following paths in `includes/class-backstop-runner.php`:
```php
// For Linux/macOS:
$node_path = '/usr/bin/node';
$backstop_path = '/usr/local/lib/node_modules/backstopjs/cli/index.js';

// For Windows (adjust paths as needed):
$node_path = 'C:\\Program Files\\nodejs\\node.exe';
$backstop_path = 'C:\\Users\\[Username]\\AppData\\Roaming\\npm\\node_modules\\backstopjs\\cli\\index.js';
```

## Usage

### Running a Test

1. Access the Visual Regression Tester through WordPress admin:
   - Go to WordPress admin panel
   - Navigate to "Visual Regression Tester" in the menu

2. Enter test details:
   - Reference URL (e.g., production site)
   - Test URL (e.g., staging site)
   - Click "Run Test"

3. View results:
   - Wait for the test to complete
   - Click "View Report" to see the comparison results

### Understanding Results

- **No Visual Differences**: The test and reference sites appear identical
- **Visual Differences Found**: The report will highlight areas where differences were detected
- **Error**: Check the error message for troubleshooting steps

## Troubleshooting

### Common Issues

1. **BackstopJS not found**
   - Linux/macOS: Verify installation: `which backstop`
   - Windows: Check if BackstopJS is in PATH: `where backstop`
   - Check PATH environment variable
   - Reinstall if necessary:
     ```bash
     # Linux/macOS:
     sudo npm install -g backstopjs
     
     # Windows (as Administrator):
     npm install -g backstopjs
     ```

2. **Permission Issues**
   - Linux/macOS:
     ```bash
     ls -la wp-content/vrt-tests
     sudo chown -R www-data:www-data wp-content/vrt-tests
     ```
   - Windows:
     - Check folder permissions in Windows Explorer
     - Ensure IIS_IUSRS has Modify permissions
     - Check if running as Administrator

3. **Node.js Issues**
   - Verify Node.js version: `node --version`
   - Update if needed:
     ```bash
     # Linux/macOS:
     sudo npm install -g n && sudo n stable
     
     # Windows:
     # Download and run the latest installer from nodejs.org
     ```

4. **Report Not Visible**
   - Check file permissions
   - Verify report directory exists
   - Check web server configuration for directory access
   - Windows: Ensure IIS has proper MIME types for .html files

## Maintenance

### Regular Cleanup

```bash
# For Linux/macOS:
find wp-content/vrt-tests -type d -mtime +7 -exec rm -rf {} \;

# For Windows (using Command Prompt):
forfiles /P wp-content\vrt-tests /D -7 /C "cmd /c if @isdir==TRUE rmdir /S /Q @path"
```

### Updates

```bash
# For Linux/macOS:
sudo npm update -g backstopjs
sudo n stable

# For Windows (as Administrator):
npm update -g backstopjs
# Download and run the latest Node.js installer
```

## Security

### Best Practices

1. **File Permissions**
   - Keep test directories secure
   - Regular permission audits
   - Proper ownership settings
   - Windows: Use appropriate NTFS permissions

2. **Access Control**
   - Ensure only authorized users can run tests
   - Protect test results from unauthorized access
   - Windows: Configure IIS authentication appropriately

3. **Cleanup**
   - Regular cleanup of old test files
   - Secure disposal of sensitive data

## Support

For support, please:
1. Check the troubleshooting section
2. Review the WordPress support forums
3. Create an issue in the GitHub repository

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- BackstopJS for the visual regression testing engine
- WordPress for the platform
- Contributors and maintainers

## Changelog

### 1.0.0
- Initial release
- Basic visual regression testing functionality
- WordPress admin interface
- HTML report generation 