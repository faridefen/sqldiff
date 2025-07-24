# sqldiff
PHP MySQL Database Diff Tool

A simple standalone PHP tool to compare two MySQL databases and generate SQL sync scripts.  
Useful for developers managing schema differences across environments.

---

## 🚀 Features

- Compare schema differences between two MySQL databases
- Outputs differences in:
  - Tables
  - Columns
  - Data types
  - Nullability
- Generates SQL `ALTER` statements to sync schemas
- No external dependencies (single PHP file)
- Runs via browser or CLI
- Configurable via `sqldiffconfig.php`

---

## 🛠 Installation

1. **Clone or download this repository**

2. **Place files in your web root (e.g., Laravel's `public/` directory)**

3. **Edit `sqldiffconfig.php`**

```php
<?php
return [
    'db1' => [
        'host' => 'localhost',
        'dbname' => 'first_database',
        'user' => 'your_username',
        'pass' => 'your_password',
    ],
    'db2' => [
        'host' => 'localhost',
        'dbname' => 'second_database',
        'user' => 'your_username',
        'pass' => 'your_password',
    ],
];
