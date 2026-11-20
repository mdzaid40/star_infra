-- =====================================================================
-- STAR INFRA ENGINEERING — MySQL database (import with phpMyAdmin)
-- Hostinger: hPanel → Databases → phpMyAdmin → select your database → Import → choose this file → Go
-- Compatible with MySQL 5.7+ / 8.x and MariaDB 10.3+. Safe to import again (existing rows are kept).
--
-- Starter admin login:  admin@example.com  /  ChangeMe@123
-- You will be asked to change the email and password after the first login.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+05:30';
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------- Tables
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  excerpt TEXT NULL,
  content MEDIUMTEXT NULL,
  featured_image VARCHAR(255) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  meta_title VARCHAR(200) NULL,
  meta_description VARCHAR(300) NULL,
  author_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_status_published (status, published_at),
  KEY idx_posts_author (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(150) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  category VARCHAR(100) NULL,
  icon VARCHAR(10) NULL,
  short_description VARCHAR(300) NULL,
  description TEXT NULL,
  features TEXT NULL,
  image VARCHAR(255) NULL,
  image_alt VARCHAR(200) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_services_slug (slug),
  KEY idx_services_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gallery (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  image VARCHAR(255) NOT NULL,
  caption VARCHAR(200) NULL,
  alt_text VARCHAR(200) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_gallery_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NULL,
  service VARCHAR(150) NULL,
  message TEXT NOT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  mail_sent TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_enquiries_created (created_at),
  KEY idx_enquiries_read (is_read),
  KEY idx_enquiries_ip_created (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(190) NULL,
  attempted_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_login_ip_time (ip, attempted_at),
  KEY idx_login_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- Site settings
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('site_name', 'STAR INFRA ENGINEERING'),
('site_tagline', 'Oil & Gas • Pipeline • EPC • Infrastructure'),
('footer_about', 'Practical engineering and infrastructure support for industrial, utility and construction projects.'),
('footer_slogan', 'Engineering with purpose. Delivering with care.'),
('contact_email', 'info@yourdomain.com'),
('contact_phone', '+91 98765 43210'),
('contact_whatsapp', ''),
('contact_address', 'New Delhi, India'),
('map_embed_url', ''),
('notify_email', 'info@yourdomain.com'),
('hero_eyebrow', 'ENGINEERING & INFRASTRUCTURE PARTNER'),
('hero_title', 'Building the systems\nthat keep *industry moving.*'),
('hero_text', 'Integrated plant, pipeline, civil and industrial maintenance services — planned carefully, executed responsibly.'),
('hero_image', 'assets/images/plant-team.webp'),
('intro_title', 'Grounded in experience.\n*Focused on execution.*'),
('intro_lead', 'We bring together the people, processes and technical capabilities needed to deliver demanding industrial and infrastructure work.'),
('intro_text', 'From pipeline installation and mechanical works to civil construction and ongoing maintenance, our approach is built around safe practices, clear coordination and dependable delivery.'),
('statement_title', 'Good engineering is more than\ngetting the job done. It is doing it\n*the right way.*'),
('cta_title', 'Let\'s build something\n*that works.*'),
('home_show_blog', '1'),
('about_title', 'Practical thinking.\n*Solid execution.*'),
('about_subtitle', 'Engineering and infrastructure services shaped around real project requirements.'),
('about_lead', 'Our firm undertakes plant and mechanical works, gas pipeline infrastructure, civil construction and industrial maintenance for public and private sector projects.'),
('about_body', 'We understand that successful project delivery depends on more than technical scope. It requires careful planning, disciplined site coordination, attention to quality, and a strong commitment to safety and statutory compliance.\n\nWe aim to be a dependable contracting partner — responsive to project needs and focused on completing each assignment to its agreed scope and requirements.'),
('projects_title', 'Ready for the demands\nof *real-world projects.*'),
('projects_subtitle', 'Flexible contracting support across tenders, work orders and multi-discipline assignments.'),
('projects_note', 'Project photographs, client names, certifications and completed-project references can be added here once verified and approved for publication.'),
('meta_description', 'Plant, mechanical, gas pipeline, civil construction and industrial maintenance services across India.'),
('og_image', 'assets/images/plant-team.webp'),
('social_linkedin', ''),
('social_facebook', ''),
('social_instagram', ''),
('social_youtube', ''),
('head_scripts', '');

-- ---------------------------------------------------------------- Services
INSERT IGNORE INTO services (id, title, slug, category, icon, short_description, description, features, image, image_alt, sort_order, is_active, created_at, updated_at) VALUES
(1, 'Plant & Mechanical Works', 'mechanical', 'INDUSTRIAL / MECHANICAL', '⌁', 'Fabrication, erection, piping, structural works, testing and shutdown support.', 'Execution support for plant installations, upgrades, shutdowns and associated mechanical packages.', 'Fabrication and erection\nPiping and structural works\nTanks and vessel works\nHydro testing, maintenance and shutdown works', 'assets/images/plant-team.webp', 'Engineers reviewing plans at an industrial plant', 10, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
(2, 'Steel Gas Pipeline Works', 'pipelines', 'GAS INFRASTRUCTURE', '⌇', 'Welding, NDT coordination, coating, trenching, testing and commissioning.', 'Pipeline construction activities from site preparation through testing, commissioning and handover documentation.', 'Welding, stringing and radiographic testing (RT)\nCoating, trenching, lowering and backfilling\nHydro testing and commissioning\nHorizontal directional drilling (HDD)', 'assets/images/steel-pipeline-welding.webp', 'Steel gas pipeline welding at a construction site', 20, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
(3, 'MDPE Pipeline Works', 'mdpe', 'DISTRIBUTION NETWORKS', '⌘', 'Pipeline laying, jointing, testing and associated reinstatement works.', 'Installation and associated works for medium-density polyethylene gas distribution pipelines.', 'Trenching and pipeline laying\nJointing and related installation activities\nTesting and commissioning\nBackfilling and associated works', 'assets/images/mdpe-pipeline-trenching.webp', 'Pipeline installation in an excavated trench', 30, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
(4, 'GI Pipeline Works', 'gi', 'PIPELINE INSTALLATION', '⟷', 'Supply, installation, jointing, testing and commissioning of GI lines.', 'Supply and installation services for GI pipeline systems in line with project specifications.', 'Supply and material coordination\nLaying and installation\nJointing and testing\nCommissioning support', 'assets/images/gi-pipes.webp', 'Industrial steel pipes ready for installation', 40, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
(5, 'Civil Construction Works', 'civil', 'CIVIL / CONSTRUCTION', '▥', 'Excavation, earthwork, foundations, concreting and road restoration.', 'Supporting infrastructure and industrial projects with essential civil and restoration activities.', 'Excavation and earthwork\nConcreting and foundation works\nRoad restoration\nCivil repair and maintenance', 'assets/images/civil-construction.webp', 'Civil construction and foundation work', 50, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00'),
(6, 'Industrial Maintenance Works', 'maintenance', 'ASSET SUPPORT', '⚙', 'Planned and responsive civil, mechanical, plant and pipeline maintenance.', 'Maintenance support across civil, mechanical, plant and gas pipeline infrastructure.', 'Routine and corrective maintenance\nPlant and mechanical upkeep\nPipeline maintenance support\nShutdown and allied maintenance activities', 'assets/images/industrial-maintenance.webp', 'Technician inspecting industrial pipeline valves', 60, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

-- ---------------------------------------------------------------- Gallery (Capabilities page)
INSERT IGNORE INTO gallery (id, image, caption, alt_text, sort_order, is_active, created_at) VALUES
(1, 'assets/images/plant-team.webp', 'Plant & engineering coordination', 'Engineering team at industrial facility', 10, 1, '2026-01-01 00:00:00'),
(2, 'assets/images/steel-pipeline-welding.webp', 'Steel pipeline construction', 'Steel pipeline welding', 20, 1, '2026-01-01 00:00:00'),
(3, 'assets/images/civil-construction.webp', 'Civil & infrastructure works', 'Civil foundation construction', 30, 1, '2026-01-01 00:00:00');

-- ---------------------------------------------------------------- Starter admin (password: ChangeMe@123)
INSERT IGNORE INTO users (id, name, email, password_hash, created_at) VALUES
(1, 'Administrator', 'admin@example.com', '$2y$10$un4rByu/1N3/jHA4V3TcVeEWReEZpGWeJ/UdZq3H3yR3pqYpmTFxG', '2026-01-01 00:00:00');

-- ---------------------------------------------------------------- Welcome blog post (draft — edit or delete it in Admin → Blog posts)
INSERT IGNORE INTO posts (id, title, slug, excerpt, content, featured_image, status, published_at, meta_title, meta_description, author_id, created_at, updated_at) VALUES
(1, 'Welcome to our blog', 'welcome-to-our-blog', 'Project updates, safety practices and insights from our engineering and infrastructure work.', '<p>Welcome to our new blog. We will use this space to share project updates, safety practices and insights from pipeline, mechanical, civil and maintenance works.</p><h2>What to expect</h2><ul><li>Project milestones and site updates</li><li>Notes on quality, testing and commissioning</li><li>Company news and announcements</li></ul><p>Have a question or a project in mind? <a href="/contact.php">Get in touch with our team</a>.</p>', 'assets/images/steel-pipeline-welding.webp', 'draft', NULL, NULL, NULL, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00');

SET FOREIGN_KEY_CHECKS = 1;
