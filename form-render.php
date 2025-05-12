<?php
function pulse_health_form_shortcode() {
    $saved_fields = get_option('pulse_form_fields') ?: [];
    $saved_order = get_option('pulse_form_order') ?: [];

    $field_definitions = [
        'firstName' => ['label' => 'First Name'],
        'lastName' => ['label' => 'Last Name'],
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
    ];

    usort($saved_fields, function($a, $b) use ($saved_order) {
        return ($saved_order[$a] ?? 100) <=> ($saved_order[$b] ?? 100);
    });

    $form_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pulse_form_submit'])) {
        $contact = [];
        $customFields = [];

        foreach ($saved_fields as $field) {
            if ($field === 'emailAddress') continue;
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

        if (!isset($_POST['emailAddress']) || !is_email($_POST['emailAddress'])) {
            $form_error .= '<p style="color:red;">Please enter a valid email address.</p>';
        } else {
            $contact['emailAddress'] = sanitize_email($_POST['emailAddress']);
        }

        if (isset($contact['phone']) && !preg_match('/^\d{10}$/', $contact['phone'])) {
            $form_error .= '<p style="color:red;">Phone number must be 10 digits.</p>';
        }
        if (isset($contact['postalCode']) && !preg_match('/^\d{5}(-\d{4})?$/', $contact['postalCode'])) {
            $form_error .= '<p style="color:red;">Please enter a valid ZIP code.</p>';
        }
        if (isset($contact['npi']) && !preg_match('/^\d{10}$/', $contact['npi'])) {
            $form_error .= '<p style="color:red;">NPI must be a 10-digit number.</p>';
        }

        if (!empty($customFields)) {
            $contact['customFields'] = $customFields;
        }

        if (!$form_error) {
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
            <p><label>Email Address <span style="color:red">*</span></label><br />
                <input type="email" name="emailAddress" required /></p>

            <?php echo $form_error; ?>
            <?php foreach ($saved_fields as $field): if ($field === 'emailAddress') continue; ?>
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
