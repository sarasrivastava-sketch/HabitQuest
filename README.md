# HabitQuest – Gamified Habit Tracker

**A WordPress-based habit tracking website that makes building consistent habits more engaging through gamification.**

## Project Information

- **Project Name:** HabitQuest – Gamified Habit Tracker
- **Subject:** Web UI & Content Management
- **Platform:** WordPress
- **Theme:** Astra
- **Development Environment:** XAMPP (Localhost)
- **Database:** MySQL
- **Programming Language:** PHP

### Group Members

- Sara Srivastava – 23030124059
- Rajdeep Kumar – 23030124044
- Sourav Ojha – 23030124150

**Course:** BCA Semester 7 | Division A

## 1. Introduction

HabitQuest is a WordPress-based gamified habit tracker designed to help users develop and maintain positive daily habits. The website combines habit tracking with game-inspired elements such as experience points (XP), levels, streaks, achievements and progress analytics.

The project uses built-in WordPress blocks for page layouts and custom PHP plugins for dynamic functionality, data processing and user-specific habit tracking.

## 2. Problem Statement

Many students and young users find it difficult to maintain good habits because motivation changes over time and conventional habit trackers can become repetitive. HabitQuest addresses this problem by introducing game-inspired rewards and progress tracking to make consistent daily actions more engaging.

## 3. Objectives

- Develop a functional, multi-page WordPress website.
- Provide user registration and login.
- Allow users to create and track daily habits.
- Encourage consistency through streaks, XP and levels.
- Recognise progress through achievements.
- Display weekly activity and habit completion statistics.
- Store user-specific data in a MySQL database.

## 4. Main Features

- **User Authentication:** Registration and login through WordPress user accounts.
- **Habit Creation:** Create habits with a title, description and category.
- **Daily Completion:** Mark habits as completed or undo the current day's completion.
- **XP System:** Earn experience points by completing habits.
- **Levels:** Progress through levels based on accumulated XP.
- **Streak Tracking:** Track consecutive days of habit completion.
- **Achievements:** Unlock awards by reaching activity and progress milestones.
- **Progress Analytics:** View completion rates, weekly activity, total XP and frequently completed habits.
- **Archive and Restore:** Archive habits and restore them later without deleting their history.
- **Tips and Quote Drop:** Read habit-building guidance and request motivational quotes.

## 5. Technologies Used

| Technology | Purpose |
|---|---|
| WordPress | Content management and website framework |
| Astra | Website theme and responsive styling |
| WordPress Blocks | Page layouts and visual content |
| PHP | Dynamic functionality and server-side processing |
| MySQL | Storage of user accounts, habits and completion records |
| HTML and CSS | Website structure and presentation |
| JavaScript / AJAX | Interactive actions and asynchronous requests |
| XAMPP | Local development environment |
| phpMyAdmin | Database management and SQL export |

## 6. Project Structure

- `wordpress/` – WordPress installation files, including themes and plugins.
- `database/` – Exported SQL database file.
- `README.md` – Project overview and setup instructions.
- `.gitignore` – Excludes local configuration and unnecessary files.

The custom HabitQuest plugins provide authentication, habit tracking and tips functionality through PHP and WordPress shortcodes.

## 7. Website Pages

- **Home:** Introduction to HabitQuest and onboarding content.
- **Login:** User authentication.
- **Sign Up:** New user registration.
- **Dashboard:** Create habits and manage daily completion.
- **Progress:** View XP, levels, completion statistics and weekly activity.
- **Awards:** View locked and unlocked achievements.
- **Tips:** Access habit-building guidance and motivational quotes.

## 8. Database Design

The website uses the WordPress database and custom tables for habit tracking.

- `wp_hq_habits` – Stores habit details, ownership, categories and active/archive status.
- `wp_hq_habit_logs` – Stores daily habit completion records and their associated users.

The `wp_` prefix shown above is an example. The actual prefix depends on the local WordPress configuration.

User-specific queries ensure that each authenticated user accesses their own habit data and progress.

## 9. Installation and Setup

### Prerequisites

- XAMPP with Apache and MySQL.
- A compatible PHP version.
- A web browser.
- phpMyAdmin.

### Step 1: Set Up WordPress Files

1. Start Apache and MySQL from the XAMPP Control Panel.
2. Extract the project files into the XAMPP `htdocs` directory.
3. Place the WordPress installation at `htdocs/habitquest/wordpress/`, or adjust the URL to match the chosen folder.
4. Ensure that the required HabitQuest plugins and theme files are present.

### Step 2: Create the Database

1. Open `http://localhost/phpmyadmin/`.
2. Create a MySQL database for the project.
3. Select the newly created database.
4. Open the **Import** tab.
5. Choose `database/habitquest_database.sql` and start the import.

If the database export is structure-only, the imported database may not contain the original users, habits or other records.

### Step 3: Configure WordPress

1. Create a local `wp-config.php` using the supplied WordPress configuration template or a fresh WordPress configuration.
2. Set the database name, username, password and host to match the local XAMPP setup.
3. Keep database credentials private; do not commit them to GitHub.
4. If necessary, update the WordPress site URL in the database to match the local installation URL.

For a default XAMPP installation, the database host is commonly `localhost` and the database username is commonly `root`. The password and other settings depend on the local installation.

### Step 4: Open the Website

Visit:

`http://localhost/habitquest/wordpress/`

If the WordPress files are placed in a different directory, use the corresponding local URL.

Log in with a local test account or register a new account if registration is enabled.

**Note:** The SQL export and WordPress files must correspond to the same project version. Imported data, database table prefixes, plugin availability and local URL settings may require adjustment.

## 10. Testing

The following functional areas were considered during project testing:

- User registration and login.
- Habit creation and dashboard display.
- Daily completion and undo actions.
- XP and level calculations.
- Streak updates.
- Habit archiving and restoration.
- Progress analytics.
- Achievement unlocking.
- Quote refresh and fallback behaviour.
- Separation of data between user accounts.

## 11. Scope and Future Enhancements

HabitQuest demonstrates how WordPress can be extended beyond static content pages using PHP plugins and database-backed functionality.

Possible future enhancements include:

- Habit editing.
- Calendar-based activity history.
- Email or push notifications and reminders.
- Additional achievements and milestones.
- User profiles with personalised statistics.

## 12. Conclusion

HabitQuest combines WordPress content management with custom PHP functionality to provide a gamified habit tracking experience. The project demonstrates the use of WordPress blocks, themes, user authentication, custom plugins, shortcodes and MySQL database operations to build a dynamic website.

## 13. References

- WordPress Documentation: https://wordpress.org/documentation/
- WordPress Developer Resources: https://developer.wordpress.org/
- Astra Theme: https://wpastra.com/
- PHP Documentation: https://www.php.net/docs.php
- MySQL Documentation: https://dev.mysql.com/doc/
- XAMPP: https://www.apachefriends.org/

---

**Academic Project:** Web UI & Content Management  
**Institution:** Symbiosis Institute of Computer Studies and Research (SICSR)