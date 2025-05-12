# Pulse Engagment Cloud - Form API WordPress Plugin

This plugin allows you to embed a customizable webform on your WordPress site to collect and submit healthcare professional (HCP) data directly to the Pulse Health platform.

## 🔧 Features

- Add a form to any page or post using the `[pulse_health_form]` shortcode
- Admin settings page for configuring:
    - API Key
    - Account ID
    - Form ID
    - Dynamic field selection and ordering
- Built-in client-side and server-side validation
- Automatic API submission to Pulse Health endpoint
- Custom field support via JSON (`customFields`)
- Mobile-friendly, styled form layout
- Optional override of CSS via theme integration

---

## 🧩 Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via WordPress Admin.
2. Activate the plugin.
3. After activation, you will be redirected to the settings page.
4. Enter your:
    - **Account ID**
    - **Form ID**
    - **API Key**
5. Select which fields to include and order them as desired.
6. Use the shortcode `[pulse_health_form]` in any page or post to render the form.

---

## 🧪 Form Submission

All form submissions are sent via POST to:

```
https://api.pulsehealth.tech/forms/{form_id}/submit
```

### Headers:
```
X-Account: {account_id}
Authorization: API-TOKEN {api_key}
Content-Type: application/json
```

### Payload:
The payload matches the HCP contact schema and dynamically includes fields selected in the admin settings, including optional `customFields`.

---

## 🎨 CSS Customization

The plugin loads default styles from:

```
/pulse-health-form/assets/css/style.css
```

### Override Option

To override these styles without editing the plugin directly:

1. In your **active theme folder**, create a file named:

```
pulse-health-form-override.css
```

2. Add your custom styles there.

This override file will automatically be loaded **after** the default styles.

---

## 🛡 Validation

The form includes both:

- ✅ **Client-side validation** (JavaScript)
- ✅ **Server-side validation** (PHP)

Validated fields:
- Email (valid format)
- Phone (10-digit U.S.)
- ZIP Code (5 or 9 digits)
- NPI (10 digits)

---


## 📬 Questions?

Reach out to the Pulse Health team or open an issue if you need support @ dev@pulsehealth.tech
