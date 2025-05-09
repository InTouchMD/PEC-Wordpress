<?php
function pulse_health_form_shortcode() {
    $saved_fields = get_option('pulse_form_fields') ?: [];
    $saved_order = get_option('pulse_form_order') ?: [];

    $field_definitions = [
        'salutation' => ['label' => 'Salutation'],
        'middleName' => ['label' => 'Middle Name'],
        'credentials' => ['label' => 'Credentials'],
        'gender' => ['label' => 'Gender'],
        'npi' => ['label' => 'NPI'],
        'specialty' => ['label' => 'Specialty'],
        'contactType' => ['label' => 'Contact Type'],
        'company' => ['label' => 'Company'],
        'addressLine1' => ['label' => 'Address Line 1'],
        'addressLine2' => ['label' => 'Address Line 2'],
        'city' => ['label' => 'City'],
        'state' => ['label' => 'State'],
        'postalCode' => ['label' => 'Postal Code'],
        'country' => ['label' => 'Country'],
        'phone' => ['label' => 'Phone'],
        'phoneIsCallSubscribed' => ['label' => 'Phone Call Subscribed', 'boolean' => true],
        'phoneIsSmsSubscribed' => ['label' => 'SMS Subscribed', 'boolean' => true],
        'highValue' => ['label' => 'High Value (Custom)', 'boolean' => true],
    ];

    usort($saved_fields, function($a, $b) use ($saved_order) {
        return ($saved_order[$a] ?? 100) <=> ($saved_order[$b] ?? 100);
    });

    $form_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pulse_form_submit'])) {
        $email = sanitize_email($_POST['emailAddress']);
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $postalCode = sanitize_text_field($_POST['postalCode'] ?? '');
        $npi = sanitize_text_field($_POST['npi'] ?? '');

        if (!is_email($email)) {
            $form_error .= '<p style="color:red;">Please enter a valid email address.</p>';
        }
        if ($phone && !preg_match('/^\d{10}$/', $phone)) {
            $form_error .= '<p style="color:red;">Phone number must be 10 digits.</p>';
        }
        if ($postalCode && !preg_match('/^\d{5}(-\d{4})?$/', $postalCode)) {
            $form_error .= '<p style="color:red;">Please enter a valid ZIP code.</p>';
        }
        if ($npi && !preg_match('/^\d{10}$/', $npi)) {
            $form_error .= '<p style="color:red;">NPI must be a 10-digit number.</p>';
        }

        if (!$form_error) {
            $contact = [
                'firstName' => sanitize_text_field($_POST['firstName']),
                'lastName' => sanitize_text_field($_POST['lastName']),
                'emailAddress' => $email,
            ];
            $customFields = [];

            foreach ($saved_fields as $field) {
                $val = $_POST[$field] ?? null;
                if (isset($field_definitions[$field]['boolean'])) {
                    $val = $val === '1' ? true : false;
                }
                if ($field === 'highValue') {
                    $customFields[$field] = $val;
                } elseif ($field === 'country' && empty($val)) {
                    $contact[$field] = 'USA';
                } else {
                    $contact[$field] = sanitize_text_field($val);
                }
            }

            if (!empty($customFields)) {
                $contact['customFields'] = $customFields;
            }

            $form_id = get_option('pulse_form_id');
            $account_id = get_option('pulse_account_id');
            $api_key = get_option('pulse_api_key');

            $url = "https://api.pulsehealth.tech/forms/{$form_id}/submit";

            $response = wp_remote_post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Account' => $account_id,
                    'Authorization' => 'API-TOKEN ' . $api_key,
                ],
                'body' => json_encode($contact)
            ]);

            if (is_wp_error($response)) {
                $form_error = '<p style="color:red;">Submission failed. Please try again.</p>';
            } else {
                echo '<p>Thank you! Your submission was successful.</p>';
                return;
            }
        }
    }

    ob_start(); ?>
    <div class="pulse-health-form">
    <form method="post">
        <?php echo $form_error; ?>
        <p><label>First Name</label><br /><input type="text" name="firstName" required /></p>
        <p><label>Last Name</label><br /><input type="text" name="lastName" required /></p>
        <p><label>Email Address</label><br /><input type="email" name="emailAddress" required /></p>

        <?php foreach ($saved_fields as $field): ?>
            <p>
                <label><?php echo esc_html($field_definitions[$field]['label'] ?? $field); ?></label><br />
                <?php if (!empty($field_definitions[$field]['boolean'])): ?>
                    <label><input type="radio" name="<?php echo $field; ?>" value="1" /> Yes</label>
                    <label><input type="radio" name="<?php echo $field; ?>" value="0" /> No</label>
                <?php else: ?>
                    <input type="text" name="<?php echo $field; ?>" <?php echo $field === 'country' ? 'value="USA"' : ''; ?> />
                <?php endif; ?>
            </p>
        <?php endforeach; ?>

        <p><input type="submit" name="pulse_form_submit" value="Submit" /></p>
    </form>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('pulse_health_form', 'pulse_health_form_shortcode');
