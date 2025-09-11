# Birthday Email Command

This command automatically sends birthday emails to employees whose birthday is today. It integrates with the existing notification system and follows the same pattern used for leave notifications.

## Features

- **Automatic Detection**: Finds employees with birthdays on the current date
- **Duplicate Prevention**: Ensures birthday emails are only sent once per day per employee
- **Multi-tenant Support**: Works across different firms
- **Scalable**: Uses the existing notification queue system
- **Dry Run Mode**: Test the command without sending actual emails
- **Comprehensive Logging**: Detailed logging for monitoring and debugging

## Command Usage

### Basic Usage
```bash
php artisan birthdays:send-emails
```

### With Options
```bash
# Send birthday emails for a specific firm
php artisan birthdays:send-emails --firm-id=1

# Dry run mode (no actual emails sent)
php artisan birthdays:send-emails --dry-run

# Combine options
php artisan birthdays:send-emails --firm-id=1 --dry-run
```

## Scheduling

The command is automatically scheduled to run daily at 9:00 AM in the `app/Console/Kernel.php`:

```php
// Send birthday emails daily at 9 AM
$schedule->command('birthdays:send-emails')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();
```

## How It Works

1. **Query Employees**: Finds employees with birthdays today using the `dob` field from `employee_personal_details` table
2. **Check Duplicates**: Verifies no birthday email was already sent today to avoid duplicates
3. **Queue Notifications**: Creates entries in the `notification_queue` table with status 'pending'
4. **Process Queue**: The existing `ProcessNotificationQueue` command processes these notifications every 5 minutes
5. **Send Emails**: Uses the existing `GenericNotification` class and `TenantMailer` to send emails

## Email Content

The birthday email includes:
- Personalized greeting with employee name
- Age calculation
- Company branding
- Professional birthday wishes

## Database Requirements

The command uses existing tables:
- `employees` - Employee basic information
- `employee_personal_details` - Contains the `dob` (date of birth) field
- `users` - User accounts for email delivery
- `notification_queue` - Queues the birthday notifications
- `firms` - Company information for branding

## Duplicate Prevention

To prevent sending multiple birthday emails to the same employee on the same day, the command:
1. Checks the `notification_queue` table for existing birthday notifications
2. Looks for notifications with `type: 'birthday'` in the JSON data
3. Filters by date to ensure only one email per day per employee

## Error Handling

- Comprehensive try-catch blocks
- Detailed logging for debugging
- Graceful handling of missing employee data
- Continues processing other employees if one fails

## Monitoring

The command provides detailed output including:
- Total employees with birthdays
- Number of emails queued
- Skipped employees (already sent or no user account)
- Error count and details

## Testing

Use the `--dry-run` flag to test the command without sending actual emails:

```bash
php artisan birthdays:send-emails --dry-run
```

This will show what would happen without making any database changes or sending emails.

## Integration

The command integrates seamlessly with:
- Existing notification system
- Multi-tenant architecture
- Current email templates
- Logging and monitoring systems

## Troubleshooting

### Common Issues

1. **No birthdays found**: Check if employees have `dob` values in `employee_personal_details`
2. **Emails not sent**: Verify the `ProcessNotificationQueue` command is running
3. **Permission errors**: Ensure proper database access and relationships

### Logs

Check Laravel logs for detailed error information:
```bash
tail -f storage/logs/laravel.log
```

## Performance Considerations

- Uses efficient database queries with proper indexing
- Processes employees in batches
- Runs in background to avoid blocking other operations
- Implements duplicate checking to prevent unnecessary processing
