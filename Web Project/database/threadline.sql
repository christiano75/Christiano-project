-- ==================================================================
-- Threadline — online clothing store
-- MySQL / MariaDB schema + demo data
-- Import through phpMyAdmin (Import tab) or: mysql < threadline.sql
-- ==================================================================

CREATE DATABASE IF NOT EXISTS threadline
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE threadline;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------------
-- Users (customers and administrators)
-- ------------------------------------------------------------------
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(30) DEFAULT NULL,
    address       VARCHAR(255) DEFAULT NULL,
    city          VARCHAR(100) DEFAULT NULL,
    postcode      VARCHAR(20) DEFAULT NULL,
    role          ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE = InnoDB;

-- ------------------------------------------------------------------
-- Categories
-- ------------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(60) NOT NULL,
    slug        VARCHAR(60) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE = InnoDB;

-- ------------------------------------------------------------------
-- Products
-- ------------------------------------------------------------------
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(140) NOT NULL,
    description TEXT NOT NULL,
    material    VARCHAR(120) DEFAULT NULL,
    care        VARCHAR(255) DEFAULT NULL,
    price       DECIMAL(10, 2) NOT NULL,
    sale_price  DECIMAL(10, 2) DEFAULT NULL,
    sizes       VARCHAR(60) NOT NULL DEFAULT 'One Size',
    stock       INT NOT NULL DEFAULT 0,
    image       VARCHAR(120) NOT NULL,
    featured    TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------------
-- Orders
-- ------------------------------------------------------------------
CREATE TABLE orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    reference      VARCHAR(20) NOT NULL,
    full_name      VARCHAR(100) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    phone          VARCHAR(30) NOT NULL,
    address        VARCHAR(255) NOT NULL,
    city           VARCHAR(100) NOT NULL,
    postcode       VARCHAR(20) NOT NULL,
    subtotal       DECIMAL(10, 2) NOT NULL,
    shipping       DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total          DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(30) NOT NULL DEFAULT 'card',
    status         ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled')
                   NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_reference (reference),
    KEY idx_orders_user (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------------
-- Order line items (product name copied so history survives edits)
-- ------------------------------------------------------------------
CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED DEFAULT NULL,
    product_name VARCHAR(120) NOT NULL,
    size         VARCHAR(20) NOT NULL DEFAULT 'One Size',
    unit_price   DECIMAL(10, 2) NOT NULL,
    quantity     INT NOT NULL DEFAULT 1,
    KEY idx_items_order (order_id),
    CONSTRAINT fk_items_order FOREIGN KEY (order_id)
        REFERENCES orders (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------------
-- Contact messages
-- ------------------------------------------------------------------
CREATE TABLE messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    subject    VARCHAR(150) NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ==================================================================
-- Demo data
-- ==================================================================

-- Passwords:
--   admin@threadline.test  →  Admin@12345
--   alex@example.com       →  Password123
--   priya@example.com      →  Password123
INSERT INTO users (full_name, email, password_hash, phone, address, city, postcode, role) VALUES
('Maya Chen',   'admin@threadline.test', '$2y$10$OQi.8vbxZd.ySiSFIKaCR.pVNcilbgXUZUmZWt96EJ6v2n0ytNK3W', '+60 3-2000 1234', '18 Jalan Tun Perak', 'Kuala Lumpur', '50050', 'admin'),
('Alex Rivera', 'alex@example.com',      '$2y$10$HdGOjVY0cVM5WD.c5Xl9U.p1pzdOFTqh1J06w6ttlJNVYrH/rQyVu', '+60 12-345 6789', '42A Jalan Bangsar', 'Kuala Lumpur', '59100', 'customer'),
('Priya Nair',  'priya@example.com',     '$2y$10$HdGOjVY0cVM5WD.c5Xl9U.p1pzdOFTqh1J06w6ttlJNVYrH/rQyVu', '+60 17-888 2211', '7 Lorong Gurney', 'Kuala Lumpur', '50900', 'customer');

INSERT INTO categories (name, slug, description) VALUES
('Women',       'women',       'Considered layers, dresses and denim for every day.'),
('Men',         'men',         'Relaxed shirting, knitwear and trousers in natural fibres.'),
('Accessories', 'accessories', 'Bags, hats and small things that finish the look.'),
('Footwear',    'footwear',    'Low-key shoes made for long walks and slow Sundays.');

INSERT INTO products (category_id, name, slug, description, material, care, price, sale_price, sizes, stock, image, featured) VALUES
(2, 'Linen Camp Shirt', 'linen-camp-shirt',
 'A warm-weather staple cut from breathable European linen. The camp collar sits flat, the body is relaxed and the buttons are corozo nut. Wear it open over a tee or buttoned for dinner.',
 '100% European linen', 'Cold machine wash. Line dry in shade. Warm iron.', 58.00, NULL, 'S,M,L,XL', 24, 'linen-shirt.jpg', 1),

(2, 'Oversized Fleece Hoodie', 'oversized-fleece-hoodie',
 'Our best-selling hoodie, brushed on the inside and garment-dyed for a lived-in finish. Dropped shoulders and a generous hood make it the piece you reach for first.',
 '80% organic cotton, 20% recycled polyester', 'Machine wash cold, inside out. Tumble dry low.', 74.00, 59.00, 'S,M,L,XL,XXL', 18, 'oversized-hoodie.jpg', 1),

(1, 'Midi Wrap Dress', 'midi-wrap-dress',
 'A fluid midi dress with a true wrap front and a self-tie waist. Cut on the bias so it moves with you, with a hem that finishes just below the knee.',
 'Lenzing™ EcoVero™ viscose', 'Hand wash cold or delicate cycle. Dry flat.', 96.00, NULL, 'XS,S,M,L,XL', 12, 'wrap-dress.jpg', 1),

(1, 'Straight Leg Jeans', 'straight-leg-jeans',
 'A high-rise, straight-leg jean in rigid organic denim that softens with wear. Full length, clean hem and a fit that works with boots or sneakers.',
 '100% organic cotton denim', 'Machine wash cold with similar colours. Do not tumble dry.', 88.00, NULL, '24,26,28,30,32', 30, 'straight-jeans.jpg', 1),

(1, 'Wool Blend Coat', 'wool-blend-coat',
 'An easy, unstructured coat in a soft wool blend. Mid-calf length, dropped shoulders and deep pockets — designed to layer over everything.',
 '60% recycled wool, 40% polyester', 'Dry clean only. Steam to refresh.', 189.00, NULL, 'XS,S,M,L', 8, 'wool-coat.jpg', 1),

(1, 'Ribbed Knit Top', 'ribbed-knit-top',
 'A fine-gauge ribbed top with a high neck and a slightly cropped body. Soft enough for layering, structured enough to wear on its own.',
 '95% organic cotton, 5% elastane', 'Machine wash cold. Reshape and dry flat.', 42.00, NULL, 'XS,S,M,L,XL', 40, 'ribbed-knit.jpg', 1),

(4, 'Canvas Low-Top Sneakers', 'canvas-low-top-sneakers',
 'A simple canvas sneaker with a vulcanised rubber sole and a cushioned insole. Made in a factory we have worked with for six years.',
 'Organic cotton canvas, natural rubber sole', 'Spot clean with a damp cloth. Air dry.', 79.00, NULL, '36,37,38,39,40,41,42,43,44', 16, 'canvas-sneakers.jpg', 1),

(3, 'Leather Tote Bag', 'leather-tote-bag',
 'A everyday tote in vegetable-tanned leather that patinas beautifully. Fits a 14-inch laptop, a notebook and a water bottle with room to spare.',
 'Vegetable-tanned full-grain leather', 'Wipe with a soft dry cloth. Condition twice a year.', 128.00, 99.00, 'One Size', 10, 'leather-tote.jpg', 1),

(3, 'Organic Cotton Cap', 'organic-cotton-cap',
 'A six-panel cap in washed organic cotton with an adjustable brass buckle. Low profile, curved brim.',
 '100% organic cotton', 'Hand wash cold. Air dry.', 28.00, NULL, 'One Size', 50, 'cotton-cap.jpg', 0),

(2, 'Wide Leg Linen Trousers', 'wide-leg-linen-trousers',
 'Pull-on linen trousers with a soft elasticated back and a wide, fluid leg. The quiet answer to warm days.',
 '100% washed linen', 'Machine wash cold. Line dry. Warm iron.', 82.00, NULL, 'S,M,L,XL', 22, 'linen-trousers.jpg', 0),

(1, 'Cropped Denim Jacket', 'cropped-denim-jacket',
 'A boxy, cropped take on the classic trucker jacket in mid-wash rigid denim. Silver hardware and two chest pockets.',
 '100% organic cotton denim', 'Machine wash cold, inside out. Air dry.', 110.00, NULL, 'XS,S,M,L', 14, 'denim-jacket.jpg', 0),

(3, 'Ribbed Knit Beanie', 'ribbed-knit-beanie',
 'A snug, double-layer beanie knitted from soft recycled wool. Folded brim, no logo.',
 '70% recycled wool, 30% nylon', 'Hand wash cold. Dry flat.', 26.00, NULL, 'One Size', 35, 'knit-beanie.jpg', 0);

INSERT INTO products (category_id, name, slug, description, material, care, price, sale_price, sizes, stock, image, featured) VALUES
(2, 'Oxford Button-Down', 'oxford-shirt', 'A pale blue Oxford shirt with a button-down collar, chest pocket and a straight hem. The woven cotton has enough structure to wear on its own.', '100% cotton Oxford', 'Machine wash cold. Line dry. Warm iron.', 64.00, NULL, 'S,M,L,XL,XXL', 28, 'oxford-shirt.jpg', 0),
(2, 'Straight-Leg Twill Chinos', 'twill-chinos', 'Sand-coloured chinos with a straight leg, slanted front pockets and a clean hem. The cotton twill has a little stretch through the seat and thighs.', '98% cotton, 2% elastane', 'Machine wash cold with similar colours. Line dry.', 78.00, NULL, '28,30,32,34,36,38', 24, 'twill-chinos.jpg', 0),
(2, 'Merino Crewneck Sweater', 'merino-crewneck', 'A charcoal crewneck in fine merino wool with ribbed cuffs and hem. Cut to sit comfortably over a shirt without adding bulk.', '100% merino wool', 'Hand wash cold. Reshape and dry flat.', 98.00, NULL, 'S,M,L,XL,XXL', 16, 'merino-crewneck.jpg', 0),
(2, 'Canvas Chore Jacket', 'canvas-chore-jacket', 'An olive canvas jacket with three patch pockets and a roomy cut for layering. The sturdy cotton canvas softens with wear.', '100% cotton canvas', 'Machine wash cold. Line dry.', 118.00, NULL, 'S,M,L,XL,XXL', 18, 'canvas-chore-jacket.jpg', 0),
(2, 'Cotton Piqué Polo', 'pique-polo', 'A navy short-sleeve polo with a two-button placket, ribbed collar and small side vents. The textured piqué knit holds its shape.', '100% cotton piqué', 'Machine wash cold. Line dry.', 48.00, NULL, 'S,M,L,XL,XXL', 32, 'pique-polo.jpg', 0);

INSERT INTO products (category_id, name, slug, description, material, care, price, sale_price, sizes, stock, image, featured) VALUES
(4, 'Suede Court Sneakers', 'suede-court-sneakers', 'Soft taupe suede, cream laces and a low profile give these court sneakers a clean everyday shape. A rubber sole adds grip.', 'Suede upper, rubber sole', 'Brush with a suede brush. Air dry if damp.', 89.00, NULL, '36,37,38,39,40,41,42,43,44,45', 22, 'suede-court-sneakers.jpg', 0),
(4, 'Leather Penny Loafers', 'leather-penny-loafers', 'Smooth dark brown leather with a stitched moc toe and classic penny strap. A rubber sole gives this slip-on a little extra grip.', 'Leather upper, rubber sole', 'Wipe clean and condition with leather balm.', 129.00, NULL, '36,37,38,39,40,41,42,43,44,45', 14, 'leather-penny-loafers.jpg', 0),
(4, 'Leather Chelsea Boots', 'leather-chelsea-boots', 'Matte black ankle boots with elastic side panels and pull tabs for easy on and off. The rounded toe leaves room for thicker socks.', 'Leather upper, rubber sole', 'Wipe with a damp cloth. Polish as needed.', 149.00, NULL, '36,37,38,39,40,41,42,43,44,45', 16, 'leather-chelsea-boots.jpg', 0),
(4, 'Two-Strap Footbed Sandals', 'two-strap-footbed-sandals', 'Two adjustable tan suede straps sit over a contoured cork footbed. The lightweight outsole has a textured tread.', 'Suede straps, cork footbed, EVA outsole', 'Spot clean and air dry.', 59.00, NULL, '36,37,38,39,40,41,42,43,44,45', 26, 'two-strap-footbed-sandals.jpg', 0),
(4, 'Mesh Running Sneakers', 'mesh-running-sneakers', 'Breathable off-white mesh with sage accents, a cushioned midsole and rubber tread for everyday runs and walks.', 'Mesh upper, EVA midsole, rubber outsole', 'Spot clean with a soft brush. Air dry.', 99.00, NULL, '36,37,38,39,40,41,42,43,44,45', 20, 'mesh-running-sneakers.jpg', 0);

INSERT INTO products (category_id, name, slug, description, material, care, price, sale_price, sizes, stock, image, featured) VALUES
(3, 'Leather Belt', 'leather-belt', 'A dark brown leather belt with a brushed brass buckle and a single keeper. Wear it with denim or tailored trousers.', 'Full-grain leather, brass buckle', 'Wipe clean. Condition the leather as needed.', 42.00, NULL, 'S,M,L,XL', 24, 'leather-belt.jpg', 0),
(3, 'Wool Fringe Scarf', 'wool-fringe-scarf', 'An oatmeal wool scarf with a fine fringe and enough length to wrap twice.', '100% wool', 'Hand wash cold. Lay flat to dry.', 49.00, NULL, 'One Size', 22, 'wool-fringe-scarf.jpg', 0),
(3, 'Canvas Crossbody Bag', 'canvas-crossbody-bag', 'An olive canvas crossbody with a zip closure, a front pocket and an adjustable webbing strap. Sized for a phone, wallet and keys.', 'Cotton canvas, cotton webbing, metal hardware', 'Spot clean with a damp cloth. Air dry.', 58.00, NULL, 'One Size', 18, 'canvas-crossbody-bag.jpg', 0),
(3, 'Leather Card Holder', 'leather-card-holder', 'A slim cognac leather card holder with three card slots and stitched edges.', 'Full-grain leather', 'Wipe clean with a soft dry cloth.', 32.00, NULL, 'One Size', 30, 'leather-card-holder.jpg', 0),
(3, 'Round Acetate Sunglasses', 'round-acetate-sunglasses', 'Round tortoiseshell frames with brown lenses and gently curved arms.', 'Acetate frame, polycarbonate lenses', 'Clean with a microfiber cloth. Store in a case.', 54.00, NULL, 'One Size', 20, 'round-acetate-sunglasses.jpg', 0);

INSERT INTO products (category_id, name, slug, description, material, care, price, sale_price, sizes, stock, image, featured) VALUES
(1, 'Pleated Midi Skirt', 'pleated-midi-skirt', 'Deep olive midi skirt with fine pleats and a slim waistband.', '100% polyester', 'Machine wash cold. Hang dry.', 72.00, NULL, 'XS,S,M,L,XL', 18, 'pleated-midi-skirt.jpg', 0),
(1, 'Cotton Poplin Blouse', 'cotton-poplin-blouse', 'White cotton poplin blouse with a round neckline, puff sleeves and button cuffs.', '100% cotton', 'Machine wash cold. Warm iron.', 56.00, NULL, 'XS,S,M,L,XL', 24, 'cotton-poplin-blouse.jpg', 0),
(1, 'Button Front Cardigan', 'button-front-cardigan', 'Oatmeal V-neck cardigan with tortoiseshell buttons and ribbed cuffs.', '70% wool, 30% nylon', 'Hand wash cold. Dry flat.', 84.00, NULL, 'XS,S,M,L,XL', 16, 'button-front-cardigan.jpg', 0),
(1, 'Belted Linen Jumpsuit', 'belted-linen-jumpsuit', 'Terracotta sleeveless linen jumpsuit with a self-tie waist and full-length wide legs.', '100% linen', 'Machine wash cold. Line dry.', 108.00, NULL, 'XS,S,M,L,XL', 14, 'belted-linen-jumpsuit.jpg', 0);

-- Demo orders -------------------------------------------------------
INSERT INTO orders (user_id, reference, full_name, email, phone, address, city, postcode, subtotal, shipping, total, payment_method, status, created_at) VALUES
(2, 'TL-100241', 'Alex Rivera', 'alex@example.com', '+60 12-345 6789', '42A Jalan Bangsar', 'Kuala Lumpur', '59100', 138.00, 0.00, 138.00, 'card', 'completed', '2026-10-12 10:24:00'),
(2, 'TL-100387', 'Alex Rivera', 'alex@example.com', '+60 12-345 6789', '42A Jalan Bangsar', 'Kuala Lumpur', '59100', 87.00, 7.95, 94.95, 'card', 'shipped', '2026-11-02 16:40:00'),
(3, 'TL-100412', 'Priya Nair', 'priya@example.com', '+60 17-888 2211', '7 Lorong Gurney', 'Kuala Lumpur', '50900', 187.00, 0.00, 187.00, 'card', 'paid', '2026-11-06 09:05:00'),
(3, 'TL-100430', 'Priya Nair', 'priya@example.com', '+60 17-888 2211', '7 Lorong Gurney', 'Kuala Lumpur', '50900', 189.00, 0.00, 189.00, 'card', 'paid', '2026-11-09 18:12:00');

INSERT INTO order_items (order_id, product_id, product_name, size, unit_price, quantity) VALUES
(1, 3, 'Midi Wrap Dress', 'M', 96.00, 1),
(1, 6, 'Ribbed Knit Top', 'S', 42.00, 1),
(2, 2, 'Oversized Fleece Hoodie', 'L', 59.00, 1),
(2, 9, 'Organic Cotton Cap', 'One Size', 28.00, 1),
(3, 4, 'Straight Leg Jeans', '28', 88.00, 1),
(3, 8, 'Leather Tote Bag', 'One Size', 99.00, 1),
(4, 5, 'Wool Blend Coat', 'M', 189.00, 1);

INSERT INTO messages (name, email, subject, message, is_read, created_at) VALUES
('Hannah Lee', 'hannah@example.com', 'Sizing on the wrap dress',
 'Hi! I am usually a size 10 and wondering whether to take S or M in the midi wrap dress. Thank you!', 0, '2026-11-08 11:20:00'),
('Daniel Tan', 'daniel@example.com', 'Restock request',
 'Could you let me know when the wool blend coat comes back in size L? Happy to be added to a waitlist.', 1, '2026-11-05 14:02:00');
