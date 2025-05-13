<?php
function pulse_health_register_settings() {
    add_option('pulse_account_id');
    add_option('pulse_api_key');
    add_option('pulse_form_id');
    add_option('pulse_form_fields');
    add_option('pulse_form_order');
}
add_action('admin_init', 'pulse_health_register_settings');

add_action('admin_init', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['pulse_test_connection'])) {
            $account_id = sanitize_text_field($_POST['pulse_account_id']);
            $api_key = sanitize_text_field($_POST['pulse_api_key']);

            $response = wp_remote_get('https://api.pulsehealth.tech/forms', [
                'headers' => [
                    'X-Account' => trim($account_id),
                    'Authorization' => 'API-TOKEN ' . trim($api_key),
                ]
            ]);

            $status = is_wp_error($response) ? 'error' : wp_remote_retrieve_response_code($response);
            set_transient('pulse_test_status', $status, 30);
            wp_redirect(admin_url('options-general.php?page=pulse-health&step=1'));
            exit;
        }

        if (isset($_POST['pulse_account_id']) && isset($_POST['pulse_api_key']) && !isset($_POST['pulse_test_connection'])) {
            update_option('pulse_account_id', sanitize_text_field($_POST['pulse_account_id']));
            update_option('pulse_api_key', sanitize_text_field($_POST['pulse_api_key']));
            wp_redirect(admin_url('options-general.php?page=pulse-health&step=2'));
            exit;
        }

        if (isset($_POST['pulse_form_id'])) {
            update_option('pulse_form_id', sanitize_text_field($_POST['pulse_form_id']));
            wp_redirect(admin_url('options-general.php?page=pulse-health&step=3'));
            exit;
        }

        if (isset($_POST['pulse_form_fields'])) {
            update_option('pulse_form_fields', $_POST['pulse_form_fields'] ?? []);
            update_option('pulse_form_order', $_POST['pulse_form_order'] ?? []);
            set_transient('pulse_form_saved', true, 30);
        }
    }
});

function pulse_health_register_options_page() {
    add_options_page('Pulse Health Settings', 'Pulse Health Form', 'manage_options', 'pulse-health', 'pulse_health_options_page');
}
add_action('admin_menu', 'pulse_health_register_options_page');

function pulse_health_options_page() {
    ob_start();

    $step = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : '1';

    $account_id = get_option('pulse_account_id');
    $api_key = get_option('pulse_api_key');
    $selected_form_id = get_option('pulse_form_id');
    $saved_fields = get_option('pulse_form_fields') ?: [];
    $saved_order = get_option('pulse_form_order') ?: [];

    $available_fields = [
        'firstName' => 'First Name',
        'lastName' => 'Last Name',
        'salutation' => 'Salutation',
        'middleName' => 'Middle Name',
        'credentials' => 'Credentials',
        'gender' => 'Gender',
        'npi' => 'NPI',
        'specialty' => 'Specialty',
        'contactType' => 'Contact Type',
        'company' => 'Company',
        'addressLine1' => 'Address Line 1',
        'addressLine2' => 'Address Line 2',
        'city' => 'City',
        'state' => 'State',
        'postalCode' => 'Postal Code',
        'country' => 'Country',
        'phone' => 'Phone',
        'phoneIsCallSubscribed' => 'Phone Call Subscribed',
        'phoneIsSmsSubscribed' => 'SMS Subscribed'
    ];

    $form_ready = $account_id && $api_key && $selected_form_id && !empty($saved_fields);

    echo '<div class="wrap">';
    echo '<img src="https://pulsehealth.tech/assets/images/ic_pulse-health-new-logos.png" alt="Pulse Health" style="max-height:40px; margin-bottom: 1rem;">';
    echo '<h2>Pulse Health Webform Settings</h2>';

    if ($form_ready && $step === '1') {
        echo '<div style="background: #f9f9f9; padding: 1rem; border-left: 4px solid #00a0d2;">';
        echo '<p><strong>To embed your form on any page or post, use the shortcode:</strong></p>';
        echo '<code>[pulse_health_form]</code>';
        echo '</div><br>';
    }

    if ($step === '1') {
        echo '<form method="post" action="">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="pulse_account_id">Account ID</label></th>';
        echo '<td><input type="text" name="pulse_account_id" value="' . esc_attr($account_id) . '" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="pulse_api_key">API Key</label></th>';
        echo '<td><input type="text" name="pulse_api_key" value="' . esc_attr($api_key) . '" class="regular-text" /></td></tr>';
        echo '</table>';
        submit_button('Save and Continue');
        echo '</form>';

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="pulse_account_id" value="' . esc_attr($account_id) . '" />';
        echo '<input type="hidden" name="pulse_api_key" value="' . esc_attr($api_key) . '" />';
        echo '<input type="submit" name="pulse_test_connection" class="button button-secondary" value="Test Connection" />';
        $test_status = get_transient('pulse_test_status');
        if ($test_status) {
            if ($test_status === 'error') {
                echo '<p style="color:red;">❌ Connection failed.</p>';
            } elseif ($test_status === '200') {
                echo '<p style="color:green;">✅ Connection successful.</p>';
            } else {
                echo '<p>HTTP Status: ' . esc_html($test_status) . '</p>';
            }
            delete_transient('pulse_test_status');
        }
        echo '</form>';
    }

    if ($step === '2' && $account_id && $api_key) {
        $forms = [];
        $response = wp_remote_get('https://api.pulsehealth.tech/forms', [
            'headers' => [
                'X-Account' => trim($account_id),
                'Authorization' => 'API-TOKEN ' . trim($api_key)
            ]
        ]);
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $decoded = json_decode($body, true);
            if (isset($decoded['items']) && is_array($decoded['items'])) {
                $forms = $decoded['items'];
            }
        }

        echo '<form method="post" action="">';
        echo '<table class="form-table">';
        echo '<tr><th><label for="pulse_form_id">Select a Form</label></th>';
        echo '<td><select name="pulse_form_id">';
        echo '<option value="">-- Select a form --</option>';
        foreach ($forms as $form) {
            $name = esc_html($form['name'] ?? 'Untitled');
            $val = esc_attr($form['uuid']);
            $selected = selected($val, $selected_form_id, false);
            echo '<option value="' . $val . '" ' . $selected . '>' . $name . '</option>';
        }
        echo '</select></td></tr>';
        echo '</table>';
        submit_button('Save and Continue');
        echo '</form>';
    }

    if ($step === '3') {
        if (get_transient('pulse_form_saved')) {
            echo '<div class="updated"><p>✅ Form settings saved successfully.</p>';
            echo '<p><strong>Use this shortcode to embed your form:</strong><br>';
            echo '<code>[pulse_health_form]</code></p></div>';
            delete_transient('pulse_form_saved');
        }

        echo '<form method="post" action="">';
        echo '<h3>Select Fields to Display</h3>';
        echo '<p><em>Email will always be included.</em></p>';
        echo '<table class="widefat striped">';
        foreach ($available_fields as $key => $label) {
            $isChecked = checked(in_array($key, $saved_fields), true, false);
            $order = isset($saved_order[$key]) ? (int)$saved_order[$key] : '1';
            echo "<tr><td><input type='checkbox' name='pulse_form_fields[]' value='$key' $isChecked></td>";
            echo "<td>$label</td>";
            echo "<td>Order: <input type='number' name='pulse_form_order[$key]' value='$order' min='1' max='100' style='width:60px;'></td></tr>";
        }
        echo '</table>';
        submit_button('Save All Settings');
        echo '</form>';
    }

    echo '</div>';
    ob_end_flush();
}
