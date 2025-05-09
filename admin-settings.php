<?php
function pulse_health_register_settings() {
    add_option('pulse_account_id');
    add_option('pulse_form_id');
    add_option('pulse_api_key');
    add_option('pulse_form_fields');
    add_option('pulse_form_order');

    register_setting('pulse_options_group', 'pulse_account_id');
    register_setting('pulse_options_group', 'pulse_form_id');
    register_setting('pulse_options_group', 'pulse_api_key');
    register_setting('pulse_options_group', 'pulse_form_fields');
    register_setting('pulse_options_group', 'pulse_form_order');
}
add_action('admin_init', 'pulse_health_register_settings');

function pulse_health_register_options_page() {
    add_options_page('Pulse Health Settings', 'Pulse Health Form', 'manage_options', 'pulse-health', 'pulse_health_options_page');
}
add_action('admin_menu', 'pulse_health_register_options_page');

function pulse_health_options_page() {
    $available_fields = [
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
        'phoneIsSmsSubscribed' => 'SMS Subscribed',
        'highValue' => 'High Value (Custom Field)'
    ];

    $saved_fields = get_option('pulse_form_fields') ?: [];
    $saved_order = get_option('pulse_form_order') ?: [];
?>
  <div class="wrap">
  <h2>Pulse Health Webform Settings</h2>
  <form method="post" action="options.php">
      <?php settings_fields('pulse_options_group'); ?>
      <table class="form-table">
          <tr valign="top">
              <th scope="row"><label for="pulse_account_id">Account ID</label></th>
              <td><input type="text" name="pulse_account_id" value="<?php echo esc_attr(get_option('pulse_account_id')); ?>" class="regular-text"/></td>
          </tr>
          <tr valign="top">
              <th scope="row"><label for="pulse_form_id">Form ID</label></th>
              <td><input type="text" name="pulse_form_id" value="<?php echo esc_attr(get_option('pulse_form_id')); ?>" class="regular-text"/></td>
          </tr>
          <tr valign="top">
              <th scope="row"><label for="pulse_api_key">API Key</label></th>
              <td><input type="text" name="pulse_api_key" value="<?php echo esc_attr(get_option('pulse_api_key')); ?>" class="regular-text"/></td>
          </tr>
      </table>

      <h3>Select Fields to Display in the Form</h3>
      <p><em>First Name, Last Name, and Email are always included.</em></p>
      <table class="widefat striped">
      <?php foreach ($available_fields as $key => $label): ?>
          <tr>
              <td><input type="checkbox" name="pulse_form_fields[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $saved_fields)); ?>></td>
              <td><?php echo esc_html($label); ?></td>
              <td>Order: <input type="number" name="pulse_form_order[<?php echo $key; ?>]" value="<?php echo isset($saved_order[$key]) ? intval($saved_order[$key]) : ''; ?>" min="1" max="100" style="width: 60px;"></td>
          </tr>
      <?php endforeach; ?>
      </table>

      <?php submit_button(); ?>
  </form>
  </div>
<?php
}
