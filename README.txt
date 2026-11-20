STAR INFRA ENGINEERING — PHP WEBSITE + ADMIN PANEL
===================================================

Plain PHP 8 + MySQL. No Composer, no Node, no build step.
Works on Hostinger shared hosting (Premium / Business / Cloud) out of the box.


SETUP ON HOSTINGER (about 10 minutes)
-------------------------------------
1. PHP version
   hPanel → Websites → Manage → Advanced → PHP Configuration → choose PHP 8.1 or newer.

2. Create the MySQL database
   hPanel → Databases → MySQL Databases → create a database and a user.
   Write down: database name (u123456789_xxx), username (u123456789_xxx) and password.

3. Import the tables and content
   hPanel → Databases → phpMyAdmin → "Enter phpMyAdmin" next to your database
   → Import tab → Choose file → database.sql → Go.
   (Do this from your computer. database.sql does not need to be uploaded to the server.)

4. Upload the website
   hPanel → Files → File Manager → public_html
   Upload the CONTENTS of this folder (index.php, config.php, admin/, assets/ …) into public_html.
   Tip: zip the folder, upload the zip, then right-click → Extract.
   Make sure the hidden ".htaccess" files are uploaded too (root, includes/, storage/, uploads/, admin/_inc/).

5. Connect the database
   Edit public_html/config.php → DATABASE section:
       DB_HOST  'localhost'
       DB_NAME  'u123456789_xxx'
       DB_USER  'u123456789_xxx'
       DB_PASS  'your-db-password'

6. Folder permissions (normally already correct on Hostinger)
   uploads/ and storage/logs/ → 755

7. SSL
   hPanel → Security → SSL → install the free SSL, then enable "Force HTTPS"
   (or uncomment the HTTPS block at the top of .htaccess).

8. Log in to the admin panel
   https://your-domain.com/admin
   Email:    admin@example.com
   Password: ChangeMe@123
   You will be asked to change the email and password immediately.


CONTACT-FORM EMAIL (recommended)
--------------------------------
Every enquiry is saved in Admin → Enquiries, so nothing is lost even if email fails.
To receive email notifications reliably:
  a) hPanel → Emails → create a mailbox, e.g. website@your-domain.com
  b) In config.php fill in:
       SMTP_HOST   'smtp.hostinger.com'
       SMTP_PORT   465
       SMTP_SECURE 'ssl'
       SMTP_USER   'website@your-domain.com'
       SMTP_PASS   'mailbox password'
  c) Admin → Site settings → Contact details → "Send contact-form enquiries to" = where you want to receive them.
Without SMTP the site uses PHP mail() from website@your-domain.


WHAT YOU CAN MANAGE IN THE ADMIN PANEL
--------------------------------------
Dashboard       Counts, latest enquiries and posts
Blog posts      Write / edit / schedule / publish / unpublish / delete posts.
                Rich-text editor with headings, lists, links and image upload.
                Featured image, custom URL slug, SEO title & description, preview for drafts.
Services        Add / edit / hide / reorder / delete services (homepage cards, Services page,
                footer and the contact-form dropdown update automatically).
Gallery         Upload project photos with captions for the Capabilities page.
Enquiries       All contact-form submissions: read, search, mark read/unread, delete, reply.
Site settings   Company name, tagline, phone, email, WhatsApp, address, Google map,
                homepage hero text & image, About page text, footer text, social links,
                default SEO description & share image, Google Analytics code.
Admin users     Add or remove admins, reset passwords.
My account      Change your name, login email and password.

Heading formatting in Site settings:  *word*  = italic accent style,  Enter = line break.


PUBLIC PAGES
------------
/                 Home
/about.php        About
/services.php     Services
/projects.php     Capabilities + gallery
/blog             Blog list            (/blog.php if rewrites are off)
/blog/post-slug   Blog post            (/post.php?slug=post-slug if rewrites are off)
/contact.php      Contact form
/sitemap.xml      Sitemap for Google Search Console
/feed.php         RSS feed


FOLDER STRUCTURE
----------------
config.php              Database, SMTP and site options  (protected from web access)
database.sql            MySQL tables + starting content, import with phpMyAdmin
index.php … contact.php Public pages
blog.php, post.php      Blog
404.php                 Not-found page
sitemap.php, robots.php, feed.php
includes/               Shared PHP code (protected from web access)
  bootstrap.php         Loads everything
  functions.php         Helpers, database access, sessions, CSRF
  content.php           Default content and the list of editable settings
  html.php              Cleans blog HTML (removes scripts / unsafe code)
  mailer.php            SMTP / mail() sender
  uploads.php           Secure image upload and resizing
  header.php, footer.php, partials/
admin/                  Admin panel
  _inc/bootstrap.php    Login, session and layout (protected)
  assets/               Admin CSS / JS
uploads/                Images uploaded from the admin panel (PHP execution disabled)
storage/logs/           PHP error log (protected)
assets/                 Site CSS, JS and images
router.php              Only for local testing:  php -S localhost:8000 router.php


SECURITY NOTES
--------------
- Passwords are hashed (bcrypt). Login is limited to 5 failed attempts per 15 minutes per IP.
- All forms use CSRF tokens. Admin sessions expire after 2 hours of inactivity.
- Changing a password signs that user out everywhere else.
- Blog HTML is cleaned on save; uploads are checked by content and cannot run as code.
- config.php, includes/, storage/ and *.sql files are blocked from the web by .htaccess.
- Keep APP_DEBUG = false on the live site.


RESET ADMIN PASSWORD (if every admin is locked out)
---------------------------------------------------
phpMyAdmin → your database → SQL tab → run:

  UPDATE users SET password_hash = '$2y$10$FtB0z6sRMefnbidW6cly1.OflSVbeA3EpHllmgm3YRiiUA61iqxPC'
  WHERE email = 'your-login@email.com';

That sets the password to  ChangeMe@123  — log in and you will be asked to change it.
Too many failed logins? Also run:  DELETE FROM login_attempts;


BACKUPS
-------
hPanel → Files → Backups (files + database), or phpMyAdmin → Export.
Uploaded images live in /uploads — include that folder in your backups.


IMAGES
------
The six images in assets/images/ are AI-generated illustrations, not photos of real
Star Infra Engineering projects. Replace them with verified company photos
(Admin → Services / Gallery / Site settings → Homepage → Hero image).
Do not publish unverified client names, certifications or project claims.
