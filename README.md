# Dormitory Management System

A comprehensive web-based dormitory management system built with PHP, MySQL, HTML, CSS, Bootstrap, and JavaScript. This system provides complete functionality for managing dormitory operations from student registration to administrative oversight.

## Features

### Admin Features
- **Dashboard** - Analytics and statistics with real-time data
- **Admin Settings** - Change username and password (Default: Bsit_batch_22/Bsit_batch_22)
- **Offense Logs** - Monitor and log student violations with severity levels
- **Announcements** - Post bulletins and alerts for events, policies, or emergencies
- **Maintenance Request Management** - View, assign, and track repair tickets
- **Room Request Approval** - Approve or reject room change requests
- **Biometrics** - Upload and manage biometric attendance files
- **Student Locator** - Monitor student location status and logs
- **Visitor Logs** - View and manage visitor registration logs
- **Room Management** - Monitor capacity and track occupancy
- **Add Rooms & Buildings** - Manage buildings and room configurations
- **Online Reservation Management** - Approve/reject student applications
- **Policies Management** - Upload and update dormitory rules
- **Complaints Management** - Review and address student complaints

### Student Features
- **Registration** - Complete application with personal details and document upload
- **Login Authentication** - Secure access using Student ID and LRN
- **Announcements** - View dorm-wide updates and notices
- **Maintenance Requests** - Submit repair requests
- **Room Requests** - Apply for room changes
- **Biometric Downloads** - Download attendance logs
- **Visitor Registration** - Register guests with check-in/out
- **Complaints** - Submit concerns and complaints
- **Offense Records** - View recorded violations
- **Policy Access** - Read dormitory rules and regulations

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, Bootstrap 5.3
- **JavaScript**: Vanilla JS with modern ES6+ features
- **Server**: Apache (XAMPP recommended)
- **Additional**: Font Awesome icons, responsive design

## Installation

### Prerequisites
- XAMPP or similar PHP/MySQL environment
- Web browser (Chrome, Firefox, Safari, Edge)
- Text editor (optional, for customization)

### Setup Instructions

1. **Download and Install XAMPP**
   - Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Install and start Apache and MySQL services

2. **Clone/Download the Project**
   ```bash
   # Option 1: Clone from repository
   git clone [repository-url]
   
   # Option 2: Download and extract ZIP file
   # Extract to xampp/htdocs/dormitory_management/
   ```

3. **Database Setup**
   ```sql
   # Open phpMyAdmin (http://localhost/phpmyadmin)
   # Import the database file or run setup.sql:
   
   # Option 1: Import database/setup.sql
   # Option 2: Run SQL commands manually
   ```

4. **Configure Database Connection**
   ```php
   # Edit config/database.php if needed
   $host = 'localhost';
   $username = 'root';
   $password = '';
   $database = 'dormitory_management';
   ```

5. **Set Permissions**
   ```bash
   # Create uploads directory and set permissions
   mkdir uploads/student_documents
   mkdir uploads/biometric_files
   chmod 755 uploads/
   ```

6. **Access the System**
   - Admin Login: `http://localhost/dormitory_management/login.php`
   - Student Login: `http://localhost/dormitory_management/student_login.php`
   - Student Registration: `http://localhost/dormitory_management/student_registration.php`

## Default Credentials

### Admin Access
- **Username**: Bsit_batch_22
- **Password**: Bsit_batch_22

### Student Access
Students must register first through the registration page. After admin approval, they can login using:
- **Student ID**: 6-digit number provided during registration
- **LRN**: 12-digit Learner Reference Number (used as password)

## File Structure

```
dormitory_management/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Main admin dashboard
│   ├── settings.php       # Admin settings
│   ├── reservations.php   # Student application management
│   ├── announcements.php  # Announcement management
│   ├── offense_logs.php   # Offense tracking
│   ├── maintenance.php    # Maintenance requests
│   ├── biometrics.php     # Biometric file management
│   ├── student_locator.php # Student location tracking
│   ├── visitor_logs.php   # Visitor management
│   ├── room_management.php # Room occupancy
│   ├── add_rooms.php      # Add buildings/rooms
│   ├── policies.php       # Policy management
│   ├── complaints.php     # Complaint handling
│   └── logout.php         # Logout functionality
├── student/               # Student portal files
│   ├── dashboard.php      # Student dashboard
│   ├── announcements.php  # View announcements
│   ├── maintenance.php    # Submit maintenance requests
│   ├── room_requests.php  # Request room changes
│   ├── biometrics.php     # Download biometric files
│   ├── visitors.php       # Register visitors
│   ├── complaints.php     # Submit complaints
│   ├── offenses.php       # View offense records
│   ├── policies.php       # View policies
│   └── logout.php         # Logout functionality
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── main.js        # Main JavaScript file
├── config/                # Configuration files
│   └── database.php       # Database connection
├── includes/              # PHP includes
│   └── auth.php           # Authentication functions
├── database/              # Database files
│   └── setup.sql          # Database schema
├── uploads/               # File uploads
│   ├── student_documents/ # Student document uploads
│   └── biometric_files/   # Biometric file uploads
├── login.php              # Admin login page
├── student_login.php      # Student login page
├── student_registration.php # Student registration
└── README.md              # This file
```

## Database Schema

### Main Tables
- **admins** - Administrator accounts
- **students** - Student information and applications
- **buildings** - Building information
- **rooms** - Room details and capacity
- **announcements** - System announcements
- **offense_logs** - Student violation records
- **maintenance_requests** - Repair and maintenance requests
- **room_requests** - Room change requests
- **biometric_files** - Uploaded biometric files
- **student_location_logs** - Student location tracking
- **visitor_logs** - Visitor registration records
- **policies** - Dormitory rules and policies
- **complaints** - Student complaints

## Key Features Explained

### Student Registration Process
1. Student fills out comprehensive registration form
2. System validates student ID (6 digits) and LRN (12 digits)
3. Optional document upload for verification
4. Application status: Pending → Approved/Rejected by admin
5. Approved students can login with Student ID and LRN

### Room Management System
- Buildings can have multiple floors and rooms
- Each room has 4 bed capacity by default
- Real-time occupancy tracking
- Room assignment during student approval

### Visitor Management
- Students register visitors with personal details
- Automatic check-in timestamp
- Manual check-out with "Time Out" button
- Admin can view all visitor logs

### Biometric Integration
- Admin uploads biometric attendance files
- Students can download their attendance records
- File management with date tracking
- Support for various file formats

### Location Tracking
- Track student status: inside dormitory, on campus, off campus
- Weekly log cycling for fresh starts
- Search functionality for quick student lookup
- Timestamp recording for accountability

## Security Features

- Password hashing using PHP's password_hash()
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF token validation for forms
- File upload security with type validation
- Session management for authentication

## Responsive Design

The system features a modern, responsive design with:
- Green-to-yellow gradient theme
- Mobile-friendly interface
- Bootstrap 5.3 components
- Font Awesome icons
- Smooth animations and transitions
- Card-based layout for better organization

## Customization

### Changing Colors
Edit `assets/css/style.css` and modify CSS variables:
```css
:root {
    --primary-green: #2e7d32;
    --primary-yellow: #ffc107;
    --gradient-primary: linear-gradient(135deg, #2e7d32 0%, #4caf50 50%, #ffc107 100%);
}
```

### Adding Features
1. Create new PHP files in appropriate directories
2. Add navigation links in sidebar
3. Implement authentication checks
4. Follow existing code patterns

### Database Modifications
1. Update `database/setup.sql`
2. Modify `includes/auth.php` for new functions
3. Update related PHP files

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check XAMPP MySQL service is running
   - Verify database credentials in `config/database.php`
   - Ensure database exists and tables are created

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Verify PHP upload limits in php.ini
   - Ensure proper file type validation

3. **Login Problems**
   - Verify default admin credentials
   - Check student approval status
   - Clear browser cache and cookies

4. **Missing Styling**
   - Check CSS file path in HTML
   - Verify Bootstrap CDN links
   - Clear browser cache

### Performance Optimization

- Enable PHP OPcache
- Optimize database queries
- Compress static assets
- Use browser caching
- Regular database maintenance

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions:
- Check the documentation
- Review common issues in troubleshooting
- Submit issues for bugs or feature requests

## Version History

- **v1.0.0** - Initial release with complete admin and student functionality
- Features: User management, room management, visitor tracking, biometric integration

---

**Note**: This system is designed for educational and small-scale dormitory management. For enterprise use, consider additional security audits and performance optimizations.