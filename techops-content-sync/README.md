# TechOps Content Sync Plugin

A WordPress plugin that provides secure REST API endpoints for syncing plugins and themes.

## Installation

1. Upload the `techops-content-sync` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Generate an Application Password in WordPress for authentication

## Generating an Application Password

1. Go to Users → Your Profile in WordPress admin
2. Scroll down to "Application Passwords"
3. Enter a name for the password (e.g., "GitHub Actions")
4. Click "Add New Application Password"
5. Copy the generated password (you won't be able to see it again)

## API Endpoints

### List Plugins
```
GET /wp-json/techops/v1/plugins/list
Authorization: Basic {base64_encoded_credentials}
```

### List Themes
```
GET /wp-json/techops/v1/themes/list
Authorization: Basic {base64_encoded_credentials}
```

### Download Plugin
```
GET /wp-json/techops/v1/plugins/download/{plugin-slug}
Authorization: Basic {base64_encoded_credentials}
```

### Download Theme
```
GET /wp-json/techops/v1/themes/download/{theme-slug}
Authorization: Basic {base64_encoded_credentials}
```

## Security

- All endpoints require authentication using Application Passwords
- Rate limiting is enabled (30 requests per minute)
- File paths are validated to prevent directory traversal
- All actions are logged when WP_DEBUG is enabled

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- ZipArchive PHP extension

## Example Usage

Using cURL:
```bash
# List plugins
curl -H "Authorization: Basic {base64_encoded_credentials}" \
     https://your-site.com/wp-json/techops/v1/plugins/list

# Download a plugin
curl -H "Authorization: Basic {base64_encoded_credentials}" \
     https://your-site.com/wp-json/techops/v1/plugins/download/your-plugin-slug \
     -o plugin.zip
```

## License

GPL v2 or later 