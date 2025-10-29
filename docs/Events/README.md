# Events Management Module - Backend Integration

## Overview
The Events Management module provides a comprehensive system for managing church events, displaying them in a calendar view with member birthdays, and tracking event statistics. All data is dynamically fetched from the database.

## Features

### 1. Event Display
- **List View**: Shows all events with filtering and search capabilities
- **Calendar View**: Monthly calendar showing events and birthdays
- **Event Details**: Detailed view of each event with all information
- **Statistics**: Real-time statistics and insights about events

### 2. Calendar Features
- Monthly calendar navigation
- Visual indicators for events (blue dot)
- Birthday indicators (🎂 emoji)
- Click on any day to see events and birthdays
- Automatic highlighting of today's date
- Support for multi-day events

### 3. Birthday Display
- Shows member birthdays on the calendar
- Displays member name and age
- Integrated with member database
- Only shows active members' birthdays

## File Structure

```
Events/
├── events.html              # Main events page with UI
├── db_config.php           # Database configuration
├── fetch_events.php        # API to fetch events
├── fetch_calendar_data.php # API for calendar with events & birthdays
├── fetch_statistics.php    # API for event statistics
└── README.md              # This file
```

## API Endpoints

### 1. fetch_events.php
**Purpose:** Retrieves events from the database with filtering options

**Parameters:**
- `filter` (optional): Filter type - 'all', 'upcoming', 'service', 'ministry', or specific type
- `search` (optional): Search term for name, description, or location
- `date` (optional): Specific date to filter by (YYYY-MM-DD format)
- `limit` (optional): Maximum number of events to return

**Response:**
```json
{
  "success": true,
  "message": "Events retrieved successfully",
  "data": {
    "events": [
      {
        "id": 1,
        "name": "Sunday Morning Service",
        "type": "Service",
        "category": "Worship",
        "description": "...",
        "start_date": "2025-10-20",
        "start_time": "09:00:00",
        "end_date": "2025-10-20",
        "end_time": "11:00:00",
        "location": "Main-sanctuary",
        "max_capacity": 200,
        "volunteers_needed": 10,
        "volunteer_roles": [
          {"name": "Usher", "quantity": 4}
        ],
        "tags": ["worship", "family"],
        "image_path": "/uploads/event_123.jpg",
        "status": "Published",
        "date_formatted": "Oct 20, 2025",
        "time_formatted": "9:00 AM"
      }
    ],
    "total": 15
  }
}
```

### 2. fetch_calendar_data.php
**Purpose:** Retrieves calendar data including events and birthdays for a specific month

**Parameters:**
- `year` (optional): Year (defaults to current year)
- `month` (optional): Month 1-12 (defaults to current month)

**Response:**
```json
{
  "success": true,
  "message": "Calendar data retrieved successfully",
  "data": {
    "year": 2025,
    "month": 10,
    "month_name": "October",
    "first_day_of_week": 3,
    "days_in_month": 31,
    "calendar_days": {
      "1": {
        "date": "2025-10-01",
        "day": 1,
        "events": [...],
        "birthdays": [...],
        "has_events": true,
        "has_birthdays": false,
        "has_activities": true,
        "event_count": 2,
        "birthday_count": 0
      }
    },
    "all_events": [...],
    "all_birthdays": [...],
    "total_events": 12,
    "total_birthdays": 5
  }
}
```

**Birthday Object Structure:**
```json
{
  "member_id": 123,
  "name": "John Doe",
  "first_name": "John",
  "last_name": "Doe",
  "date_of_birth": "1990-10-15",
  "birthday_date": "2025-10-15",
  "day": 15,
  "age": 35,
  "phone": "+1234567890",
  "email": "john@example.com",
  "event_type": "birthday"
}
```

### 3. fetch_statistics.php
**Purpose:** Retrieves statistics and insights about events

**Response:**
```json
{
  "success": true,
  "message": "Statistics retrieved successfully",
  "data": {
    "total_events": 50,
    "upcoming_events": 15,
    "draft_events": 3,
    "this_month_events": 8,
    "recent_events": 12,
    "virtual_events": 5,
    "requires_registration": 10,
    "needs_volunteers": 3,
    "total_volunteers_needed": 25,
    "attendance_growth": 22,
    "events_by_type": [
      {"type": "Service", "count": 20},
      {"type": "Bible-study", "count": 15}
    ],
    "events_by_category": [
      {"category": "Worship", "count": 25},
      {"category": "Youth", "count": 12}
    ],
    "next_week_events": [...]
  }
}
```

## Frontend Integration

### Initialization
The page automatically initializes on load by fetching:
1. All events (filtered by current filter)
2. Calendar data for current month
3. Statistics

```javascript
async function initializePage() {
    await Promise.all([
        fetchEvents(),
        fetchCalendarData(currentYear, currentMonth),
        fetchStatistics()
    ]);
}
```

### Event Filtering
Events can be filtered by:
- **All Events**: Shows all events
- **Upcoming**: Shows only future events
- **Service**: Shows worship services
- **Ministry**: Shows ministry-related events

```javascript
function filterEvents(filter) {
    currentFilter = filter;
    fetchEvents(filter);
}
```

### Search Functionality
Real-time search across event names, descriptions, and locations:

```javascript
function searchEvents() {
    const searchTerm = document.getElementById('cfEmEventSearch').value;
    fetchEvents(currentFilter, searchTerm);
}
```

### Calendar Navigation
Navigate between months to view events and birthdays:

```javascript
function previousMonth() {
    currentMonth--;
    if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    fetchCalendarData(currentYear, currentMonth);
}

function nextMonth() {
    currentMonth++;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    }
    fetchCalendarData(currentYear, currentMonth);
}
```

### Calendar Day Click
Clicking on a calendar day shows all events and birthdays for that day:

```javascript
function showDayDetails(dayData) {
    // Shows events and birthdays for the selected day
}
```

## Visual Indicators

### Calendar Day States
- **Regular Day**: Plain number
- **Today**: Blue background with white text
- **Has Events**: Blue dot at bottom
- **Has Birthdays**: 🎂 emoji in top-right corner
- **Inactive**: Grayed out (previous/next month days)

### CSS Classes
```css
.cf-em-calendar-day              /* Regular day */
.cf-em-calendar-day.cf-em-today  /* Today */
.cf-em-calendar-day.cf-em-has-event  /* Day with events */
.cf-em-calendar-day.cf-em-has-birthday  /* Day with birthdays */
.cf-em-calendar-day.cf-em-inactive  /* Inactive days */
```

## Database Requirements

### Tables Used
1. **events** - Main events table
2. **event_volunteer_roles** - Volunteer roles for events
3. **event_tags** - Tags associated with events
4. **members** - Member information (for birthdays)

### Key Queries

**Events for a Month:**
```sql
SELECT * FROM events
WHERE (start_date BETWEEN '2025-10-01' AND '2025-10-31')
OR (end_date BETWEEN '2025-10-01' AND '2025-10-31')
ORDER BY start_date ASC, start_time ASC
```

**Birthdays for a Month:**
```sql
SELECT * FROM members
WHERE status = 'active'
AND date_of_birth IS NOT NULL
AND MONTH(date_of_birth) = 10
ORDER BY DAY(date_of_birth) ASC
```

## Event Status Determination

Events have different status displays:
- **Confirmed**: Event is fully planned with volunteers
- **Needs Volunteers**: Event has unfilled volunteer roles
- **Needs Planning**: Event is in Draft status

```javascript
function determineEventStatus(event) {
    if (event.volunteers_needed > 0 &&
        event.volunteer_roles.length === 0) {
        return 'needs-volunteers';
    }
    if (event.status === 'Draft') {
        return 'needs-planning';
    }
    return 'confirmed';
}
```

## Adding New Events

The "New Event" button redirects to the dedicated event creation page:

```javascript
function openAddEventModal() {
    window.location.href = '../Add_Event/New_Event.html';
}
```

## Birthday Integration

### How It Works
1. Birthdays are fetched from the `members` table
2. Only active members' birthdays are shown
3. Birthdays are matched by month and day (year-agnostic)
4. Age is calculated based on current year
5. Birthdays appear with a 🎂 emoji on calendar

### Birthday Display
```javascript
if (dayData.has_birthdays) {
    dayDiv.classList.add('cf-em-has-birthday');
    const indicator = document.createElement('span');
    indicator.className = 'cf-em-birthday-indicator';
    indicator.textContent = '🎂';
    indicator.setAttribute('title', `${dayData.birthday_count} birthday(s)`);
    dayDiv.appendChild(indicator);
}
```

## Error Handling

All API calls include error handling:

```javascript
try {
    const response = await fetch(url);
    const data = await response.json();

    if (data.success) {
        // Process data
    } else {
        console.error('Error:', data.message);
    }
} catch (error) {
    console.error('Error:', error);
}
```

## Performance Considerations

### Parallel Loading
Data is fetched in parallel for faster page load:

```javascript
await Promise.all([
    fetchEvents(),
    fetchCalendarData(currentYear, currentMonth),
    fetchStatistics()
]);
```

### Caching
Consider implementing caching for:
- Calendar data (cache per month)
- Statistics (refresh every 5 minutes)
- Event lists (refresh on user action)

## Customization

### Adding New Filters
To add a new filter option:

1. Add button to HTML:
```html
<button class="cf-em-tab" onclick="filterEvents('retreat')">Retreats</button>
```

2. Update fetch_events.php to handle the filter

3. No JavaScript changes needed - it's already dynamic

### Adding New Statistics
To add new statistics:

1. Add query to `fetch_statistics.php`:
```php
$newStatQuery = "SELECT COUNT(*) as total FROM events WHERE ...";
$result = $conn->query($newStatQuery);
$newStat = $result->fetch_assoc()['total'];
```

2. Include in response:
```php
'new_stat' => intval($newStat)
```

3. Use in `updateStatisticsDisplay()` function

## Testing

### Test Checklist
- [ ] Events load on page load
- [ ] Calendar displays current month
- [ ] Birthdays show on correct days
- [ ] Month navigation works (previous/next)
- [ ] Event filtering works (all filters)
- [ ] Search functionality works
- [ ] Click on calendar day shows details
- [ ] Statistics display correctly
- [ ] Events with no data show "No events found"
- [ ] Birthday emoji displays correctly
- [ ] Multi-day events span correctly
- [ ] Today's date is highlighted
- [ ] Event dots appear on days with events

### Sample Test Data

**Create Test Event:**
```sql
INSERT INTO events (name, type, category, description, start_date, start_time,
                    end_date, end_time, location, max_capacity, status)
VALUES ('Test Event', 'Service', 'Worship', 'Test Description',
        CURDATE(), '10:00:00', CURDATE(), '12:00:00',
        'Main-sanctuary', 100, 'Published');
```

**Create Test Birthday:**
```sql
-- Ensure a member has a birthday this month
UPDATE members
SET date_of_birth = CONCAT(YEAR(CURDATE()) - 30, '-', MONTH(CURDATE()), '-15')
WHERE member_id = 1;
```

## Troubleshooting

### Common Issues

**1. Events not displaying**
- Check database connection in db_config.php
- Verify events table has data with status = 'Published'
- Check browser console for JavaScript errors
- Verify API endpoints are accessible

**2. Birthdays not showing**
- Ensure members table has date_of_birth values
- Check that members have status = 'active'
- Verify month matching logic

**3. Calendar not rendering**
- Check if calendarData is being populated
- Verify month/year parameters
- Check CSS is loading correctly

**4. Statistics showing zero**
- Verify fetch_statistics.php is accessible
- Check database queries are returning data
- Ensure no SQL errors

## Security Considerations

1. **SQL Injection**: All queries use prepared statements
2. **XSS Prevention**: All user inputs are sanitized
3. **Access Control**: Consider adding authentication checks
4. **Data Validation**: Server-side validation of all inputs

## Future Enhancements

Potential improvements:
- Event registration system
- Attendance tracking integration
- Export events to CSV/PDF
- Email reminders for upcoming events
- Birthday email notifications
- Recurring event management
- Event categories management
- Advanced filtering options
- Mobile app integration
- Print calendar view
- Event image gallery
- Social media sharing

## Version History

**v1.0** (2025) - Initial Implementation
- Events fetching from database
- Calendar view with month navigation
- Birthday integration
- Event statistics
- Search and filtering
- Dynamic calendar rendering
- Multi-day event support

## Support

For issues or questions:
- Check browser console for errors
- Review PHP error logs
- Verify database connections
- Test API endpoints individually
- Check network tab in browser dev tools
