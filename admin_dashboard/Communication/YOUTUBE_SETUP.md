# YouTube Real-Time Statistics Setup

## Overview
This guide will help you connect your YouTube channel **@LawrenceBlaze-vv7fd** to display real-time statistics in your Church Management System.

## 🎯 What You'll Get
- ✅ Real-time subscriber count
- ✅ Total video views
- ✅ Video count
- ✅ Engagement metrics
- ✅ Recent video statistics
- ✅ Auto-sync every hour (or on-demand)

---

## 📋 Step-by-Step Setup

### Step 1: Get YouTube API Key (10 minutes)

1. **Go to Google Cloud Console**
   - Visit: https://console.cloud.google.com/

2. **Create a New Project**
   - Click "Select a Project" at the top
   - Click "New Project"
   - Name: `Church Management System`
   - Click "Create"

3. **Enable YouTube Data API v3**
   - In the sidebar, go to "APIs & Services" → "Library"
   - Search for "YouTube Data API v3"
   - Click on it
   - Click "Enable"

4. **Create API Credentials**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "API Key"
   - Copy the API key (it looks like: `AIzaSyDxxxxxxxxxxxxxxxxxxxxxxxx`)
   - **IMPORTANT**: Click "Restrict Key" for security
   
5. **Restrict the API Key** (Recommended)
   - Under "API restrictions", select "Restrict key"
   - Check only "YouTube Data API v3"
   - Under "Application restrictions":
     - Select "HTTP referrers (websites)"
     - Add: `localhost/*`
     - Add: Your domain if deployed
   - Click "Save"

---

### Step 2: Get Your Channel ID

**Option A: Automatic (Recommended)**
- The system will try to find it automatically from your handle `@LawrenceBlaze-vv7fd`

**Option B: Manual**

1. Go to: https://www.youtube.com/@LawrenceBlaze-vv7fd
2. Right-click on the page → View Page Source
3. Search for `"channelId"` or `"externalId"`
4. Copy the ID (starts with `UC` - looks like: `UCxxxxxxxxxxxxxxxxxxxxx`)

**Option C: Using YouTube Studio**
1. Go to: https://studio.youtube.com/
2. Click on your profile picture (top right)
3. Click "Settings"
4. Go to "Channel" → "Advanced settings"
5. Your Channel ID is shown there

---

### Step 3: Configure the System

Open file: `youtube_sync.php`

Replace these lines:

```php
define('YOUTUBE_API_KEY', 'YOUR_API_KEY_HERE'); 
// Replace with: AIzaSyDxxxxxxxxxxxxxxxxxxxxx

define('YOUTUBE_CHANNEL_ID', 'UC_YOUR_CHANNEL_ID'); 
// Replace with: UCxxxxxxxxxxxxxxxxxxxxx (if you found it manually)
```

**Example:**
```php
define('YOUTUBE_API_KEY', 'AIzaSyB1234567890abcdefghijklmnopqrstuvwxyz');
define('YOUTUBE_CHANNEL_ID', 'UCa1b2c3d4e5f6g7h8i9j0k1l2m'); // Optional if auto-detect works
```

---

### Step 4: Test the Connection

1. **Open the test page:**
   ```
   http://localhost/Church_Management_System/admin_dashboard/Communication/youtube_sync.php?action=test
   ```

2. **You should see:**
   ```json
   {
     "api_key_set": true,
     "channel_id_set": true,
     "channel_handle": "@LawrenceBlaze-vv7fd",
     "php_version": "8.x.x",
     "allow_url_fopen": true
   }
   ```

3. **Sync your data:**
   ```
   http://localhost/Church_Management_System/admin_dashboard/Communication/youtube_sync.php?action=sync
   ```

4. **Expected response:**
   ```json
   {
     "success": true,
     "channel_id": "UCxxxxx...",
     "channel_title": "Lawrence Blaze",
     "subscribers": 123,
     "total_views": 1234,
     "video_count": 10,
     "fetched_at": "2025-01-22 12:34:56",
     "db_updated": true
   }
   ```

---

### Step 5: Enable Auto-Sync

**Option A: Manual Refresh Button (Easiest)**
- Add a "Refresh Stats" button in the UI
- Click to fetch latest data

**Option B: Auto-Sync on Page Load**
- Add to `communication.html`:
```javascript
// Auto-sync YouTube on page load
async function syncYouTubeStats() {
    try {
        const response = await fetch('youtube_sync.php?action=sync');
        const data = await response.json();
        if (data.success) {
            console.log('YouTube synced:', data);
            loadSocialMediaAccounts(); // Refresh UI
        }
    } catch (error) {
        console.error('YouTube sync error:', error);
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', function() {
    syncYouTubeStats();
});
```

**Option C: Scheduled Sync with CRON (Advanced)**
Create file: `sync_social_media.php`
```php
<?php
// Run this via cron every hour
include 'youtube_sync.php';
```

Add to Windows Task Scheduler or Linux crontab:
```
0 * * * * php /path/to/sync_social_media.php
```

---

## 🎨 Update the UI

Add a "Sync Now" button to your YouTube card in `communication.html`:

```html
<div class="social-box">
    <div class="social-top">
        <div class="social-info">
            <div class="social-icon yt">
                <i class="fab fa-youtube"></i>
            </div>
            <div class="social-details">
                <h3>YouTube</h3>
                <span class="social-status">Connected</span>
            </div>
        </div>
        <button class="link-btn" onclick="syncYouTubeNow()">
            <i class="fas fa-sync-alt"></i> Sync Now
        </button>
    </div>
    <!-- Rest of YouTube card -->
</div>

<script>
async function syncYouTubeNow() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    
    try {
        const response = await fetch('youtube_sync.php?action=sync');
        const data = await response.json();
        
        if (data.success) {
            alert('✓ YouTube stats updated!\n' +
                  'Subscribers: ' + data.subscribers + '\n' +
                  'Views: ' + data.total_views);
            loadSocialMediaAccounts(); // Refresh display
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        alert('Failed to sync: ' + error.message);
    }
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Sync Now';
}
</script>
```

---

## 📊 API Endpoints

### Sync YouTube Stats
```
GET youtube_sync.php?action=sync
```
Fetches current stats and updates database.

### Test Configuration
```
GET youtube_sync.php?action=test
```
Checks if API key and settings are configured.

### Get Recent Videos
```
GET youtube_sync.php?action=videos
```
Returns list of 10 most recent videos with stats.

---

## 🔐 Security Best Practices

1. **Restrict Your API Key**
   - Limit to YouTube Data API v3 only
   - Restrict to your domain/localhost

2. **Hide API Key in Production**
   - Move API key to environment variable
   - Never commit API keys to Git
   
3. **Rate Limiting**
   - YouTube API allows 10,000 quota points/day
   - 1 channel stats request = 3 points
   - Cache results for 1 hour to save quota

4. **Monitor Usage**
   - Check quotaGoogle Cloud Console → APIs → Dashboard
   - View usage and errors

---

## 🐛 Troubleshooting

### Error: "API key not valid"
- **Solution**: Check that you copied the full API key
- Ensure YouTube Data API v3 is enabled in your project

### Error: "Channel not found"
- **Solution**: Manually set your Channel ID in `youtube_sync.php`
- Verify your channel is public

### Error: "allow_url_fopen is disabled"
- **Solution**: Edit `php.ini`:
  ```
  allow_url_fopen = On
  ```
- Restart Apache in XAMPP

### Error: "Quota exceeded"
- **Solution**: You've hit the daily limit (10,000 points)
- Wait until midnight PT (Pacific Time)
- Or request quota increase in Google Cloud Console

### Stats not updating
- Check that `setup_complete.sql` was run
- Verify `social_media_accounts` table has a 'youtube' row
- Test endpoint: `youtube_sync.php?action=test`

---

## 💰 Costs

**YouTube Data API v3 is FREE** with limits:
- 10,000 quota points per day (free tier)
- 1 channel stats request = 3 points
- ~3,333 requests per day = **free**
- For church use, this is more than enough!

To increase quota (if needed):
- Go to Google Cloud Console
- Request quota increase (may require billing account setup)
- Typical increase: 1,000,000 points/day

---

## 📈 What Gets Tracked

| Metric | Description | Updates |
|--------|-------------|---------|
| Subscribers | Total channel subscribers | Real-time |
| Total Views | All-time video views | Real-time |
| Video Count | Number of published videos | Real-time |
| Recent Videos | Last 10 videos with stats | Real-time |
| Engagement Rate | Calculated metric | Real-time |

---

## 🎯 Next Steps

1. ✅ Get API key from Google Cloud Console
2. ✅ Add API key to `youtube_sync.php`
3. ✅ Test with: `youtube_sync.php?action=test`
4. ✅ Sync data: `youtube_sync.php?action=sync`
5. ✅ View stats in `communication.html` → Social Media tab
6. ✅ (Optional) Add auto-sync or sync button

---

## 📞 Support

- **YouTube API Docs**: https://developers.google.com/youtube/v3
- **Google Cloud Console**: https://console.cloud.google.com/
- **Quota Calculator**: https://developers.google.com/youtube/v3/determine_quota_cost

---

Your YouTube channel **@LawrenceBlaze-vv7fd** will now display real-time stats! 🎉
