# Cafe – Food Ordering & Restaurant Management System

Cafe is a responsive food-ordering website made with Core PHP and MySQL. It includes customer ordering, cart and checkout, order tracking, admin management, kitchen workflow, delivery workflow, coupons, reviews, reports and multiple user roles.

# Beginner-friendly Windows installation guide

You do not need programming knowledge to run this project. Follow every step in the same order.

## What you need

1. A Windows computer
2. An internet connection for downloading XAMPP and displaying online food images
3. XAMPP
4. Google Chrome, Microsoft Edge or Firefox

You do **not** need to install PHP, MySQL, Node.js or Composer separately. XAMPP already includes PHP, Apache, MariaDB/MySQL and phpMyAdmin.

## Step 1: Download XAMPP

1. Open your web browser.
2. Visit https://www.apachefriends.org/.
3. Download XAMPP for Windows.
4. Select a version that includes PHP 8.1 or newer.
5. Wait for the download to finish.

## Step 2: Install XAMPP

1. Double-click the downloaded XAMPP installer.
2. If Windows displays a permission warning, click **Yes**.
3. Keep these components selected:
   - Apache
   - MySQL
   - PHP
   - phpMyAdmin
4. Click **Next**.
5. Install XAMPP in the default location:

   `C:\xampp`

6. Continue clicking **Next** until installation finishes.
7. Open the **XAMPP Control Panel**.

## Step 3: Copy the project into XAMPP

1. If the project was downloaded as a ZIP file, right-click it and select **Extract All**.
2. Rename the extracted project folder to:

   `food-ordering-website`

3. Open File Explorer.
4. Go to:

   `C:\xampp\htdocs`

5. Copy the complete `food-ordering-website` folder into the `htdocs` folder.
6. The final location must look exactly like this:

   `C:\xampp\htdocs\food-ordering-website`

7. Open that folder and confirm that files such as `index.php`, `config.php` and `database` are visible.

Do not accidentally create an extra folder level such as:

`C:\xampp\htdocs\food-ordering-website\food-ordering-website`

## Step 4: Start Apache and MySQL

1. Open the **XAMPP Control Panel**.
2. Find **Apache** and click its **Start** button.
3. Find **MySQL** and click its **Start** button.
4. Apache and MySQL should become green.
5. Do not close the XAMPP Control Panel while using the website. You can minimize it.

## Step 5: Create and import the database

1. Make sure Apache and MySQL are green in XAMPP.
2. Open your browser.
3. Visit:

   http://localhost/phpmyadmin

4. Click the **Import** tab at the top.
5. Click **Choose File**.
6. Select this file from the project:

   `C:\xampp\htdocs\food-ordering-website\database\food_ordering.sql`

7. Scroll to the bottom of the phpMyAdmin page.
8. Click **Import** or **Go**.
9. Wait until phpMyAdmin shows a success message.
10. The `food_ordering` database will be created automatically. You do not need to create it manually.

Important: importing this SQL file again resets the demo database and removes changes made through the website.

## Step 6: Open the website

Open this address in your browser:

http://localhost/food-ordering-website/

The Cafe customer homepage should now appear.

## Step 7: Sign in with demo accounts

Click **Sign in** on the website.

The password for every demo account is:

`password`

### Customer

- Email: `customer@demo.com`
- Password: `password`
- Use this account to browse food, add items to the cart, apply coupons, checkout, track orders and write reviews.

### Administrator

- Email: `admin@demo.com`
- Password: `password`
- Use this account to manage orders, menu items, food images, customers, staff, coupons, reviews, reports and restaurant settings.

### Kitchen staff

- Email: `kitchen@demo.com`
- Password: `password`
- Use this account to accept kitchen jobs, start cooking and mark orders as ready.

### Delivery staff

- Email: `delivery@demo.com`
- Password: `password`
- Use this account to start deliveries, open directions and mark orders as delivered.

### Cashier/staff

- Email: `staff@demo.com`
- Password: `password`
- Use this account to view orders and perform permitted front-desk actions.

## Step 8: Test the complete order flow

Use this sequence to demonstrate the project:

1. Sign in as the customer.
2. Open the menu.
3. Add food to the cart.
4. Increase or decrease quantities.
5. Apply coupon `WELCOME100` when the minimum order value is reached.
6. Complete checkout.
7. Sign out.
8. Sign in as the administrator and confirm the new order.
9. Sign out.
10. Sign in as kitchen staff.
11. Start cooking the order.
12. Mark the order as ready.
13. Sign out.
14. Sign in as delivery staff.
15. Start the delivery.
16. Mark the order as delivered.
17. Sign back in as the customer to see the delivered order and submit a review.

## How to run the project next time

You only need to import the database during the first installation.

Every other time:

1. Open the XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.
4. Open http://localhost/food-ordering-website/ in your browser.

## How to stop the project

1. Open the XAMPP Control Panel.
2. Click **Stop** next to Apache.
3. Click **Stop** next to MySQL.
4. Close XAMPP.

Always stop MySQL properly before shutting down the computer to reduce the risk of database corruption.

# Common problems and solutions

## The website says “Database connection failed”

1. Open XAMPP.
2. Confirm that MySQL is green.
3. Confirm that `food_ordering.sql` was imported successfully.
4. Open phpMyAdmin and check that a database named `food_ordering` exists.

## localhost does not open

1. Confirm that Apache is green in XAMPP.
2. Confirm that the project is inside `C:\xampp\htdocs`.
3. Confirm that the folder is named exactly `food-ordering-website`.
4. Try opening http://localhost/ in the browser.

## Apache does not start

Another application may already be using port 80.

1. Close applications such as Skype, IIS, Web Deployment Service or another local web server.
2. If Laravel Herd is running, exit Herd temporarily.
3. Restart the XAMPP Control Panel as Administrator.
4. Try starting Apache again.

## MySQL does not start

1. Close any separately installed MySQL or MariaDB application.
2. Restart XAMPP as Administrator.
3. Do not delete files from `C:\xampp\mysql\data`.
4. Check the MySQL logs from the XAMPP Control Panel if it still fails.

## Port 80 is not available

You can change Apache to port 8080:

1. In XAMPP, click **Config** next to Apache.
2. Open `httpd.conf`.
3. Change `Listen 80` to `Listen 8080`.
4. Change `ServerName localhost:80` to `ServerName localhost:8080`.
5. Save the file and restart Apache.
6. Open:

   http://localhost:8080/food-ordering-website/

## Food images are not showing

Most demo food images are loaded from Unsplash.

1. Confirm that the computer is connected to the internet.
2. Disable browser extensions that block images.
3. Refresh the page with `Ctrl + F5`.

Uploaded menu images are stored locally in:

`uploads\menu`

The upload limit is 2 MB. Supported formats are JPG, PNG, WebP and GIF.

## The page looks old after an update

Press `Ctrl + F5` to perform a hard refresh and clear the browser’s cached CSS and JavaScript.

## phpMyAdmin reports an import error

1. Confirm MySQL is running.
2. Make sure you selected `database\food_ordering.sql`.
3. Do not edit the SQL file before importing it.
4. If the database already contains broken test data, select the `food_ordering` database, delete it, and import the SQL file again.

# Database configuration

The project is already configured for a normal XAMPP installation:

- Host: `127.0.0.1`
- Database: `food_ordering`
- Username: `root`
- Password: empty

These values are stored in `config.php`.

If your MySQL username, password or port is different, update `config.php` before opening the website.

# Important notes

- Card and UPI payments are demonstration payments. They do not charge real money.
- Connect a real provider such as Razorpay before accepting online payments.
- Change every demo password before publishing the project on a public server.
- Do not use the default `root` database account on a production server.
- Online food and avatar images require an internet connection.
- Uploaded menu images work without internet access.
- Keep regular database backups if you add real data.

# Main features

- Responsive customer storefront
- Menu search, categories, vegetarian filter and sorting
- Cart with live quantity and total calculation
- Coupons, tax, delivery fee and minimum-order rules
- Delivery and pickup checkout
- Customer favorites, notifications, order history and reviews
- Visual order tracking
- Administrator dashboard with live database values
- Order filtering and CSV export
- Menu create, edit, archive, availability and image upload
- Staff account and role management
- Coupon management
- Review replies and moderation
- Persistent restaurant settings
- Kitchen order queue
- Delivery assignment and workflow
- Role-based access control, CSRF protection and prepared database queries
