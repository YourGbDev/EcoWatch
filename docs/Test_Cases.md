# Test Cases

## Test Environment

- **OS**: Windows 10/11
- **Server**: XAMPP (Apache + MySQL)
- **PHP**: 7.x / 8.x
- **Browser**: Chrome/Firefox/Edge
- **Database**: `smart_flood_waste`

## Pre-Test Setup

1. Start Apache and MySQL in XAMPP Control Panel
2. Import `database/setup.sql` into MySQL
3. Navigate to `http://localhost/smart-flood-waste-monitoring`
4. Ensure demo accounts exist:
   - Admin: `admin@ecowatch.gov` / `admin123`
   - Citizen: `demo@ecowatch.gov` / `demo1234`

---

## Test Case 1: User Registration

**Test Name**: Valid user registration via homepage modal  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Open `index.html`
2. Click "Register" in navbar
3. Enter name: "Test User"
4. Enter email: `testuser@example.com`
5. Enter password: `testpass123`
6. Enter confirm password: `testpass123`
7. Click "Register Account"

**Expected Result**:
- Alert shows: "Registration successful! You can now log in."
- Modal closes
- Login modal opens automatically

**Actual Result**: Matches expected

---

## Test Case 2: User Login

**Test Name**: Valid user login  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Open `index.html`
2. Click "Login"
3. Enter email: `testuser@example.com`
4. Enter password: `testpass123`
5. Click "Sign In"

**Expected Result**:
- Redirect to `dashboard.php`
- Page shows: "Welcome back, Test User!"
- Dashboard loads with stats

**Actual Result**: Matches expected

---

## Test Case 3: Invalid Login

**Test Name**: Login with wrong credentials  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Open `index.html`
2. Click "Login"
3. Enter email: `testuser@example.com`
4. Enter password: `wrongpassword`
5. Click "Sign In"

**Expected Result**:
- Alert shows: "Invalid credentials."
- Modal stays open
- No redirect occurs

**Actual Result**: Matches expected

---

## Test Case 4: Submit Report

**Test Name**: Citizen submits environmental report  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Login as citizen
2. Navigate to "Submit Report"
3. Select category: "Flood"
4. Select severity: "High"
5. Select barangay: "Barangay 1"
6. Enter address: "123 Main St"
7. Enter description: "Flash flooding blocking road"
8. Click "Submit Report"

**Expected Result**:
- Success screen appears
- Tracking token displayed (format: EW-XXXXXX)
- Token is unique and alphanumeric

**Actual Result**: Matches expected

---

## Test Case 5: Photo Upload

**Test Name**: Submit report with photo evidence  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Login as citizen
2. Navigate to "Submit Report"
3. Select category, severity, barangay, address
4. Click file input, select JPEG image (<5MB)
5. Verify preview appears
6. Submit form

**Expected Result**:
- Photo preview displays before submission
- Report submits successfully
- Photo path stored in database

**Actual Result**: Matches expected

---

## Test Case 6: Tracking Token Lookup

**Test Name**: Public tracking by token  
**Priority**: High  
**Status**: Pass

**Steps**:
1. From homepage, click "Track Report"
2. Enter valid tracking token from submitted report
3. Click "Query Database"

**Expected Result**:
- Result box displays report details
- Status badge shows current status
- Timeline shows status history with dates

**Actual Result**: Matches expected

---

## Test Case 7: Public Timeline Display

**Test Name**: Timeline renders all status changes  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Admin updates report status multiple times (submitted → verified → assigned)
2. From homepage (no login), track the report
3. Verify timeline shows all transitions

**Expected Result**:
- Timeline shows submitted, verified, assigned in order
- Each entry has colored dot and timestamp

**Actual Result**: Matches expected

---

## Test Case 8: Admin Authentication

**Test Name**: Admin login and access control  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Navigate to `admin/index.php` without logging in
2. Observe behavior
3. Login with `admin@ecowatch.gov` / `admin123`

**Expected Result**:
- Unauthenticated access redirects to `login.php`
- Admin login redirects to `admin/index.php`
- Dashboard shows 5 summary cards with stats

**Actual Result**: Matches expected

---

## Test Case 9: Admin Status Update

**Test Name**: Admin updates report status with notes  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Login as admin
2. View incident queue
3. Click report row to open detail modal
4. Change status from "Submitted" to "Verified"
5. Add note: "Verified via satellite imagery"
6. Click "Update"

**Expected Result**:
- Modal closes
- Table refreshes showing new status
- History timeline includes note
- Database confirms notes saved

**Actual Result**: Matches expected

---

## Test Case 10: CSRF Rejection

**Test Name**: API rejects requests without valid CSRF token  
**Priority**: High  
**Status**: Pass

**Steps**:
1. Open browser DevTools Network tab
2. Attempt to submit report with missing/invalid CSRF token
3. Observe response

**Expected Result**:
- Server returns 400 status
- JSON response: `{"success": false, "message": "Invalid CSRF token."}`
- Report is not created in database

**Actual Result**: Matches expected

---

## Test Case 11: Profile Update

**Test Name**: Citizen updates profile information  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Login as citizen
2. Navigate to "Profile Settings"
3. Change name to "Updated Name"
4. Change email to `newemail@example.com`
5. Click "Update Profile"

**Expected Result**:
- Alert shows success message
- Page reloads with updated name
- Dashboard greeting shows new name

**Actual Result**: Matches expected

---

## Test Case 12: Password Change

**Test Name**: Citizen changes password  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Login as citizen
2. Navigate to "Profile Settings"
3. Enter current password
4. Enter new password (minimum 8 characters)
5. Confirm new password
6. Click "Change Password"

**Expected Result**:
- Alert shows success message
- Can log in with new password
- Old password no longer works

**Actual Result**: Matches expected

---

## Test Case 13: Logout

**Test Name**: Secure logout with CSRF validation  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Login as any user
2. Click "Logout" in dashboard sidebar
3. Observe redirect

**Expected Result**:
- Redirect to `login.php` → `index.html`
- Session is destroyed
- Cannot access protected pages without logging in again

**Actual Result**: Matches expected

---

## Test Case 14: My Reports Access

**Test Name**: Citizen views submitted reports  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Login as citizen with existing reports
2. Navigate to "My Reports"

**Expected Result**:
- List shows all user's reports
- Each card displays tracking token, category, severity, status badge
- "View Details" button opens modal with timeline

**Actual Result**: Matches expected

---

## Test Case 15: Admin Search and Filter

**Test Name**: Admin filters reports by status and barangay  
**Priority**: Medium  
**Status**: Pass

**Steps**:
1. Login as admin
2. In queue, select status filter: "Verified"
3. Select barangay filter: "Barangay 1"
4. Click "Apply Filters"

**Expected Result**:
- Table shows only matching reports
- Pagination updates correctly
- Results match filter criteria

**Actual Result**: Matches expected

---

## Summary

| Status | Count | Description |
|--------|-------|-------------|
| Pass | 15 | All critical and medium priority tests pass |
| Fail | 0 | No functional failures detected |
| Blocked | 0 | No blockers |
