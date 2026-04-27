# SkillForge - Online Skills Marketplace

University-level database project built with `PHP`, `MySQL`, `HTML`, `CSS`, and `JavaScript`.

## Project Overview

SkillForge is a web-based skills marketplace where:

- Users can register as `freelancer`, `client`, or `both`
- Freelancers can enlist skills and take verification tests
- Users must score at least `60%` to get a verified skill badge
- Only verified freelancers can apply to projects
- Clients can post projects, review applicants, and manage statuses
- Notifications keep users updated on test results and application decisions

## Main Features

- Secure registration and login
- Skill enlistment and MCQ-based skill verification
- Verified skill badges
- Project posting and browsing
- Project search and filtering
- Freelancer applications with status updates
- Client dashboard with applicant management
- Notification panel
- Profile editing with avatar upload
- Responsive dashboard UI

## Database Design

The database follows the ERD and is normalized around these main tables:

- `users`
- `skills`
- `skill_tests`
- `test_questions`
- `user_skills`
- `test_results`
- `projects`
- `applications`
- `notifications`

### Relationship Summary

- One user can have many enlisted skills
- One skill can belong to many users through `user_skills`
- One skill has one verification test
- One test has many questions
- One user can attempt many tests
- One client can post many projects
- One project can receive many applications
- One freelancer can apply to many projects

## Folder Structure

```text
skillforge/
|-- index.php
|-- login.php
|-- register.php
|-- dashboard.php
|-- logout.php
|-- database.sql
|-- README.md
|-- includes/
|   `-- config.php
|-- php/
|   |-- auth.php
|   |-- skills.php
|   |-- projects.php
|   `-- user.php
|-- js/
|   `-- app.js
|-- css/
|   `-- style.css
`-- assets/
    |-- images/
    |   `-- .gitkeep
    `-- icons/
        `-- .gitkeep
```

## How To Run In XAMPP

### 1. Copy project into XAMPP

Place the full `skillforge` folder inside:

- Windows: `C:\xampp\htdocs\skillforge`
- Linux: `/opt/lampp/htdocs/skillforge`
- macOS: `/Applications/XAMPP/htdocs/skillforge`

### 2. Start services

Open XAMPP Control Panel and start:

- `Apache`
- `MySQL`

### 3. Create the database

1. Open `http://localhost/phpmyadmin`
2. Create a new database named `skillforge_db`
3. Open the `SQL` tab
4. Paste the full contents of `database.sql`
5. Run the script

### 4. Check database settings

Open [includes/config.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/includes/config.php:1) and verify:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'skillforge_db');
```

If your MySQL password is not empty, update `DB_PASS`.

### 5. Launch the project

Visit:

```text
http://localhost/skillforge/
```

## Demo Flow For Presentation

### Freelancer flow

1. Register as `freelancer`
2. Login and open `My Skills`
3. Enlist one or more skills
4. Take a skill verification test
5. Score at least `6/10`
6. Show the verified badge on the dashboard
7. Browse projects and apply
8. Show the application in `My Applications`

### Client flow

1. Register as `client` or `both`
2. Post a new project
3. Open `My Projects`
4. View applicants
5. Accept one applicant
6. Show how the project moves to `In Progress`
7. Show notifications on both sides

## Image, Logo, and Icon Placement

### Profile pictures

- Uploaded images are stored in `assets/images/`
- User avatars are handled automatically by the profile form

### Custom logo

If you want a custom logo, place it at:

```text
assets/images/logo.png
```

Then replace the text logo in [index.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/index.php:1) and [dashboard.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/dashboard.php:1).

### Hero image or illustration

Recommended location:

```text
assets/images/hero.png
```

You can place it in the landing page hero section if you want a more visual presentation.

### Suggested free resources

- Icons: `Font Awesome`
- Illustrations: `unDraw`
- Illustrations: `Storyset`
- Photos: `Unsplash`
- Photos: `Pexels`
- Fonts: `Google Fonts`
- Fonts: `Fontshare`

## Security Features

- Password hashing with `password_hash()`
- Login verification with `password_verify()`
- Prepared statements using `PDO`
- Session-based authentication
- Input sanitization
- Role-based permission checks
- Duplicate application prevention

## Teacher-Impressing Points

- ERD converted into a practical normalized schema
- Verification test system connected directly with database logic
- Business rule enforced: only verified freelancers can apply
- Clients can manage project and applicant lifecycles
- Notifications make the system feel like a real marketplace
- Dynamic dashboards for different user roles
- Clean, responsive interface suitable for final-year presentation

## Important Files

- SQL schema: [database.sql](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/database.sql:1)
- Shared config: [includes/config.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/includes/config.php:1)
- Auth API: [php/auth.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/php/auth.php:1)
- Skills API: [php/skills.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/php/skills.php:1)
- Projects API: [php/projects.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/php/projects.php:1)
- User API: [php/user.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/php/user.php:1)
- Frontend JS: [js/app.js](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/js/app.js:1)
- Main UI: [dashboard.php](/c:/Users/Tab%20&%20Tech/Downloads/files/skillforge/dashboard.php:1)

## Notes

- The app is designed for local XAMPP use
- Base URLs are now detected automatically, so the project works even if the folder name changes
- If you want, you can still customize colors, logo, and hero image before submission
