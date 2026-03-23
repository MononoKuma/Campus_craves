# Complaint Management System

## Overview
A comprehensive complaint management system that allows buyers and sellers to file complaints, track their status, and communicate with administrators. Features a modern modal-based admin interface and user-friendly submission forms.

## Features
- **User-Facing Complaint Submission**: Clean forms for filing complaints with validation
- **Complaint History**: Users can track all their complaints and responses
- **Admin Modal Interface**: Modern modal-based complaint management for administrators
- **Status Tracking**: Pending, Investigating, Resolved, Dismissed status levels
- **Response System**: Two-way communication between users and admins
- **Integration**: Connected to orders and products for context

## Database Schema

### Complaints Table
```sql
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complainant_id INT NOT NULL,
    respondent_id INT NOT NULL,
    complaint_type ENUM('buyer', 'seller', 'product_issue', 'service_issue', 'payment_issue', 'delivery_issue', 'other') NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    order_id INT NULL,
    product_id INT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (complainant_id) REFERENCES users(id),
    FOREIGN KEY (respondent_id) REFERENCES users(id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);
```

### Complaint Responses Table
```sql
CREATE TABLE complaint_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    responder_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (responder_id) REFERENCES users(id)
);
```

## File Structure

### Controllers
- `src/controllers/ComplaintController.php` - User-facing complaint operations
- `src/controllers/AdminController.php` - Admin complaint management methods

### Models
- `src/models/Complaint.php` - Database operations for complaints

### Views
- `public/file-complaint.php` - Complaint submission form
- `public/my-complaints.php` - User complaint history
- `public/admin/complaints.php` - Admin complaint management with modal interface

## Key Features

### 1. Complaint Submission
- Pre-filled data from product/order pages
- Duplicate prevention (24-hour window)
- Validation and error handling
- Support for different complaint types

### 2. Admin Management
- Modal-based interface for viewing complaints
- Status updates with admin responses
- Response threading for communication
- Filtering by complaint type (buyer/seller)

### 3. User Experience
- Clean, modern blue and white theme
- Responsive design for mobile devices
- Real-time status tracking
- Two-way communication system

## Status Values
- `pending` - New complaint awaiting review
- `investigating` - Under investigation by admin
- `resolved` - Issue has been resolved
- `dismissed` - Complaint was dismissed

## Complaint Types
- `buyer` - Complaints filed by buyers
- `seller` - Complaints filed by sellers  
- `product_issue` - Issues with specific products
- `service_issue` - Service-related problems
- `payment_issue` - Payment and billing issues
- `delivery_issue` - Shipping and delivery problems
- `other` - General complaints

## Integration Points

### Product Pages
- Report buttons that pre-fill product information
- Direct links to complaint submission

### User Dashboard
- Quick access to complaint history
- Status indicators for active complaints

### Admin Dashboard
- Comprehensive complaint management
- Filtering and search capabilities
- Response and resolution tools

## Security Features
- Access control - users only see their own complaints
- Input validation and sanitization
- SQL injection prevention
- Session-based authentication

## Deployment Notes

### Database Setup
The `docker/mysql/init.sql` file includes:
- Correct table schemas with proper enum values
- Sample data for testing
- Proper foreign key relationships
- Indexes for performance

### Sample Data
The system includes sample users, products, orders, and complaints to demonstrate functionality:
- 5 users (admin, customers, seller)
- 4 products with seller relationships
- 3 orders with different statuses
- 5 sample complaints with various types and statuses
- Sample admin responses

## Future Enhancements
- Email notifications for complaint updates
- File attachment support for evidence
- Advanced filtering and search
- Reporting and analytics
- Escalation workflows
- Auto-assignment based on complaint type

## Troubleshooting

### Common Issues
1. **Data truncation errors** - Ensure database schema matches enum values
2. **Session issues** - Check session_start() is called in complaint pages
3. **Permission errors** - Verify user roles and access controls

### Migration
If updating from an old version, run:
```sql
UPDATE complaints SET status = 'pending' WHERE status NOT IN ('pending', 'investigating', 'resolved', 'dismissed');
ALTER TABLE complaints MODIFY COLUMN status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending';
```
