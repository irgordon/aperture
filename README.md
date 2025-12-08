# AperturePro CRM

**A privacy-focused, secure, and modern CRM plugin for professional photographers.**

AperturePro allows you to manage leads, invoice clients, proof galleries, and handle contracts directly inside WordPress—without monthly SaaS fees or third-party data tracking.

---

### 👤 Author
**Ian R. Gordon** Website: [iangordon.app](https://iangordon.app)  
GitHub: [https://github.com/irgordon/aperturepro](https://github.com/irgordon/aperturepro)  
Support: [hello@iangordon.app](mailto:hello@iangordon.app)

---

## 🚀 Features

* **Lead Capture Pipeline:** Customizable embedded contact forms that feed directly into a Kanban-style dashboard.
* **Invoicing & Payments:** Integrated Stripe payments with a secure client portal.
* **Gallery Proofing:** High-speed proofing system that bypasses the WP Media Library to keep your site fast. Includes client selection & approval workflows.
* **Questionnaires:** Drag-and-drop form builder for gathering client details before a shoot.
* **Calendar Sync:** Two-way Google Calendar synchronization to prevent double bookings.
* **Privacy First:** "One-Click Export" allows you to download all SQL data and images instantly. You own your data.

## 🛠️ Installation

AperturePro is a modern WordPress plugin that requires building dependencies before use.

### Prerequisites
* WordPress 6.0+
* PHP 7.4+
* Node.js & NPM
* Composer

### Step-by-Step Setup

1.  **Clone the Repository**
    Navigate to your WordPress plugins directory:
    ```bash
    cd wp-content/plugins
    git clone [https://github.com/irgordon/aperturepro.git](https://github.com/irgordon/aperturepro.git)
    cd aperturepro
    ```

2.  **Install PHP Dependencies**
    We use Composer to manage the Stripe and Google SDKs.
    ```bash
    composer install
    ```

3.  **Compile Frontend Assets**
    We use React for the admin dashboard and client portal. You must build the assets.
    ```bash
    npm install
    npm run build
    ```

4.  **Activate Plugin**
    * Log in to your WordPress Admin Dashboard.
    * Go to **Plugins > Installed Plugins**.
    * Activate **AperturePro CRM**.
    * *Note:* Upon activation, the plugin will automatically create the necessary custom database tables and default pages.

## ⚙️ Configuration

Once activated, go to **AperturePro > Settings** in your WordPress dashboard to configure the plugin.

1.  **Branding:** Upload your logo and set your business address/phone for invoices.
2.  **Stripe Integration:** Enter your Stripe **Publishable Key** and **Secret Key**.
3.  **Google Calendar (Optional):** Enter your Google Cloud Client ID and Secret to enable sync.

## 🖥️ Usage & Shortcodes

The plugin automatically creates two pages for you upon activation:

* **Contact Us:** Contains the lead capture form.
    * Shortcode: `[aperture_contact_form]`
* **Client Portal:** Handles invoice payments and gallery proofing.
    * Shortcode: `[aperture_client_portal]`

You can place these shortcodes on any page to render the respective React applications.

## 🔒 Security & Privacy

* **Secure Storage:** Financial data is never stored on your server (handled via Stripe Elements).
* **Data Ownership:** Use the "Export Data" feature in Settings to download a full ZIP archive of your database tables (CSV) and proofing images.
* **Access Control:** API endpoints are secured with WordPress Nonces and Capability checks (`manage_options` for admin actions).

## 📄 License

This project is licensed under the GPL-2.0-or-later license.
