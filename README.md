# AtelierFinalProj
President University IT Class 6 2025 Software Engineering Final Project

## PHP and MySQL storefront

The storefront and seller dashboard run on PHP 8.1+ with PDO MySQL and MySQL 8+.

1. Install PHP with the `pdo_mysql` and `fileinfo` extensions, and MySQL.
2. From a MySQL client, run `Main Web/database/schema.sql`. It creates the `atelier` database and seeds the initial products from the existing image collection.
3. Configure the database connection with `ATELIER_DB_HOST`, `ATELIER_DB_NAME`, `ATELIER_DB_USER`, and `ATELIER_DB_PASS` if the defaults (`127.0.0.1`, `atelier`, `root`, and an empty password) are not appropriate.
4. Start the PHP development server with the `Main Web` folder as its document root:

   ```powershell
   php -S 127.0.0.1:8000 -t "Main Web"
   ```

5. Open `http://127.0.0.1:8000/`. Customers create accounts at **Create account**. To bootstrap the first seller, register an account and promote it once from MySQL:

   ```sql
   USE atelier;
   UPDATE users SET role = 'seller' WHERE email = 'seller@example.com';
   ```

   Sign in with that account. The seller dashboard can add products, create more seller accounts, review customer payment confirmations, and confirm shipping.

Customers add items to their session cart, provide shipping details, and confirm that they submitted payment at checkout. This project does not process real payments: the seller must verify receipt and select **Confirm payment** before the customer's status becomes **Payment confirmed**. Selecting **Confirm shipping** changes it to **In delivery**.

Product image uploads are limited to JPG, PNG, or WebP files up to 5 MB and are stored in `Main Web/uploads`.
