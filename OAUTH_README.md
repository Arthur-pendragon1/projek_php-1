# OAuth Setup Instructions

## Google OAuth Setup
1. Go to [Google Developer Console](https://console.developers.google.com/)
2. Create a new project or select existing one
3. Enable the following APIs:
   - Google Sign-In API
   - Google People API
4. Go to Credentials > Create Credentials > OAuth 2.0 Client IDs
5. Set Application type to Web application
6. Add authorized redirect URIs: `http://localhost/web_sunsal/oauth_callback.php?provider=google`
   - **Important**: Must be exact match, including the query parameter `?provider=google`
   - If XAMPP uses different port (e.g., 8080), use `http://localhost:8080/web_sunsal/oauth_callback.php?provider=google`
7. Copy Client ID and Client Secret to `oauth_config.php`

## GitHub OAuth Setup
1. Go to [GitHub OAuth Apps](https://github.com/settings/applications/new)
2. Fill in:
   - Application name: Web Sunsal
   - Homepage URL: http://localhost/web_sunsal
   - Authorization callback URL: `http://localhost/web_sunsal/oauth_callback.php?provider=github`
     - **Important**: Must be exact match, including the query parameter `?provider=github`
     - If XAMPP uses different port, adjust accordingly
3. Copy Client ID and Client Secret to `oauth_config.php`

## Update oauth_config.php
Replace the placeholders in `oauth_config.php` with your actual credentials.

## Troubleshooting Common Errors

### Google: Error 401: invalid_client
- **Cause**: Client ID is incorrect or not set properly.
- **Fix**: Double-check Client ID in `oauth_config.php` matches exactly from Google Console. Ensure no extra spaces.

### GitHub: 404 Client
- **Cause**: Redirect URI doesn't match exactly.
- **Fix**: Ensure the Authorization callback URL in GitHub is exactly `http://localhost/web_sunsal/oauth_callback.php?provider=github` (or with port if needed).

### Port Issues
- Check XAMPP Control Panel for Apache port (usually 80 or 8080).
- If not 80, update `BASE_URL` in `oauth_config.php` to `http://localhost:PORT/web_sunsal/`
- Update redirect URIs in both consoles accordingly.

### Other Tips
- Ensure XAMPP Apache is running.
- Clear browser cache/cookies.
- Test with a new incognito window.

## Notes
- Users logging in via OAuth will have accounts created automatically if email doesn't exist.
- Passwords for OAuth users are randomly generated (they can reset if needed).