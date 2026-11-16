# Aura Jewels – local WooCommerce

Local WordPress + WooCommerce store for artificial jewellery (India / ₹ INR).

| What | URL |
|---|---|
| Store | http://localhost:8090 |
| Admin | http://localhost:8090/wp-admin (user `admin`, password in `.admin-pass`) |
| phpMyAdmin | http://localhost:8091 (user `wp` / `wp_pass_local`) |

## Daily use
```bash
docker compose up -d      # start
docker compose stop       # stop (data is kept)
./wp.sh plugin list       # run any WP-CLI command
```

## What's set up
- WordPress (latest) + WooCommerce, Astra theme with custom brand CSS (`setup/store.css`)
- Prices in ₹, GST 3% included in prices (HSN 7117), India-only selling
- Shipping: free above ₹499, else ₹49 · Payments: Cash on Delivery (Razorpay installed, needs keys)
- Categories: Earrings, Necklaces, Bangles & Bracelets, Rings, Anklets, Jewellery Sets
- Filters (price, colour, material, occasion), "% OFF" badges, ratings, wishlist, WhatsApp button
- Pages: Home, Shop, Sale, About, Contact, Shipping, Returns, FAQ, Privacy, Terms
- Store tweaks plugin: `wordpress/wp-content/mu-plugins/jw-store.php`

## Design (emerald / amber gold / garnet)
Based on the Stitch design in `~/Desktop/stitch_jewelry_color_palette_selector` (screen.png + DESIGN.md).
- Styles: `setup/aura.css` (colour tokens at the top) · Theme behaviour: `wordpress/wp-content/mu-plugins/aura-theme.php`
- Re-apply after edits: `./wp.sh eval-file /setup/setup-aura.php`
- Newsletter sign-ups: **Tools → Aura Subscribers** in wp-admin

## Before going live
1. Fill in every `[bracketed]` placeholder (Contact, About, policies, footer social links).
2. **Settings → General**: site name. **Appearance → Customize**: logo.
3. **WooCommerce → Settings → Payments → Razorpay**: add your Key ID / Secret.
4. **Joinchat** (WhatsApp): set your real number (now `919999999999`).
5. Remove sample products, fake reviews and placeholder images:
   `./wp.sh eval-file /setup/remove-sample-data.php`
6. Replace the homepage banners and category images (Media library).
7. Migrate with **All-in-One WP Migration** (Export → File, then Import on the live site).
