# Social Media Tracking Setup Guide

## Overview
This implementation provides a complete social media tracking system for your Church Management System. It tracks metrics from Facebook, Instagram, Twitter (X), and YouTube.

## Features
- ✅ Real-time social media metrics display
- ✅ Track followers, engagement, and growth
- ✅ Post scheduling and management
- ✅ Historical analytics tracking
- ✅ Multi-platform support (Facebook, Instagram, Twitter/X, YouTube)
- ✅ Auto-refresh every 5 minutes
- ✅ REST API for social media operations

## Setup Instructions

### Step 1: Create Database Tables

1. Open **phpMyAdmin** in your browser: `http://localhost/phpmyadmin`
2. Select your database: `church_management`
3. Go to the **SQL** tab
4. Copy and paste the contents of `social_media_setup.sql`
5. Click **Go** to execute

This will create three tables:
- `social_media_accounts` - Stores account information and current metrics
- `social_media_posts` - Tracks all posts across platforms
- `social_media_analytics` - Historical daily analytics

### Step 2: Verify Database Setup

Run this query in phpMyAdmin to verify:
```sql
SELECT * FROM social_media_accounts;
```

You should see 4 rows (Facebook, Instagram, Twitter, YouTube) with sample data.

### Step 3: Test the Interface

1. Open your browser and navigate to:
   ```
   http://localhost/Church_Management_System/admin_dashboard/Communication/communication.html
   ```

2. Click on the **Social Media** tab

3. You should see all 4 platforms with live metrics loaded from the database

### Step 4: Update Metrics (Optional)

You can manually update metrics through the API. Example using JavaScript console:

```javascript
fetch('social_media_api.php?action=update_metrics', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        platform: 'facebook',
        followers_count: 5000,
        engagement_count: 450,
        engagement_rate: 9.0,
        posts_count: 25
    })
});
```

## API Endpoints

### GET Endpoints

#### Get All Accounts
```
GET social_media_api.php?action=accounts
```
Returns all social media accounts with current metrics.

#### Get Posts
```
GET social_media_api.php?action=posts&platform=facebook&limit=10
```
Returns recent posts for a platform (or all if platform not specified).

#### Get Analytics
```
GET social_media_api.php?action=analytics&platform=facebook
```
Returns 30-day analytics history.

### POST Endpoints

#### Create Post
```
POST social_media_api.php?action=post
Body: {
    "platform": "facebook",
    "post_type": "text",
    "content": "Join us this Sunday!",
    "media_path": null,
    "scheduled_at": null
}
```

#### Update Metrics
```
POST social_media_api.php?action=update_metrics
Body: {
    "platform": "facebook",
    "followers_count": 4500,
    "engagement_count": 380,
    "engagement_rate": 8.44,
    "posts_count": 20
}
```

#### Connect/Disconnect Account
```
POST social_media_api.php?action=connect
Body: {
    "platform": "facebook",
    "is_connected": true,
    "account_name": "First Baptist Church",
    "account_id": "123456789",
    "access_token": "your_token_here"
}
```

## Database Schema

### social_media_accounts
- `id` - Primary key
- `platform` - Platform name (facebook, instagram, twitter, youtube)
- `account_name` - Account display name
- `account_id` - Platform-specific account ID
- `access_token` - OAuth token (encrypted in production)
- `is_connected` - Connection status (0/1)
- `followers_count` - Total followers
- `engagement_count` - Total engagements
- `engagement_rate` - Engagement percentage
- `additional_metrics` - JSON field for platform-specific metrics
- `last_updated` - Last update timestamp
- `created_at` - Creation timestamp

### social_media_posts
- `id` - Primary key
- `platform` - Platform name
- `post_type` - Type (text, image, video, reel, story, live, playlist)
- `content` - Post content
- `media_path` - Path to media file
- `post_url` - Published post URL
- `likes_count` - Number of likes
- `comments_count` - Number of comments
- `shares_count` - Number of shares
- `views_count` - Number of views
- `status` - Status (draft, scheduled, published, failed)
- `scheduled_at` - Scheduled publish time
- `published_at` - Actual publish time
- `created_at` - Creation timestamp

### social_media_analytics
- `id` - Primary key
- `platform` - Platform name
- `date` - Date of metrics
- `followers_count` - Followers on that date
- `new_followers` - New followers that day
- `engagement_count` - Total engagements
- `engagement_rate` - Engagement percentage
- `posts_count` - Posts published that day
- `reach` - Content reach
- `impressions` - Content impressions
- `created_at` - Creation timestamp

## Integration with Real Social Media APIs

To connect to actual social media platforms, you'll need to:

### Facebook/Instagram
1. Create a Facebook Developer account
2. Create an app and get API credentials
3. Implement Facebook Graph API
4. Use OAuth for authentication
5. Store access tokens securely

### Twitter (X)
1. Create a Twitter Developer account
2. Get API keys and tokens
3. Implement Twitter API v2
4. Handle OAuth 2.0 authentication

### YouTube
1. Create a Google Cloud project
2. Enable YouTube Data API
3. Get OAuth 2.0 credentials
4. Implement YouTube API v3

## Security Considerations

⚠️ **IMPORTANT**: Before going to production:

1. **Encrypt access tokens** - Never store tokens in plain text
2. **Use HTTPS** - All API calls should be over HTTPS
3. **Add authentication** - Require user login to access APIs
4. **Rate limiting** - Implement rate limits on API endpoints
5. **Input validation** - Validate and sanitize all inputs
6. **CSRF protection** - Add CSRF tokens to forms
7. **SQL injection** - Already using prepared statements (✓)

## Testing

### Test Post Creation
```javascript
// Open browser console on communication.html
fetch('social_media_api.php?action=post', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        platform: 'facebook',
        content: 'Test post from Church Management System!',
        post_type: 'text'
    })
}).then(r => r.json()).then(console.log);
```

### Test Metrics Update
```javascript
fetch('social_media_api.php?action=update_metrics', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        platform: 'instagram',
        followers_count: 4000,
        engagement_count: 550,
        engagement_rate: 13.75,
        posts_count: 18
    })
}).then(r => r.json()).then(console.log);
```

## Troubleshooting

### Issue: "Database connection failed"
- Check that XAMPP MySQL is running
- Verify database name is `church_management`
- Check credentials in `db_connect.php`

### Issue: "Social media not loading"
- Open browser console (F12) to check for errors
- Verify `social_media_api.php` is accessible
- Check that database tables were created

### Issue: "Metrics not updating"
- Check browser console for errors
- Verify API endpoint is working: `http://localhost/.../social_media_api.php?action=accounts`
- Clear browser cache

## Future Enhancements

- 📊 Add charts for analytics visualization
- 🔔 Email notifications for engagement milestones
- 📅 Advanced scheduling with timezone support
- 🤖 AI-powered content suggestions
- 📸 Direct media upload to platforms
- 📱 Mobile app integration
- 🔄 Auto-sync with platform APIs every hour
- 💬 Unified inbox for comments/messages

## Support

For issues or questions, check:
1. Browser console for JavaScript errors
2. PHP error log: `C:\xampp\apache\logs\error.log`
3. Database query results in phpMyAdmin

## License

Part of the Church Management System
