# Content Cards

## Project Overview

The **Content Cards** project is a modular Drupal-based system designed to dynamically display content in visually engaging card formats. It allows editors to showcase different types of content—such as articles, events, and resources—using customizable card layouts. This system emphasizes reusability, flexibility, and performance, making it ideal for content-driven websites.

### Features

- Custom content types tailored for card-style presentation.
- Predefined responsive card layouts.
- Easy backend management via Drupal admin interface.
- Extensible module structure for future enhancements.

---

## Getting Started

Follow the steps below to get the project running in your local development environment using **Lando**.

### Prerequisites

- [Lando](https://lando.dev/)
- Composer
- Drupal 10
- Database dump file (`dump.sql.gz`)

---

### Setup Instructions

1. **Start the Lando environment**

   ```bash
   lando start
   ```

2. **Install dependencies**
   If `composer install` is **not automatically triggered** during `lando start`, run:

   ```bash
   lando composer install
   ```

3. **Import the database**
   Ensure you have `dump.sql.gz` in your project directory and run:

   ```bash
   lando db-import dump.sql.gz
   ```

4. **Access the site**
   After setup, access the site using the local URL provided by Lando (typically something like `http://content-cards.lndo.site`).

---

## Project Structure

- **Custom Module**: `content_cards`
- **Main Components**:
  - Custom content types
  - Responsive card layouts
  - Admin-friendly interface for content management
  - Clean, modular code for maintainability
  - Unit test
