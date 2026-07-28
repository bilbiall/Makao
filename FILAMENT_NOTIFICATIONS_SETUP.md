# Filament Database Notifications Setup Guide

## ✅ Status

Your Filament notifications system is now **fully configured and working**!

## What's Been Set Up

### 1. **Database Table** ✓
- The `notifications` table exists and stores all database notifications
- Contains: `id`, `type`, `notifiable_type`, `notifiable_id`, `data`, `read_at`, `created_at`, `updated_at`

### 2. **User Model** ✓
- User model has the `Notifiable` trait imported
- Users can send and receive notifications

### 3. **Notification Classes** ✓
Two notification classes are available:

#### **DatabaseNotification** (Generic)
Location: `app/Notifications/DatabaseNotification.php`
```php
use App\Notifications\DatabaseNotification;

$user->notify(new DatabaseNotification(
    title: 'Invoice Sent',
    message: 'Invoice #INV-001 has been sent',
    url: '/admin/invoices/1' // optional
));
```

#### **NewIssueNotification** (Specific)
Location: `app/Notifications/NewIssueNotification.php`
- Used when new issues are reported
- Automatically sent to admins and affected tenants

### 4. **Panel Configuration** ✓
Both panels configured:
- **Admin Panel** (`AdminPanelProvider`): Database notifications enabled
- **Tenant Panel** (`TenantPanelProvider`): Database notifications enabled
- **Polling**: Set to 10 seconds (checks for new notifications every 10s)
- **Bell icon**: Visible in both panels

### 5. **Existing Notification Triggers** ✓
Notifications are already being sent on these events:

| Event | Location | Notified |
|-------|----------|----------|
| New Invoice Created | `app/Models/Invoice.php` | Admin users |
| Payment Received | `app/Models/Payment.php` | Admin users & Tenant users |
| New Tenant Added | `app/Models/Tenant.php` | Admin users |
| Issue Status Changed | `app/Models/Issue.php` | Admins & Affected tenants |
| New Issue Reported | `app/Filament/Tenant/Resources/IssueResource/Pages/CreateIssue.php` | Admin users |

## Testing the System

### Test Command
A test command has been created to verify notifications work:

```bash
php artisan notifications:test --user-id=1
```

### Manual Testing
To send a notification programmatically:

```php
use App\Notifications\DatabaseNotification;

$user = User::find(1);
$user->notify(new DatabaseNotification(
    'Test Title',
    'Test message content',
    null
));
```

### Verify in Database
```bash
php artisan tinker
DB::table('notifications')->where('notifiable_id', 1)->latest()->get();
```

## How to Use in Your Code

### 1. Send Simple Notification
```php
// In models, controllers, or anywhere
use App\Notifications\DatabaseNotification;

$admin = User::where('role', 'admin')->first();
$admin->notify(new DatabaseNotification(
    'Title',
    'Message content',
    '/admin/path/to/resource' // optional URL
));
```

### 2. Send to Multiple Users
```php
$admins = User::where('role', 'admin')->get();
foreach ($admins as $admin) {
    $admin->notify(new DatabaseNotification(...));
}
```

### 3. Send to All Admins (Common Pattern)
```php
User::where('role', 'admin')->get()->each(function ($admin) {
    $admin->notify(new DatabaseNotification(
        'Invoice Created',
        "Invoice #{$invoice->number} created",
        null
    ));
});
```

## Where Notifications Appear

### Admin Panel
- **URL**: `https://yoursite.com/admin`
- **Bell Icon**: Top right corner
- Click to see unread notifications
- Auto-refresh every 10 seconds

### Tenant Panel
- **URL**: `https://yoursite.com/tenant`
- **Bell Icon**: Top right corner
- Shows notifications specific to that tenant
- Auto-refresh every 10 seconds

## Recommended Notification Triggers

Consider adding notifications for these events:

1. **Invoice Events**
   - Invoice overdue (already sending on creation)
   - Invoice cancelled

2. **Payment Events**
   - Payment received (already set up)
   - Payment failed/pending
   - Payment refunded

3. **House/Tenant Management**
   - New tenant added (already set up)
   - Tenant deleted/vacated
   - House status changed

4. **Issues**
   - New issue reported (already set up)
   - Issue assigned
   - Issue resolved (already set up)

5. **Notice to Vacate**
   - Notice submitted
   - Notice approved (already sends SMS, can add notification)
   - Notice denied (already sends SMS, can add notification)

## Advanced: Adding Notifications to Existing Actions

Example: Send notification when approving a notice to vacate

```php
// In NoticeToVacateResource.php approve action
->action(function (NoticeToVacate $record, array $data) {
    // ... existing code ...
    
    // Send notification to tenant
    $tenant = $record->tenant;
    if ($tenant?->user) {
        $tenant->user->notify(new DatabaseNotification(
            'Notice to Vacate Approved',
            "Your notice to vacate on {$record->vacate_date->format('d M Y')} has been approved.",
            null
        ));
    }
    
    // ... rest of approval logic ...
})
```

## Troubleshooting

### Notifications not appearing?

1. **Check database**:
   ```bash
   php artisan tinker
   DB::table('notifications')->count();
   ```

2. **Verify user is logged in** - Notifications only show for authenticated users

3. **Check polling interval** - Currently set to 10 seconds, increase if needed:
   ```php
   ->databaseNotificationsPolling('5s') // faster polling
   ```

4. **Clear cache**:
   ```bash
   php artisan cache:clear
   ```

5. **Check browser console** - Look for JavaScript errors

### Notifications appearing but marked as read?

The system automatically marks notifications as read when the user views them. Click the bell to see the full list with read/unread status.

## Configuration Files

- **Admin Panel**: `app/Providers/Filament/AdminPanelProvider.php`
- **Tenant Panel**: `app/Providers/Filament/TenantPanelProvider.php`
- **Notification Classes**: `app/Notifications/`
- **Database Migrations**: `database/migrations/2026_01_11_095250_create_notifications_table.php`

## Next Steps

1. ✅ System is set up and working
2. Add notification triggers to Notice to Vacate approval/denial (recommended)
3. Consider adding email notifications in addition to database notifications
4. Customize notification icons and colors as needed

---

**Last Updated**: January 11, 2026
**Status**: ✅ Fully Functional
